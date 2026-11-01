<?php

declare(strict_types=1);

use App\Interfaces\Services\AdminMetricsServiceInterface;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| AdminMetricsService numbers and the grantingProfessional scope
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15 12:00:00');
    config(['stripe.plans.professional.price' => 29.90]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * 8 users (1 created 40 days ago), 3 Professional, 6 subscriptions
 * (4 active status, 1 past_due, 1 canceled), 2 billable for MRR,
 * 2 paid payments this month.
 */
function seedAdminMetricsDataset(): void
{
    Subscription::factory()->for(User::factory())->create();
    Subscription::factory()->pendingCancellation()->for(User::factory())->create();
    Subscription::factory()->canceled()->for(User::factory())->create();
    Subscription::factory()->pastDue()->for(User::factory())->create();
    Subscription::factory()->expired()->for(User::factory())->create();
    Subscription::factory()->free()->for(User::factory())->create();
    User::factory()->create();
    User::factory()->create(['created_at' => now()->subDays(40)]);

    $payer = User::query()->firstOrFail();
    PaymentHistory::factory()->for($payer)->create(['amount' => 29.90, 'paid_at' => now()->subDays(2)]);
    PaymentHistory::factory()->for($payer)->create(['amount' => 10.00, 'paid_at' => now()->startOfMonth()]);
    PaymentHistory::factory()->for($payer)->create(['amount' => 50.00, 'paid_at' => now()->subMonth()]);
    PaymentHistory::factory()->for($payer)->failed()->create(['amount' => 29.90]);
    PaymentHistory::factory()->for($payer)->pending()->create(['amount' => 29.90]);
}

it('computes the dashboard numbers for a fixed dataset', function (): void {
    seedAdminMetricsDataset();

    $metrics = app(AdminMetricsServiceInterface::class)->metrics();

    expect($metrics->totalUsers)->toBe(8)
        ->and($metrics->newUsersLast30Days)->toBe(7)
        ->and($metrics->professionalUsers)->toBe(3)
        ->and($metrics->freeUsers)->toBe(5)
        ->and($metrics->activeSubscriptions)->toBe(4)
        ->and($metrics->pastDueSubscriptions)->toBe(1)
        ->and($metrics->canceledSubscriptions)->toBe(1)
        ->and($metrics->paymentsThisMonthCount)->toBe(2)
        ->and($metrics->paymentsThisMonthTotal)->toBe(39.90)
        ->and($metrics->paymentsThisMonthFormatted)->toBe('R$ 39,90')
        ->and($metrics->stripeTestKeyInProduction)->toBeFalse();
});

it('estimates MRR as billable subscriptions times the configured regular price', function (): void {
    seedAdminMetricsDataset();
    config(['stripe.plans.professional.price' => 49.50]);

    $metrics = app(AdminMetricsServiceInterface::class)->metrics();

    expect($metrics->mrrEstimate)->toBe(round(2 * (float) config('stripe.plans.professional.price'), 2))
        ->and($metrics->mrrFormatted)->toBe('R$ 99,00');
});

it('matches Subscription::isProfessional() with the grantingProfessional scope', function (): void {
    Subscription::factory()->create();
    Subscription::factory()->free()->create();
    Subscription::factory()->canceled()->create();
    Subscription::factory()->canceled()->create(['current_period_end' => null]);
    Subscription::factory()->pendingCancellation()->create();
    Subscription::factory()->expired()->create();
    Subscription::factory()->canceled()->expired()->create();
    Subscription::factory()->pastDue()->create();
    Subscription::factory()->create(['status' => 'trialing']);
    Subscription::factory()->create(['status' => 'incomplete']);
    Subscription::factory()->create(['current_period_end' => null]);

    $expected = Subscription::query()->get()
        ->filter(fn (Subscription $subscription): bool => $subscription->isProfessional())
        ->pluck('id')->sort()->values()->all();

    $scoped = Subscription::query()->grantingProfessional()->pluck('id')->sort()->values()->all();

    expect($scoped)->toBe($expected)
        ->and($scoped)->toHaveCount(5);
});
