<?php

namespace Tests\Fakes;

use App\Enums\SubscriptionStatus;
use App\Exceptions\CheckoutSessionNotFoundException;
use App\Interfaces\Services\StripeGatewayInterface;
use Stripe\Event;
use Stripe\Webhook;

/**
 * In-memory Stripe gateway for tests. Never touches the network; records
 * every call in $calls (method => list of argument arrays).
 *
 * Usage: $fake = new FakeStripeGateway;
 *        $fake->sessions['cs_1'] = FakeStripeGateway::sessionData(['client_reference_id' => $user->id]);
 *        $this->app->instance(StripeGatewayInterface::class, $fake);
 */
class FakeStripeGateway implements StripeGatewayInterface
{
    /** @var array<string, array<string, mixed>> session id => normalized session array */
    public array $sessions = [];

    /** Thrown by retrieveCheckoutSession() when set (simulates a Stripe outage). */
    public ?\Throwable $retrieveException = null;

    /** Thrown by cancelImmediately() when set. */
    public ?\Throwable $cancelException = null;

    public string $checkoutUrl = 'https://checkout.stripe.test/session';

    /** @var array<string, list<array<int, mixed>>> */
    public array $calls = [];

    /**
     * Normalized checkout session, as returned by retrieveCheckoutSession().
     *
     * @param  array<string, mixed>  $overrides  shallow overrides (pass 'invoice' => null to drop it)
     * @return array<string, mixed>
     */
    public static function sessionData(array $overrides = []): array
    {
        return array_replace([
            'id' => 'cs_test_fake',
            'status' => 'complete',
            'client_reference_id' => null,
            'metadata_user_id' => null,
            'customer_id' => 'cus_fake',
            'subscription_id' => 'sub_fake',
            'subscription_status' => 'active',
            'cancel_at_period_end' => false,
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'ended_at' => null,
            'price_id' => 'price_test_session',
            'invoice' => [
                'id' => 'in_fake',
                'amount_paid' => 2990,
                'amount_due' => 2990,
                'currency' => 'BRL',
                'status' => 'paid',
                'hosted_invoice_url' => 'https://invoice.stripe.test/in_fake',
                'invoice_pdf' => 'https://invoice.stripe.test/in_fake.pdf',
                'paid_at' => now(),
                'period_start' => now(),
                'period_end' => now()->addMonth(),
            ],
        ], $overrides);
    }

    public function ensureCustomer(?string $existingCustomerId, string $email, string $name, string $userId): string
    {
        $this->calls['ensureCustomer'][] = func_get_args();

        return $existingCustomerId ?? 'cus_fake_'.$userId;
    }

    public function createCheckoutSession(string $customerId, string $priceId, string $userId, string $successUrl, string $cancelUrl): string
    {
        $this->calls['createCheckoutSession'][] = func_get_args();

        return $this->checkoutUrl;
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $this->calls['retrieveCheckoutSession'][] = func_get_args();

        if ($this->retrieveException) {
            throw $this->retrieveException;
        }

        if (! isset($this->sessions[$sessionId])) {
            throw new CheckoutSessionNotFoundException;
        }

        return $this->sessions[$sessionId];
    }

    public function cancelAtPeriodEnd(string $stripeSubscriptionId): array
    {
        $this->calls['cancelAtPeriodEnd'][] = func_get_args();

        return $this->subscriptionResponse($stripeSubscriptionId, true);
    }

    public function cancelImmediately(string $stripeSubscriptionId): array
    {
        $this->calls['cancelImmediately'][] = func_get_args();

        if ($this->cancelException) {
            throw $this->cancelException;
        }

        return [
            'id' => $stripeSubscriptionId,
            'status' => 'canceled',
            'cancel_at_period_end' => false,
            'period_start' => now()->subDays(5),
            'period_end' => now()->addDays(25),
            'canceled_at' => now(),
            'ended_at' => now(),
        ];
    }

    public function resumeSubscription(string $stripeSubscriptionId): array
    {
        $this->calls['resumeSubscription'][] = func_get_args();

        return $this->subscriptionResponse($stripeSubscriptionId, false);
    }

    public function constructWebhookEvent(string $payload, ?string $signature, string $secret): Event
    {
        return Webhook::constructEvent($payload, (string) $signature, $secret);
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionResponse(string $id, bool $cancelAtPeriodEnd): array
    {
        return [
            'id' => $id,
            'status' => SubscriptionStatus::ACTIVE,
            'price_id' => null,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
            'period_start' => now()->subDays(5),
            'period_end' => now()->addDays(25),
            'canceled_at' => $cancelAtPeriodEnd ? now() : null,
            'ended_at' => null,
        ];
    }
}
