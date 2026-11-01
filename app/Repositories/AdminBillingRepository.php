<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\SubscriptionStatus;
use App\Interfaces\Repositories\AdminBillingRepositoryInterface;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminBillingRepository implements AdminBillingRepositoryInterface
{
    public function subscriptionsQuery(): Builder
    {
        return Subscription::query()->with(['user' => fn (BelongsTo $query) => $query->withTrashed()]);
    }

    public function paymentsQuery(): Builder
    {
        return PaymentHistory::query()->with(['user' => fn (BelongsTo $query) => $query->withTrashed()]);
    }

    public function countSubscriptionsByStatus(): array
    {
        $counts = [];

        Subscription::query()
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get()
            ->each(function (object $row) use (&$counts): void {
                $counts[(string) $row->status] = (int) $row->aggregate;
            });

        return $counts;
    }

    public function countMrrSubscriptions(): int
    {
        return Subscription::query()
            ->where('plan', 'professional')
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where(fn (Builder $period) => $period
                ->whereNull('current_period_end')
                ->orWhere('current_period_end', '>', now()))
            ->count();
    }

    public function paymentsInMonth(CarbonInterface $month): array
    {
        $row = PaymentHistory::query()
            ->toBase()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('COUNT(*) as aggregate, COALESCE(SUM(amount), 0) as total')
            ->first();

        return [
            'count' => (int) ($row->aggregate ?? 0),
            'total' => round((float) ($row->total ?? 0), 2),
        ];
    }
}
