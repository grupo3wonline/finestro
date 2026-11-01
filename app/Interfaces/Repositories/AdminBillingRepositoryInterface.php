<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\PaymentHistory;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cross-user billing reads for the admin panel (subscriptions and payments).
 */
interface AdminBillingRepositoryInterface
{
    /**
     * @return Builder<Subscription>
     */
    public function subscriptionsQuery(): Builder;

    /**
     * @return Builder<PaymentHistory>
     */
    public function paymentsQuery(): Builder;

    /**
     * Number of subscriptions per stored status value.
     *
     * @return array<string, int>
     */
    public function countSubscriptionsByStatus(): array;

    /**
     * Subscriptions that are billed monthly right now: Professional plan,
     * status active, period end missing or in the future.
     */
    public function countMrrSubscriptions(): int;

    /**
     * Paid payments with paid_at inside the month of $month.
     *
     * @return array{count: int, total: float}
     */
    public function paymentsInMonth(CarbonInterface $month): array;
}
