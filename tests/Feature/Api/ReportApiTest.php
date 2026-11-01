<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

/*
 * /api/v1/reports/summary: only the caller's own transactions.
 */

function reportApiRow(User $user, string $type, float $amount): Transaction
{
    return Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => Wallet::factory()->for($user)->create()->id,
        'type' => $type,
        'status' => 'paid',
        'amount' => $amount,
        'date' => '2026-04-15',
        'is_recurring' => false,
    ]);
}

it('summarizes only the user\'s own transactions', function () {
    $user = proUser();
    reportApiRow($user, 'income', 1000);
    reportApiRow($user, 'expense', 300);
    reportApiRow(proUser(), 'income', 99999);

    $this->withToken(apiToken($user, ['reports:read']))
        ->getJson('/api/v1/reports/summary?start_date=2026-04-01&end_date=2026-04-30')
        ->assertOk()
        ->assertJsonPath('data.startDate', '2026-04-01')
        ->assertJsonPath('data.endDate', '2026-04-30')
        ->assertJsonPath('data.totalIncome', 1000)
        ->assertJsonPath('data.totalExpenses', 300)
        ->assertJsonPath('data.balance', 700)
        ->assertJsonPath('data.transactionCount', 2)
        ->assertJsonStructure(['data' => ['fixedVsVariable', 'statusBreakdown', 'expenseByCategory']]);
});

it('validates the period in pt-BR', function () {
    $this->withToken(apiToken(proUser(), ['reports:read']))
        ->getJson('/api/v1/reports/summary?start_date=2026-04-30&end_date=2026-04-01')
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.end_date.0', 'A data final deve ser igual ou posterior à data inicial.');
});
