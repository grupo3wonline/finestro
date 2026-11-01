<?php

namespace App\Services;

use App\Exceptions\CheckoutSessionNotFoundException;
use App\Exceptions\StripeResourceMissingException;
use App\Interfaces\Services\StripeGatewayInterface;
use Stripe\Event;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Thin wrapper around the Stripe PHP SDK. Keeps the SDK isolated behind an
 * interface so services stay testable and free of vendor coupling.
 */
class StripeGateway implements StripeGatewayInterface
{
    private ?StripeClient $client = null;

    /**
     * Lazily create the client: `new StripeClient('')` throws, and webhook
     * verification never needs it.
     */
    private function client(): StripeClient
    {
        if (! $this->client instanceof StripeClient) {
            $secretKey = (string) config('stripe.secret_key');

            if ($secretKey === '') {
                throw new \RuntimeException('Stripe secret key not configured.');
            }

            $this->client = new StripeClient($secretKey);
        }

        return $this->client;
    }

    public function ensureCustomer(?string $existingCustomerId, string $email, string $name, string $userId): string
    {
        if ($existingCustomerId) {
            return $existingCustomerId;
        }

        $customer = $this->client()->customers->create([
            'email' => $email,
            'name' => $name,
            'metadata' => ['user_id' => $userId],
        ]);

        return $customer->id;
    }

    public function createCheckoutSession(string $customerId, string $priceId, string $userId, string $successUrl, string $cancelUrl): string
    {
        $session = $this->client()->checkout->sessions->create([
            'customer' => $customerId,
            'client_reference_id' => $userId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['user_id' => $userId],
            'subscription_data' => [
                'metadata' => ['user_id' => $userId],
            ],
        ]);

        return (string) $session->url;
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        try {
            $session = $this->client()->checkout->sessions->retrieve(
                $sessionId,
                ['expand' => ['subscription', 'invoice']]
            );
        } catch (InvalidRequestException) {
            throw new CheckoutSessionNotFoundException;
        }

        $subscription = $session->subscription ?? null;

        if (is_string($subscription)) {
            $subscription = $this->client()->subscriptions->retrieve($subscription, []);
        }

        $subscriptionData = is_object($subscription)
            ? StripePayloadMapper::subscriptionData($subscription)
            : null;

        $invoice = $session->invoice ?? null;

        if (is_string($invoice)) {
            $invoice = $this->client()->invoices->retrieve($invoice, []);
        }

        return [
            'id' => (string) $session->id,
            'status' => isset($session->status) ? (string) $session->status : null,
            'client_reference_id' => isset($session->client_reference_id) ? (string) $session->client_reference_id : null,
            'metadata_user_id' => isset($session->metadata->user_id) ? (string) $session->metadata->user_id : null,
            'customer_id' => StripePayloadMapper::id($session->customer ?? null),
            'subscription_id' => $subscriptionData['id'] ?? null,
            'subscription_status' => is_object($subscription) ? ($subscription->status ?? null) : null,
            'cancel_at_period_end' => $subscriptionData['cancel_at_period_end'] ?? false,
            'period_start' => $subscriptionData['period_start'] ?? null,
            'period_end' => $subscriptionData['period_end'] ?? null,
            'ended_at' => $subscriptionData['ended_at'] ?? null,
            'price_id' => $subscriptionData['price_id'] ?? null,
            'invoice' => is_object($invoice) ? StripePayloadMapper::invoiceData($invoice) : null,
        ];
    }

    public function cancelAtPeriodEnd(string $stripeSubscriptionId): array
    {
        $subscription = $this->client()->subscriptions->update($stripeSubscriptionId, [
            'cancel_at_period_end' => true,
        ]);

        return StripePayloadMapper::subscriptionData($subscription);
    }

    public function cancelImmediately(string $stripeSubscriptionId): array
    {
        try {
            $subscription = $this->client()->subscriptions->cancel($stripeSubscriptionId);
        } catch (InvalidRequestException $e) {
            if (self::isResourceMissing($e)) {
                throw new StripeResourceMissingException('Stripe resource missing', 0, $e);
            }

            throw $e;
        }

        return StripePayloadMapper::subscriptionData($subscription);
    }

    /**
     * Whether the error means Stripe does not know the requested resource.
     */
    public static function isResourceMissing(\Throwable $e): bool
    {
        if (self::isModeMismatch($e)) {
            return false;
        }

        return $e instanceof InvalidRequestException
            && ($e->getStripeCode() === 'resource_missing' || $e->getHttpStatus() === 404);
    }

    /**
     * Whether the resource exists in the other Stripe mode (live vs test key).
     * That is a configuration error, not a missing resource.
     */
    public static function isModeMismatch(\Throwable $e): bool
    {
        return $e instanceof InvalidRequestException
            && str_contains(strtolower($e->getMessage()), 'a similar object exists in');
    }

    public function resumeSubscription(string $stripeSubscriptionId): array
    {
        $subscription = $this->client()->subscriptions->update($stripeSubscriptionId, [
            'cancel_at_period_end' => false,
        ]);

        return StripePayloadMapper::subscriptionData($subscription);
    }

    public function constructWebhookEvent(string $payload, ?string $signature, string $secret): Event
    {
        return Webhook::constructEvent($payload, (string) $signature, $secret);
    }
}
