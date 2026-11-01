<?php

namespace App\Services;

use App\DTOs\SubscriptionDTO;
use App\Enums\SubscriptionStatus;
use App\Exceptions\CheckoutSessionNotFoundException;
use App\Exceptions\SubscriptionActionException;
use App\Interfaces\Repositories\PaymentHistoryRepositoryInterface;
use App\Interfaces\Repositories\StripeWebhookEventRepositoryInterface;
use App\Interfaces\Repositories\SubscriptionRepositoryInterface;
use App\Interfaces\Services\PlanPricingInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        protected StripeGatewayInterface $gateway,
        protected SubscriptionRepositoryInterface $subscriptions,
        protected PaymentHistoryRepositoryInterface $payments,
        protected PlanPricingInterface $pricing,
        protected StripeWebhookEventRepositoryInterface $webhookEvents,
    ) {}

    public function getSubscriptionForUser(User $user): ?Subscription
    {
        return $this->subscriptions->findLatestForUser($user->id);
    }

    public function getPaymentHistory(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->payments->paginateForUser($user->id, $perPage);
    }

    public function chargedPrice(?Subscription $subscription): float
    {
        $latest = $subscription instanceof Subscription
            ? $this->payments->latestPaidForSubscription($subscription->user_id, $subscription->id)
            : null;

        return $latest instanceof PaymentHistory ? (float) $latest->amount : $this->pricing->currentPrice();
    }

    public function startCheckout(User $user): string
    {
        if ($user->isProfessional()) {
            throw new SubscriptionActionException('Você já é assinante do plano Profissional.');
        }

        $priceId = $this->pricing->checkoutPriceId();

        if (! $priceId) {
            throw new \RuntimeException('Plano não configurado.');
        }

        $existing = $this->subscriptions->findLatestForUser($user->id);

        $customerId = $this->gateway->ensureCustomer(
            $existing?->stripe_customer_id,
            $user->email,
            $user->name,
            $user->id,
        );

        return $this->gateway->createCheckoutSession(
            $customerId,
            $priceId,
            $user->id,
            route('subscription.success').'?session_id={CHECKOUT_SESSION_ID}',
            route('subscription.plans'),
        );
    }

    public function handleCheckoutSuccess(User $user, string $sessionId): Subscription
    {
        $data = $this->gateway->retrieveCheckoutSession($sessionId);

        if (! $this->sessionBelongsTo($data, (string) $user->id)) {
            throw new CheckoutSessionNotFoundException;
        }

        return $this->activateFromCheckout((string) $user->id, $data);
    }

    public function cancelAtPeriodEnd(User $user): Subscription
    {
        $subscription = $this->subscriptions->findLatestForUser($user->id);

        if (! $subscription instanceof Subscription
            || ! $subscription->stripe_subscription_id
            || ! $subscription->isProfessional()
            || $subscription->status === SubscriptionStatus::CANCELED) {
            throw new SubscriptionActionException('Você não possui uma assinatura Profissional ativa para cancelar.');
        }

        if ($subscription->hasPendingCancellation()) {
            throw new SubscriptionActionException('O cancelamento da sua assinatura já está agendado.');
        }

        $remote = $this->gateway->cancelAtPeriodEnd($subscription->stripe_subscription_id);

        return $this->subscriptions->updateAttributes($subscription, array_merge(
            $this->remoteAttributes($remote),
            ['cancel_at_period_end' => true, 'canceled_at' => now()],
        ));
    }

    public function cancel(User $user): Subscription
    {
        $subscription = $this->subscriptions->findLatestForUser($user->id);

        if ($subscription?->stripe_subscription_id && $subscription->isPaymentPending()) {
            return $this->cancelImmediately($subscription);
        }

        return $this->cancelAtPeriodEnd($user);
    }

    public function cancelImmediately(Subscription $subscription): Subscription
    {
        if (! $subscription->stripe_subscription_id) {
            throw new SubscriptionActionException('Você não possui uma assinatura para cancelar.');
        }

        $remote = $this->gateway->cancelImmediately($subscription->stripe_subscription_id);

        // Same end-of-access rule as customer.subscription.deleted; the later
        // webhook for this cancellation is idempotent.
        return $this->subscriptions->updateAttributes($subscription, [
            'status' => SubscriptionStatus::CANCELED,
            'canceled_at' => $remote['canceled_at'] ?? now(),
            'cancel_at_period_end' => false,
            'current_period_end' => $remote['ended_at'] ?? now(),
        ]);
    }

    public function markCanceledLocally(Subscription $subscription): Subscription
    {
        return $this->subscriptions->updateAttributes($subscription, [
            'status' => SubscriptionStatus::CANCELED,
            'canceled_at' => now(),
            'cancel_at_period_end' => false,
            'current_period_end' => now(),
        ]);
    }

    public function resume(User $user): Subscription
    {
        $subscription = $this->subscriptions->findLatestForUser($user->id);

        if (! $subscription instanceof Subscription
            || ! $subscription->stripe_subscription_id
            || ! $subscription->hasPendingCancellation()) {
            throw new SubscriptionActionException('Não há cancelamento agendado para reverter.');
        }

        $remote = $this->gateway->resumeSubscription($subscription->stripe_subscription_id);

        return $this->subscriptions->updateAttributes($subscription, array_merge(
            $this->remoteAttributes($remote),
            ['cancel_at_period_end' => false, 'canceled_at' => null],
        ));
    }

    public function handleWebhookEvent(Event $event): bool
    {
        return DB::transaction(function () use ($event): bool {
            if (! $this->webhookEvents->markProcessed((string) $event->id, (string) $event->type)) {
                return false;
            }

            $object = $event->data->object ?? null;

            if (! is_object($object)) {
                Log::warning('Stripe webhook without data object', ['id' => $event->id, 'type' => $event->type]);

                return true;
            }

            match ($event->type) {
                'checkout.session.completed' => $this->onCheckoutCompleted($object),
                'invoice.paid' => $this->onInvoicePaid($object),
                'invoice.payment_failed' => $this->onInvoicePaymentFailed($object),
                'customer.subscription.updated' => $this->onSubscriptionUpdated($object),
                'customer.subscription.deleted' => $this->onSubscriptionDeleted($object),
                default => null,
            };

            return true;
        });
    }

    /**
     * Ownership rules for a checkout session (complete, has a subscription,
     * owned by $userId, and same Stripe customer as any local subscription).
     *
     * @param  array<string, mixed>  $data
     */
    private function sessionBelongsTo(array $data, string $userId): bool
    {
        if (($data['status'] ?? null) !== 'complete' || empty($data['subscription_id'])) {
            return false;
        }

        $owner = $data['client_reference_id'] ?? $data['metadata_user_id'] ?? null;

        if (! is_string($owner) || ! hash_equals($userId, $owner)) {
            return false;
        }

        $metadataUserId = $data['metadata_user_id'] ?? null;

        if ($metadataUserId !== null && ! hash_equals($userId, (string) $metadataUserId)) {
            return false;
        }

        $existing = $this->subscriptions->findLatestForUser($userId);

        return ! $existing?->stripe_customer_id || hash_equals($existing->stripe_customer_id, (string) ($data['customer_id'] ?? ''));
    }

    /**
     * Shared by the success page and the checkout.session.completed webhook;
     * idempotent (one subscription row per user, one payment row per invoice).
     *
     * @param  array<string, mixed>  $data  normalized session from the gateway
     */
    private function activateFromCheckout(string $userId, array $data): Subscription
    {
        return DB::transaction(function () use ($userId, $data): Subscription {
            $status = StripePayloadMapper::mapStatus($data['subscription_status'] ?? 'active');
            $ended = $status === SubscriptionStatus::CANCELED;

            if ($ended) {
                $existing = $this->subscriptions->findLatestForUser($userId);

                // An old session of a replaced subscription never overwrites the current one.
                if ($existing instanceof Subscription && $existing->stripe_subscription_id !== $data['subscription_id']) {
                    return $existing;
                }
            }

            $subscription = $this->subscriptions->updateOrCreateForUser(
                $userId,
                SubscriptionDTO::fromArray([
                    'stripe_subscription_id' => $data['subscription_id'],
                    'stripe_customer_id' => $data['customer_id'] ?? null,
                    'stripe_price_id' => $data['price_id'] ?? $this->pricing->checkoutPriceId(),
                    'plan' => 'professional',
                    'status' => $status,
                    'current_period_start' => $data['period_start'] ?? null,
                    // An ended subscription keeps a future period end in Stripe;
                    // use the end date so access stops (same rule as subscription.deleted).
                    'current_period_end' => $ended
                        ? ($data['ended_at'] ?? now())
                        : ($data['period_end'] ?? null),
                    'cancel_at_period_end' => $ended ? false : (bool) ($data['cancel_at_period_end'] ?? false),
                ]),
            );

            $invoice = $data['invoice'] ?? null;

            if (is_array($invoice) && ($invoice['status'] ?? null) === 'paid') {
                $this->recordInvoice($subscription, $invoice);
            }

            return $subscription;
        });
    }

    /**
     * Upsert the payment row of a Stripe invoice.
     *
     * @param  array<string, mixed>  $invoice  StripePayloadMapper::invoiceData shape
     */
    private function recordInvoice(Subscription $subscription, array $invoice, string $status = 'paid'): void
    {
        if (empty($invoice['id'])) {
            return;
        }

        $cents = $status === 'paid' ? ($invoice['amount_paid'] ?? null) : ($invoice['amount_due'] ?? null);

        $this->payments->upsertForInvoice($subscription->user_id, (string) $invoice['id'], [
            'subscription_id' => $subscription->id,
            'amount' => $cents !== null ? $cents / 100 : $this->pricing->currentPrice(),
            'currency' => strtoupper((string) ($invoice['currency'] ?? config('stripe.currency', 'BRL'))),
            'status' => $status,
            'description' => 'Assinatura '.$this->pricing->planName(),
            'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null,
            'invoice_pdf_url' => $invoice['invoice_pdf'] ?? null,
            'paid_at' => $status === 'paid' ? ($invoice['paid_at'] ?? now()) : null,
        ]);
    }

    /**
     * Local attributes from a mapped Stripe subscription (status + period).
     *
     * @param  array<string, mixed>  $remote
     * @return array<string, mixed>
     */
    private function remoteAttributes(array $remote): array
    {
        return array_filter([
            'status' => $remote['status'] ?? null,
            'current_period_start' => $remote['period_start'] ?? null,
            'current_period_end' => $remote['period_end'] ?? null,
        ], fn ($value): bool => ! is_null($value));
    }

    private function onCheckoutCompleted(object $session): void
    {
        $sessionId = StripePayloadMapper::id($session->id ?? null);

        if (! $sessionId) {
            return;
        }

        try {
            $data = $this->gateway->retrieveCheckoutSession($sessionId);
        } catch (CheckoutSessionNotFoundException) {
            Log::info('Stripe checkout.session.completed for unknown session', ['session_id' => $sessionId]);

            return;
        }

        $ownerId = $data['client_reference_id'] ?? $data['metadata_user_id'] ?? null;

        if (! is_string($ownerId)
            || ! User::query()->whereKey($ownerId)->exists()
            || ! $this->sessionBelongsTo($data, $ownerId)) {
            Log::info('Stripe checkout.session.completed without a valid owner', ['session_id' => $sessionId]);

            return;
        }

        $this->activateFromCheckout($ownerId, $data);
    }

    private function onInvoicePaid(object $invoice): void
    {
        $subscription = $this->findForInvoice($invoice);

        if (! $subscription instanceof Subscription) {
            return;
        }

        $data = StripePayloadMapper::invoiceData($invoice);

        $attributes = ['status' => SubscriptionStatus::ACTIVE];

        if ($data['period_end']) {
            $attributes['current_period_start'] = $data['period_start'];
            $attributes['current_period_end'] = $data['period_end'];
        }

        $subscription = $this->subscriptions->updateAttributes($subscription, $attributes);

        $this->recordInvoice($subscription, $data, 'paid');
    }

    private function onInvoicePaymentFailed(object $invoice): void
    {
        $subscription = $this->findForInvoice($invoice);

        if (! $subscription instanceof Subscription) {
            return;
        }

        $subscription = $this->subscriptions->updateAttributes($subscription, ['status' => SubscriptionStatus::PAST_DUE]);

        $this->recordInvoice($subscription, StripePayloadMapper::invoiceData($invoice), 'failed');
    }

    private function onSubscriptionUpdated(object $stripeSubscription): void
    {
        $subscription = $this->findForSubscription($stripeSubscription);

        if (! $subscription instanceof Subscription) {
            return;
        }

        $data = StripePayloadMapper::subscriptionData($stripeSubscription);

        $this->subscriptions->updateAttributes($subscription, array_merge(
            $this->remoteAttributes($data),
            [
                'cancel_at_period_end' => $data['cancel_at_period_end'],
                'canceled_at' => $data['canceled_at'],
            ],
        ));
    }

    private function onSubscriptionDeleted(object $stripeSubscription): void
    {
        $subscription = $this->findForSubscription($stripeSubscription);

        if (! $subscription instanceof Subscription) {
            return;
        }

        $data = StripePayloadMapper::subscriptionData($stripeSubscription);

        // A really-ended subscription loses access immediately.
        $this->subscriptions->updateAttributes($subscription, [
            'status' => SubscriptionStatus::CANCELED,
            'canceled_at' => $data['canceled_at'] ?? now(),
            'cancel_at_period_end' => false,
            'current_period_end' => $data['ended_at'] ?? now(),
        ]);
    }

    private function findForInvoice(object $invoice): ?Subscription
    {
        $stripeId = StripePayloadMapper::invoiceSubscriptionId($invoice);
        $subscription = $stripeId ? $this->subscriptions->findByStripeId($stripeId) : null;

        if (! $subscription instanceof Subscription) {
            Log::info('Stripe invoice for unknown subscription', ['invoice_id' => $invoice->id ?? null]);
        }

        return $subscription;
    }

    private function findForSubscription(object $stripeSubscription): ?Subscription
    {
        $stripeId = StripePayloadMapper::id($stripeSubscription->id ?? null);
        $subscription = $stripeId ? $this->subscriptions->findByStripeId($stripeId) : null;

        if (! $subscription instanceof Subscription) {
            Log::info('Stripe subscription event for unknown subscription', ['subscription_id' => $stripeId]);
        }

        return $subscription;
    }
}
