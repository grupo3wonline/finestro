<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Exceptions\CheckoutSessionNotFoundException;
use App\Exceptions\SubscriptionActionException;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Stripe\Event;

interface SubscriptionServiceInterface
{
    /**
     * Get the latest subscription for the user.
     */
    public function getSubscriptionForUser(User $user): ?Subscription;

    /**
     * Paginated payment history of the user (one row per Stripe invoice).
     *
     * @return LengthAwarePaginator<int, PaymentHistory>
     */
    public function getPaymentHistory(User $user, int $perPage = 10): LengthAwarePaginator;

    /**
     * Monthly amount the subscriber is actually charged: the latest paid
     * invoice of the subscription, or the configured price when none exists.
     */
    public function chargedPrice(?Subscription $subscription): float;

    /**
     * Start a Stripe Checkout session and return the redirect URL.
     *
     * @throws SubscriptionActionException when the user is already Professional
     * @throws \RuntimeException when the plan price is not configured
     */
    public function startCheckout(User $user): string;

    /**
     * Reconcile a successful checkout owned by the user.
     *
     * @throws CheckoutSessionNotFoundException when the session is unknown, not complete or not the user's
     */
    public function handleCheckoutSuccess(User $user, string $sessionId): Subscription;

    /**
     * Schedule cancellation at period end; access is kept until then.
     *
     * @throws SubscriptionActionException
     */
    public function cancelAtPeriodEnd(User $user): Subscription;

    /**
     * Cancel the user's subscription. past_due/unpaid are canceled immediately
     * because there is no paid period to keep; otherwise cancellation is
     * scheduled at period end.
     *
     * @throws SubscriptionActionException
     */
    public function cancel(User $user): Subscription;

    /**
     * Cancel the subscription in Stripe now and end local access.
     * Gateway errors propagate.
     *
     * The caller MUST ensure the subscription belongs to the authenticated
     * user: this method operates on the given entity and performs no user_id
     * scoping, so passing another user's subscription would cancel it (IDOR).
     *
     * @throws SubscriptionActionException when there is no Stripe subscription
     */
    public function cancelImmediately(Subscription $subscription): Subscription;

    /**
     * End local access without calling Stripe, for a subscription Stripe no
     * longer knows. Same ownership caveat as cancelImmediately().
     */
    public function markCanceledLocally(Subscription $subscription): Subscription;

    /**
     * Undo a scheduled cancellation.
     *
     * @throws SubscriptionActionException
     */
    public function resume(User $user): Subscription;

    /**
     * Process a verified Stripe webhook event idempotently.
     * Returns false when the event id was already processed.
     */
    public function handleWebhookEvent(Event $event): bool;
}
