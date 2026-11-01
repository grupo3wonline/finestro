<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

/*
 * Regression: the wallet doughnut chart used to evaluate the JS fallback
 * `palette.series` as PHP when no wallet had a positive balance, crashing
 * the dashboard. These cases must render with HTTP 200.
 */

function dashboardUser(): User
{
    return User::factory()->create(['terms_accepted_at' => now()]);
}

it('renders the dashboard when the user has no wallets', function () {
    $this->actingAs(dashboardUser())
        ->get(route('dashboard'))
        ->assertOk();
});

it('renders the dashboard when no wallet has a positive balance', function () {
    $user = dashboardUser();
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => -150]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('shows the empty wallet chart state when there is income but no positive wallet', function () {
    $user = dashboardUser();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => -150]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'status' => 'paid',
        'amount' => 200,
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Sem saldo positivo para exibir.');
});

it('renders the wallet chart when a wallet has a positive balance and income exists', function () {
    $user = dashboardUser();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 500]);
    Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'status' => 'paid',
        'amount' => 200,
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('chart-wallets', false);
});
