<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Enums\SubscriptionStatus;
use App\Exceptions\CheckoutSessionNotFoundException;
use App\Exceptions\StripeResourceMissingException;
use Carbon\CarbonInterface;
use Stripe\Event;

interface StripeGatewayInterface
{
    /**
     * Ensure a Stripe customer exists for the given identity, returning its id.
     */
    public function ensureCustomer(?string $existingCustomerId, string $email, string $name, string $userId): string;

    /**
     * Create a subscription Checkout session and return its redirect URL.
     * The session carries client_reference_id and metadata.user_id = $userId.
     */
    public function createCheckoutSession(string $customerId, string $priceId, string $userId, string $successUrl, string $cancelUrl): string;

    /**
     * Retrieve a Checkout session (subscription + invoice expanded) as a normalized array.
     *
     * @return array{id: string, status: ?string, client_reference_id: ?string, metadata_user_id: ?string, customer_id: ?string, subscription_id: ?string, subscription_status: ?string, cancel_at_period_end: bool, period_start: ?CarbonInterface, period_end: ?CarbonInterface, ended_at: ?CarbonInterface, price_id: ?string, invoice: ?array<string, mixed>}
     *
     * @throws CheckoutSessionNotFoundException when Stripe does not know the id
     */
    public function retrieveCheckoutSession(string $sessionId): array;

    /**
     * Schedule cancellation at the end of the current period.
     *
     * @return array{id: ?string, status: SubscriptionStatus, price_id: ?string, cancel_at_period_end: bool, period_start: ?CarbonInterface, period_end: ?CarbonInterface, canceled_at: ?CarbonInterface, ended_at: ?CarbonInterface}
     */
    public function cancelAtPeriodEnd(string $stripeSubscriptionId): array;

    /**
     * Cancel the subscription now; Stripe stops retrying open invoices.
     *
     * @return array{id: ?string, status: SubscriptionStatus, price_id: ?string, cancel_at_period_end: bool, period_start: ?CarbonInterface, period_end: ?CarbonInterface, canceled_at: ?CarbonInterface, ended_at: ?CarbonInterface}
     *
     * @throws StripeResourceMissingException when Stripe no longer knows the subscription
     */
    public function cancelImmediately(string $stripeSubscriptionId): array;

    /**
     * Undo a scheduled cancellation.
     *
     * @return array{id: ?string, status: SubscriptionStatus, price_id: ?string, cancel_at_period_end: bool, period_start: ?CarbonInterface, period_end: ?CarbonInterface, canceled_at: ?CarbonInterface, ended_at: ?CarbonInterface}
     */
    public function resumeSubscription(string $stripeSubscriptionId): array;

    /**
     * Construct and verify a webhook event from the raw payload/signature.
     */
    public function constructWebhookEvent(string $payload, ?string $signature, string $secret): Event;
}
