<?php

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/*
 * The recurring command never pushes a Free user past the monthly cap of
 * 100 transactions; Pro users keep getting every occurrence.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-03-10');
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * 98 one-off March transactions plus a daily boleto series starting on
 * 2026-03-08: 99 March transactions before the command runs.
 */
function userWithDailySeries(User $user): Transaction
{
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 5000]);
    $base = ['user_id' => $user->id, 'wallet_id' => $wallet->id, 'type' => 'expense', 'status' => 'paid'];

    Transaction::factory()->count(98)->create(array_merge($base, [
        'is_recurring' => false,
        'date' => '2026-03-05',
    ]));

    return Transaction::factory()->create(array_merge($base, [
        'description' => 'Academia',
        'amount' => 10,
        'date' => '2026-03-08',
        'is_recurring' => true,
        'recurring_frequency' => 'daily',
        'payment_method' => 'boleto',
    ]));
}

function marchCount(User $user): int
{
    return Transaction::withoutGlobalScopes()
        ->where('user_id', $user->id)
        ->whereNull('deleted_at')
        ->whereYear('date', 2026)
        ->whereMonth('date', 3)
        ->count();
}

it('stops generating occurrences at the Free monthly cap', function () {
    Log::spy();
    $user = User::factory()->create();
    $source = userWithDailySeries($user);

    $this->artisan('transactions:process-recurring')->assertSuccessful();

    expect(marchCount($user))->toBe(100)
        ->and(Transaction::where('recurring_source_id', $source->id)->pluck('date')->map->toDateString()->all())
        ->toBe(['2026-03-09']);

    Log::shouldHaveReceived('info')
        ->with('Recurring transaction skipped: monthly plan limit reached', Mockery::type('array'))
        ->atLeast()->once();
});

it('generates every occurrence for a Pro user', function () {
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create(['current_period_end' => now()->addMonth()]);
    $source = userWithDailySeries($user->fresh());

    $this->artisan('transactions:process-recurring')->assertSuccessful();

    $dates = Transaction::where('recurring_source_id', $source->id)->orderBy('date')->pluck('date')
        ->map->toDateString()->all();

    expect($dates)->toHaveCount(9)
        ->and($dates[0])->toBe('2026-03-09')
        ->and(end($dates))->toBe('2026-03-17')
        ->and(marchCount($user))->toBe(108);
});
