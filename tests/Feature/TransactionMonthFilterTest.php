<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

/*
 * Regression: Transaction::scopeInMonth() must include the last day of the
 * month. The old whereBetween('date', ['Y-m-01', 'Y-m-t']) dropped rows stored
 * as 'Y-m-t 00:00:00' on SQLite, so on the last day of a month "today's"
 * transactions vanished from the month list and its summary. Time is pinned to
 * the last day of the month so the test cannot pass by luck on another day.
 */

function monthEndRow(User $user, Wallet $wallet, string $date, string $description): Transaction
{
    return Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => null,
        'type' => 'expense',
        'status' => 'paid',
        'amount' => 50,
        'description' => $description,
        'date' => $date,
        'is_recurring' => false,
        'notes' => null,
    ]);
}

it('includes the last day of the month in the month filter and the summary', function (string $today, string $previous, string $next): void {
    $this->travelTo("{$today} 12:00:00");

    $user = proUser();
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking']);
    $lastDay = monthEndRow($user, $wallet, now()->toDateString(), 'Compra Ultimo Dia');
    monthEndRow($user, $wallet, $previous, 'Compra Mes Anterior');
    monthEndRow($user, $wallet, $next, 'Compra Mes Seguinte');

    $this->actingAs($user);

    expect(Transaction::query()->inMonth(now()->year, now()->month)->pluck('id')->all())
        ->toBe([$lastDay->id]);

    // Default period (current month) and the explicit year/month filter.
    foreach ([[], ['year' => now()->year, 'month' => now()->month]] as $params) {
        $this->get(route('transactions.index', $params))
            ->assertOk()
            ->assertSee('Compra Ultimo Dia')
            ->assertDontSee('Compra Mes Anterior')
            ->assertDontSee('Compra Mes Seguinte')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1
                && abs($summary['expense_paid'] - 50.0) < 0.001);
    }
})->with([
    '31-day month' => ['2026-10-31', '2026-09-30', '2026-11-01'],
    'February' => ['2026-02-28', '2026-01-31', '2026-03-01'],
    'leap February' => ['2028-02-29', '2028-01-31', '2028-03-01'],
    'year end' => ['2026-12-31', '2026-11-30', '2027-01-01'],
]);
