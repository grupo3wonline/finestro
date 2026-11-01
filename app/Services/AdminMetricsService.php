<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AdminMetricsData;
use App\Enums\SubscriptionStatus;
use App\Interfaces\Repositories\AdminBillingRepositoryInterface;
use App\Interfaces\Repositories\AdminUserRepositoryInterface;
use App\Interfaces\Services\AdminMetricsServiceInterface;
use App\Interfaces\Services\PlanPricingInterface;
use App\Interfaces\Services\StripeConfigCheckInterface;

class AdminMetricsService implements AdminMetricsServiceInterface
{
    public function __construct(
        protected AdminUserRepositoryInterface $users,
        protected AdminBillingRepositoryInterface $billing,
        protected PlanPricingInterface $pricing,
        protected StripeConfigCheckInterface $stripeConfig,
    ) {}

    public function metrics(): AdminMetricsData
    {
        $total = $this->users->countAll();
        $professional = $this->users->countProfessional();
        $byStatus = $this->billing->countSubscriptionsByStatus();
        $payments = $this->billing->paymentsInMonth(now());

        // Estimate only: promo prices and trials are not taken into account.
        $mrr = round($this->billing->countMrrSubscriptions() * $this->pricing->regularPrice(), 2);

        return new AdminMetricsData(
            totalUsers: $total,
            newUsersLast30Days: $this->users->countCreatedSince(now()->subDays(30)),
            freeUsers: max(0, $total - $professional),
            professionalUsers: $professional,
            activeSubscriptions: $byStatus[SubscriptionStatus::ACTIVE->value] ?? 0,
            pastDueSubscriptions: $byStatus[SubscriptionStatus::PAST_DUE->value] ?? 0,
            canceledSubscriptions: $byStatus[SubscriptionStatus::CANCELED->value] ?? 0,
            mrrEstimate: $mrr,
            mrrFormatted: $this->pricing->format($mrr),
            paymentsThisMonthCount: $payments['count'],
            paymentsThisMonthTotal: $payments['total'],
            paymentsThisMonthFormatted: $this->pricing->format($payments['total']),
            stripeTestKeyInProduction: $this->stripeConfig->isTestKeyInProduction(),
        );
    }
}
