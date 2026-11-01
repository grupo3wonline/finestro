<?php

namespace Tests\Feature\Billing;

use App\Interfaces\Services\StripeGatewayInterface;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

/**
 * Stripe webhooks: signature is verified locally (HMAC-SHA256), each event id
 * is processed once, and every handled event updates local state correctly.
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_local';

    private FakeStripeGateway $fake;

    protected function setUp(): void
    {
        parent::setUp();

        config(['stripe.webhook_secret' => self::SECRET]);

        $this->fake = new FakeStripeGateway;
        $this->app->instance(StripeGatewayInterface::class, $this->fake);
    }

    private function payload(string $type, array $object, ?string $eventId = null): string
    {
        return json_encode([
            'id' => $eventId ?? 'evt_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'api_version' => '2026-04-22.dahlia',
            'created' => time(),
            'data' => ['object' => $object],
        ]);
    }

    private function signature(string $payload, string $secret = self::SECRET): string
    {
        $t = time();

        return "t={$t},v1=".hash_hmac('sha256', "{$t}.{$payload}", $secret);
    }

    private function send(string $payload, ?string $signature): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($signature !== null) {
            $server['HTTP_STRIPE_SIGNATURE'] = $signature;
        }

        return $this->call('POST', route('stripe.webhook'), [], [], [], $server, $payload);
    }

    private function sendEvent(string $type, array $object, ?string $eventId = null): TestResponse
    {
        $payload = $this->payload($type, $object, $eventId);

        return $this->send($payload, $this->signature($payload));
    }

    private function proSubscription(array $attributes = []): Subscription
    {
        return Subscription::factory()->for(User::factory())->create(array_replace([
            'stripe_subscription_id' => 'sub_live',
            'status' => 'active',
            'current_period_start' => now()->subDays(5),
            'current_period_end' => now()->addDays(25),
        ], $attributes));
    }

    private function invoice(array $overrides = [], bool $legacy = false): array
    {
        $invoice = [
            'id' => 'in_live_1',
            'object' => 'invoice',
            'amount_paid' => 2990,
            'amount_due' => 2990,
            'currency' => 'brl',
            'status' => 'paid',
            'hosted_invoice_url' => 'https://invoice.stripe.test/in_live_1',
            'invoice_pdf' => 'https://invoice.stripe.test/in_live_1.pdf',
            'status_transitions' => ['paid_at' => time()],
            'lines' => [
                'object' => 'list',
                'data' => [[
                    'object' => 'line_item',
                    'period' => ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp],
                ]],
            ],
        ];

        if ($legacy) {
            $invoice['subscription'] = 'sub_live';
        } else {
            $invoice['parent'] = [
                'type' => 'subscription_details',
                'subscription_details' => ['subscription' => 'sub_live'],
            ];
        }

        return array_replace($invoice, $overrides);
    }

    public function test_unset_secret_returns_400(): void
    {
        config(['stripe.webhook_secret' => '']);
        $payload = $this->payload('invoice.paid', $this->invoice());

        $this->send($payload, $this->signature($payload))->assertStatus(400);
        $this->assertDatabaseCount('stripe_webhook_events', 0);
    }

    public function test_missing_signature_returns_400(): void
    {
        $this->send($this->payload('invoice.paid', $this->invoice()), null)->assertStatus(400);
    }

    public function test_wrong_secret_returns_400_and_writes_nothing(): void
    {
        $this->proSubscription();
        $payload = $this->payload('invoice.paid', $this->invoice());

        $this->send($payload, $this->signature($payload, 'whsec_wrong'))->assertStatus(400);

        $this->assertDatabaseCount('payment_histories', 0);
        $this->assertDatabaseCount('stripe_webhook_events', 0);
    }

    public function test_invoice_paid_records_one_paid_row_with_receipt_url(): void
    {
        $subscription = $this->proSubscription();

        $this->sendEvent('invoice.paid', $this->invoice())->assertOk();

        $this->assertDatabaseCount('payment_histories', 1);
        $this->assertDatabaseHas('payment_histories', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => 'in_live_1',
            'amount' => 29.90,
            'currency' => 'BRL',
            'status' => 'paid',
            'hosted_invoice_url' => 'https://invoice.stripe.test/in_live_1',
        ]);
        $this->assertSame('active', $subscription->fresh()->status->value);
    }

    public function test_invoice_paid_with_legacy_subscription_field_works(): void
    {
        $this->proSubscription();

        $this->sendEvent('invoice.paid', $this->invoice([], legacy: true))->assertOk();

        $this->assertDatabaseHas('payment_histories', ['stripe_invoice_id' => 'in_live_1', 'status' => 'paid']);
    }

    public function test_same_event_twice_is_processed_once(): void
    {
        $this->proSubscription();
        $payload = $this->payload('invoice.paid', $this->invoice(), 'evt_duplicate');

        $this->send($payload, $this->signature($payload))->assertOk();
        $this->send($payload, $this->signature($payload))->assertOk()->assertSee('Already processed');

        $this->assertDatabaseCount('payment_histories', 1);
        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    public function test_invoice_payment_failed_sets_past_due_and_user_loses_pro(): void
    {
        $subscription = $this->proSubscription();

        $this->sendEvent('invoice.payment_failed', $this->invoice([
            'status' => 'open',
            'amount_paid' => 0,
            'status_transitions' => ['paid_at' => null],
        ]))->assertOk();

        $this->assertSame('past_due', $subscription->fresh()->status->value);
        $this->assertDatabaseHas('payment_histories', [
            'stripe_invoice_id' => 'in_live_1',
            'status' => 'failed',
            'amount' => 29.90,
        ]);
        $this->assertFalse(User::find($subscription->user_id)->isProfessional());
    }

    public function test_failed_event_never_downgrades_a_paid_invoice(): void
    {
        $this->proSubscription();

        $this->sendEvent('invoice.paid', $this->invoice())->assertOk();
        $this->sendEvent('invoice.payment_failed', $this->invoice(['status' => 'open']))->assertOk();

        $this->assertDatabaseCount('payment_histories', 1);
        $this->assertDatabaseHas('payment_histories', ['stripe_invoice_id' => 'in_live_1', 'status' => 'paid']);
    }

    public function test_subscription_updated_stores_period_and_pending_cancellation(): void
    {
        $subscription = $this->proSubscription();
        $end = now()->addDays(40)->startOfSecond();

        $this->sendEvent('customer.subscription.updated', [
            'id' => 'sub_live',
            'object' => 'subscription',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'canceled_at' => time(),
            'items' => [
                'object' => 'list',
                'data' => [[
                    'object' => 'subscription_item',
                    'current_period_start' => now()->subDays(10)->timestamp,
                    'current_period_end' => $end->timestamp,
                ]],
            ],
        ])->assertOk();

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->cancel_at_period_end);
        $this->assertSame($end->timestamp, $fresh->current_period_end->timestamp);
        $this->assertTrue($fresh->hasPendingCancellation());
        $this->assertTrue(User::find($subscription->user_id)->isProfessional());
    }

    public function test_subscription_deleted_cancels_and_user_becomes_free(): void
    {
        $subscription = $this->proSubscription();

        $this->sendEvent('customer.subscription.deleted', [
            'id' => 'sub_live',
            'object' => 'subscription',
            'status' => 'canceled',
            'canceled_at' => time() - 60,
            'ended_at' => time() - 60,
        ])->assertOk();

        $fresh = $subscription->fresh();
        $this->assertSame('canceled', $fresh->status->value);
        $this->assertFalse($fresh->cancel_at_period_end);
        $this->assertFalse(User::find($subscription->user_id)->isProfessional());
    }

    public function test_checkout_session_completed_activates_owner(): void
    {
        $user = User::factory()->create();
        $this->fake->sessions['cs_hook'] = FakeStripeGateway::sessionData([
            'id' => 'cs_hook',
            'client_reference_id' => $user->id,
            'subscription_id' => 'sub_hook',
        ]);

        $this->sendEvent('checkout.session.completed', [
            'id' => 'cs_hook',
            'object' => 'checkout.session',
            'client_reference_id' => $user->id,
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_hook',
            'status' => 'active',
            'plan' => 'professional',
        ]);
        $this->assertTrue($user->fresh()->isProfessional());
    }

    public function test_handler_failure_returns_500_and_rolls_back_so_stripe_retries(): void
    {
        $user = User::factory()->create();
        $this->fake->sessions['cs_fail'] = FakeStripeGateway::sessionData([
            'id' => 'cs_fail',
            'client_reference_id' => $user->id,
        ]);
        $this->fake->retrieveException = new \RuntimeException('Stripe unavailable');

        $payload = $this->payload('checkout.session.completed', [
            'id' => 'cs_fail',
            'object' => 'checkout.session',
            'client_reference_id' => $user->id,
        ], 'evt_retry_me');

        $this->send($payload, $this->signature($payload))->assertStatus(500);

        $this->assertDatabaseCount('stripe_webhook_events', 0);
        $this->assertDatabaseCount('subscriptions', 0);

        // Stripe's retry of the same event is processed once the outage is over.
        $this->fake->retrieveException = null;
        $this->send($payload, $this->signature($payload))->assertOk()->assertSee('OK');

        $this->assertDatabaseHas('stripe_webhook_events', ['stripe_event_id' => 'evt_retry_me']);
        $this->assertTrue($user->fresh()->isProfessional());
    }

    public function test_unknown_event_type_returns_200_and_is_recorded(): void
    {
        $this->sendEvent('customer.created', ['id' => 'cus_x', 'object' => 'customer'], 'evt_unknown_type')->assertOk();

        $this->assertDatabaseHas('stripe_webhook_events', ['stripe_event_id' => 'evt_unknown_type', 'type' => 'customer.created']);
    }

    public function test_unknown_subscription_returns_200_without_changes(): void
    {
        $subscription = $this->proSubscription();

        $this->sendEvent('invoice.paid', $this->invoice([
            'parent' => ['type' => 'subscription_details', 'subscription_details' => ['subscription' => 'sub_unknown']],
        ]))->assertOk();

        $this->assertDatabaseCount('payment_histories', 0);
        $this->assertSame('active', $subscription->fresh()->status->value);
    }
}
