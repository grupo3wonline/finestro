<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;

/*
 * A recurring series whose owner account is soft-deleted is skipped
 * entirely by the recurring command: no new occurrences are created.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-03-10');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('skips series whose owner is soft-deleted', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking', 'balance' => 5000]);

    $source = Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'status' => 'paid',
        'description' => 'Academia',
        'amount' => 10,
        'date' => '2026-03-05',
        'is_recurring' => true,
        'recurring_frequency' => 'daily',
        'payment_method' => 'boleto',
    ]);

    $user->delete();

    // The series itself stays live, so the command reaches it and must skip it.
    expect(Transaction::withoutGlobalScopes()->whereKey($source->id)->whereNull('deleted_at')->exists())->toBeTrue();

    $before = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->count();

    $this->artisan('transactions:process-recurring')->assertSuccessful();

    expect(Transaction::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe($before);
});
