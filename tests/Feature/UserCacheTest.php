<?php

use App\Interfaces\Services\UserCacheInterface;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Factories\SubscriptionFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function userCacheUser(bool $pro = false): User
{
    $user = User::factory()->create(['terms_accepted_at' => now()]);

    if ($pro) {
        SubscriptionFactory::new()->for($user)->create(['current_period_end' => now()->addMonth()]);
    }

    return $user->fresh();
}

function userCacheIncome(User $user, Wallet $wallet, float $amount, string $description = 'Receita'): Transaction
{
    return Transaction::factory()->create([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'status' => 'paid',
        'amount' => $amount,
        'description' => $description,
        'date' => now()->toDateString(),
        'is_recurring' => false,
    ]);
}

/** Insert without model events, so the cache is not invalidated. */
function userCacheRawIncome(User $user, Wallet $wallet, float $amount): void
{
    DB::table('transactions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'status' => 'paid',
        'amount' => $amount,
        'description' => 'Linha inserida sem eventos',
        'date' => now()->toDateString(),
        'is_recurring' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function userCacheVersion(User $user): mixed
{
    return Cache::get('finestro:user:'.$user->id.':version');
}

beforeEach(function (): void {
    $this->a = userCacheUser(pro: true);
    $this->b = userCacheUser(pro: true);
    $this->walletA = Wallet::factory()->for($this->a)->create(['type' => 'checking', 'balance' => 0]);
    $this->walletB = Wallet::factory()->for($this->b)->create(['type' => 'checking', 'balance' => 0]);

    $this->incomeA = userCacheIncome($this->a, $this->walletA, 1000);
    userCacheIncome($this->b, $this->walletB, 2000);
});

it('keeps each user dashboard summary isolated', function (): void {
    $this->actingAs($this->a)->get(route('dashboard'))->assertOk()->assertViewHas('totalIncome', 1000.0);
    $this->actingAs($this->b)->get(route('dashboard'))->assertOk()->assertViewHas('totalIncome', 2000.0);
    $this->actingAs($this->a)->get(route('dashboard'))->assertOk()->assertViewHas('totalIncome', 1000.0);
});

it('serves the cached dashboard summary until a transaction changes through Eloquent', function (): void {
    $this->actingAs($this->a)->get(route('dashboard'))->assertViewHas('totalIncome', 1000.0);

    // Raw insert fires no model event: the cached total is still served.
    userCacheRawIncome($this->a, $this->walletA, 500);
    $this->actingAs($this->a)->get(route('dashboard'))->assertViewHas('totalIncome', 1000.0);

    // Eloquent create invalidates (raw row + new row now counted).
    userCacheIncome($this->a, $this->walletA, 250);
    $this->actingAs($this->a)->get(route('dashboard'))->assertViewHas('totalIncome', 1750.0);

    // Update.
    $this->incomeA->update(['amount' => 1100]);
    $this->actingAs($this->a)->get(route('dashboard'))->assertViewHas('totalIncome', 1850.0);

    // Delete (soft delete fires `deleted`).
    $this->incomeA->delete();
    $this->actingAs($this->a)->get(route('dashboard'))->assertViewHas('totalIncome', 750.0);

    // Restore.
    Transaction::withTrashed()->findOrFail($this->incomeA->id)->restore();
    $this->actingAs($this->a)->get(route('dashboard'))->assertViewHas('totalIncome', 1850.0);

    // B is never affected by A's cache.
    $this->actingAs($this->b)->get(route('dashboard'))->assertViewHas('totalIncome', 2000.0);
});

it('bumps the user cache version on wallet, goal and budget changes', function (): void {
    $changes = [
        'wallet created' => fn () => Wallet::factory()->for($this->a)->create(),
        'wallet updated' => fn () => $this->walletA->update(['name' => 'Renomeada']),
        'wallet incremented' => fn () => $this->walletA->increment('balance', 10),
        'goal created' => fn () => $this->goal = Goal::factory()->for($this->a)->create(),
        'goal updated' => fn () => $this->goal->update(['name' => 'Viagem 2']),
        'goal deleted' => fn () => $this->goal->delete(),
        'budget created' => fn () => $this->budget = Budget::factory()->for($this->a)->create(),
        'budget updated' => fn () => $this->budget->update(['amount' => 321]),
        'budget deleted' => fn () => $this->budget->delete(),
        'wallet deleted' => fn () => $this->walletA->delete(),
    ];

    foreach ($changes as $label => $change) {
        $versionB = userCacheVersion($this->b);
        $before = userCacheVersion($this->a);

        $change();

        expect(userCacheVersion($this->a))->not->toBe($before, $label)
            ->and(userCacheVersion($this->b))->toBe($versionB, $label);
    }
});

it('caches the report summary per user and invalidates it on transaction changes', function (): void {
    $this->actingAs($this->a)->get(route('reports.index'))->assertOk()->assertViewHas('totalIncome', 1000.0);
    $this->actingAs($this->b)->get(route('reports.index'))->assertOk()->assertViewHas('totalIncome', 2000.0);

    userCacheRawIncome($this->a, $this->walletA, 500);
    $this->actingAs($this->a)->get(route('reports.index'))->assertViewHas('totalIncome', 1000.0);

    $this->incomeA->update(['amount' => 1200]);
    $this->actingAs($this->a)->get(route('reports.index'))->assertViewHas('totalIncome', 1700.0);
    $this->actingAs($this->b)->get(route('reports.index'))->assertViewHas('totalIncome', 2000.0);
});

it('uses different keys for different users with the same params', function (): void {
    $cache = app(UserCacheInterface::class);

    expect($cache->remember('user-a', 'demo', ['x' => 1], fn (): int => 1))->toBe(1)
        ->and($cache->remember('user-b', 'demo', ['x' => 1], fn (): int => 2))->toBe(2)
        ->and($cache->remember('user-a', 'demo', ['x' => 1], fn (): int => 99))->toBe(1)
        ->and($cache->remember('user-b', 'demo', ['x' => 1], fn (): int => 99))->toBe(2);

    // Param order does not matter.
    expect($cache->remember('user-a', 'demo2', ['a' => 1, 'b' => 2], fn (): int => 5))->toBe(5)
        ->and($cache->remember('user-a', 'demo2', ['b' => 2, 'a' => 1], fn (): int => 6))->toBe(5);

    // Flushing A leaves B cached.
    $cache->flush('user-a');
    expect($cache->remember('user-a', 'demo', ['x' => 1], fn (): int => 3))->toBe(3)
        ->and($cache->remember('user-b', 'demo', ['x' => 1], fn (): int => 99))->toBe(2);
});

it('does not cache without a user', function (): void {
    $cache = app(UserCacheInterface::class);
    $calls = 0;

    $cache->remember(null, 'demo', [], function () use (&$calls): int {
        return ++$calls;
    });
    $cache->remember(null, 'demo', [], function () use (&$calls): int {
        return ++$calls;
    });

    expect($calls)->toBe(2);
});
