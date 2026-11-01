<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Exceptions\CheckoutSessionNotFoundException;
use App\Exceptions\SubscriptionActionException;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

/**
 * Exercises the subscription business logic with a faked Stripe gateway, so
 * checkout, success reconciliation and cancellation are verified without ever
 * calling the real Stripe API.
 *
 * Two test doubles are used on purpose:
 *  - FakeStripeGateway (state-based): preferred for flows whose assertions are
 *    about what got persisted. Register it with bindFakeGateway().
 *  - Mockery (interaction-based): used only where the test must prove a specific
 *    gateway call happened with specific arguments. Register it with mockGateway().
 */
class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function bindFakeGateway(): FakeStripeGateway
    {
        $gateway = new FakeStripeGateway;
        $this->app->instance(StripeGatewayInterface::class, $gateway);

        return $gateway;
    }

    private function mockGateway(): Mockery\MockInterface
    {
        $gateway = Mockery::mock(StripeGatewayInterface::class);
        $this->app->instance(StripeGatewayInterface::class, $gateway);

        return $gateway;
    }

    private function service(): SubscriptionServiceInterface
    {
        return $this->app->make(SubscriptionServiceInterface::class);
    }

    public function test_start_checkout_returns_gateway_url(): void
    {
        config(['stripe.plans.professional.stripe_price_id' => 'price_test_123']);

        $gateway = $this->bindFakeGateway();
        $gateway->checkoutUrl = 'https://checkout.stripe.test/session';

        $user = User::factory()->create();
        $url = $this->service()->startCheckout($user);

        $this->assertSame('https://checkout.stripe.test/session', $url);
        $this->assertCount(1, $gateway->calls['ensureCustomer'] ?? []);
        $this->assertCount(1, $gateway->calls['createCheckoutSession'] ?? []);
    }

    public function test_start_checkout_throws_when_price_not_configured(): void
    {
        config(['stripe.plans.professional.stripe_price_id' => null]);

        $this->bindFakeGateway();
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->service()->startCheckout($user);
    }

    public function test_start_checkout_throws_when_user_is_already_professional(): void
    {
        config(['stripe.plans.professional.stripe_price_id' => 'price_test_123']);

        $this->bindFakeGateway();

        $user = User::factory()->create();
        Subscription::factory()->for($user)->create([
            'plan' => 'professional',
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->expectException(SubscriptionActionException::class);
        $this->service()->startCheckout($user->refresh());
    }

    public function test_handle_checkout_success_persists_subscription_and_payment(): void
    {
        config(['stripe.plans.professional.stripe_price_id' => 'price_test_123']);

        $user = User::factory()->create();

        $gateway = $this->bindFakeGateway();
        $gateway->sessions['cs_test_session'] = FakeStripeGateway::sessionData([
            'id' => 'cs_test_session',
            'client_reference_id' => $user->id,
            'subscription_id' => 'sub_abc',
            'customer_id' => 'cus_abc',
        ]);

        $this->service()->handleCheckoutSuccess($user, 'cs_test_session');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_abc',
            'plan' => 'professional',
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('payment_histories', [
            'user_id' => $user->id,
            'stripe_invoice_id' => 'in_fake',
            'status' => 'paid',
            // decimal(10,2) column: compare as a string to stay driver-agnostic.
            'amount' => '29.90',
        ]);

        $this->assertTrue($user->refresh()->isProfessional());
    }

    public function test_handle_checkout_success_rejects_session_of_another_user(): void
    {
        $user = User::factory()->create();
        $attacker = User::factory()->create();

        $gateway = $this->bindFakeGateway();
        $gateway->sessions['cs_test_session'] = FakeStripeGateway::sessionData([
            'id' => 'cs_test_session',
            'client_reference_id' => $attacker->id,
            'subscription_id' => 'sub_abc',
            'customer_id' => 'cus_abc',
        ]);

        $this->expectException(CheckoutSessionNotFoundException::class);

        try {
            $this->service()->handleCheckoutSuccess($user, 'cs_test_session');
        } finally {
            // Nothing should have been persisted for the victim.
            $this->assertDatabaseMissing('subscriptions', [
                'user_id' => $user->id,
                'stripe_subscription_id' => 'sub_abc',
            ]);
        }
    }

    public function test_cancel_at_period_end_throws_without_active_professional_subscription(): void
    {
        $this->bindFakeGateway();

        $user = User::factory()->create();

        $this->expectException(SubscriptionActionException::class);
        $this->service()->cancelAtPeriodEnd($user);
    }

    public function test_cancel_at_period_end_keeps_access_and_calls_gateway(): void
    {
        // Interaction-based: the point of this test is that the gateway is asked
        // to cancel the exact remote subscription id, so Mockery is the right fit.
        $gateway = $this->mockGateway();
        $gateway->shouldReceive('cancelAtPeriodEnd')->once()->with('sub_cancel')->andReturn([
            'id' => 'sub_cancel',
            'status' => SubscriptionStatus::ACTIVE,
            'price_id' => null,
            'cancel_at_period_end' => true,
            'period_start' => now()->subDays(5),
            'period_end' => now()->addDays(25),
            'canceled_at' => now(),
            'ended_at' => null,
        ]);

        $user = User::factory()->create();
        Subscription::factory()->for($user)->create([
            'plan' => 'professional',
            'stripe_subscription_id' => 'sub_cancel',
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $this->service()->cancelAtPeriodEnd($user);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'cancel_at_period_end' => true,
        ]);
        $this->assertTrue($user->refresh()->isProfessional());
    }

    public function test_payment_history_is_scoped_per_user(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();

        $mySub = Subscription::factory()->for($mine)->create();
        $otherSub = Subscription::factory()->for($other)->create();

        PaymentHistory::factory()->count(2)->create([
            'user_id' => $mine->id,
            'subscription_id' => $mySub->id,
        ]);
        PaymentHistory::factory()->count(4)->create([
            'user_id' => $other->id,
            'subscription_id' => $otherSub->id,
        ]);

        $service = $this->service();

        $mineHistory = $service->getPaymentHistory($mine);
        $otherHistory = $service->getPaymentHistory($other);

        // getPaymentHistory() returns a paginator; count() would only reflect the
        // current page, so assert the real totals instead.
        $this->assertSame(2, $mineHistory->total());
        $this->assertSame(4, $otherHistory->total());

        // Each user must see only their own rows (ACL / user_id scoping).
        $this->assertTrue($mineHistory->pluck('user_id')->every(fn ($id) => $id === $mine->id));
        $this->assertTrue($otherHistory->pluck('user_id')->every(fn ($id) => $id === $other->id));
    }
}
