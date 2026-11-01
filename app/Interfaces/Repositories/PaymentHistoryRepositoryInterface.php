<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\PaymentHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentHistoryRepositoryInterface
{
    /**
     * Get the most recent payments for a user (payment history table).
     *
     * @return Collection<int, PaymentHistory>
     */
    public function getRecentForUser(string $userId, int $limit = 10): Collection;

    /**
     * Paginate the user's payments, newest first (paid_at, then created_at).
     *
     * @return LengthAwarePaginator<int, PaymentHistory>
     */
    public function paginateForUser(string $userId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Most recent paid payment of one of the user's subscriptions.
     */
    public function latestPaidForSubscription(string $userId, string $subscriptionId): ?PaymentHistory;

    /**
     * Record a payment entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): PaymentHistory;

    /**
     * Create or update the single row of a Stripe invoice for the user.
     * An existing paid row is never downgraded to failed.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertForInvoice(string $userId, string $invoiceId, array $data): PaymentHistory;
}
