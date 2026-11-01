<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;

/**
 * Aggregated numbers for the admin dashboard. Counts only: no per-user
 * financial data.
 */
class AdminMetricsData extends Data
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $newUsersLast30Days,
        public readonly int $freeUsers,
        public readonly int $professionalUsers,
        public readonly int $activeSubscriptions,
        public readonly int $pastDueSubscriptions,
        public readonly int $canceledSubscriptions,
        public readonly float $mrrEstimate,
        public readonly string $mrrFormatted,
        public readonly int $paymentsThisMonthCount,
        public readonly float $paymentsThisMonthTotal,
        public readonly string $paymentsThisMonthFormatted,
        public readonly bool $stripeTestKeyInProduction,
    ) {}
}
