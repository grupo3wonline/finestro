<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Query budgets: the landing page renders without the database, and the
 * dashboard and transaction list run a constant number of queries no matter
 * how many transactions the user has (no N+1). Budgets = measured + ~20%.
 */

const LANDING_QUERY_BUDGET = 2;
// Measured (SQLite, after warm-up): dashboard 18, transactions.index 14, for both 5 and 25 rows.
const DASHBOARD_QUERY_BUDGET = 22;
const TRANSACTIONS_INDEX_QUERY_BUDGET = 17;

/** A free-plan user with one wallet, one category and $count transactions this month. */
function userWithTransactions(int $count): User
{
    $user = freeUser();
    $wallet = Wallet::factory()->for($user)->create(['type' => 'checking']);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    for ($i = 0; $i < $count; $i++) {
        Transaction::factory()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $i % 2 === 0 ? $category->id : null,
            'type' => $i % 2 === 0 ? 'expense' : 'income',
            'status' => $i % 3 === 0 ? 'pending' : 'paid',
            'amount' => 10 + $i,
            'date' => now()->startOfMonth()->addDays($i % 20)->toDateString(),
            'is_recurring' => false,
            'notes' => null,
        ]);
    }

    return $user;
}

/**
 * One request by an unrelated user first, so app-wide caches loaded once per
 * process (Spatie permission registry) do not skew the first measurement.
 */
function warmUp(TestCase $test, string $route): void
{
    $test->actingAs(userWithTransactions(0))->get(route($route))->assertOk();
}

/** Number of queries the request runs. */
function countQueries(callable $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $request();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('renders the landing page for guests within the query budget', function (): void {
    $queries = countQueries(fn () => $this->get(route('home'))->assertOk());

    if (getenv('QUERY_BUDGET_DUMP')) {
        fwrite(STDERR, "landing: {$queries}\n");
    }

    expect($queries)->toBeLessThanOrEqual(LANDING_QUERY_BUDGET);
});

it('runs a constant number of queries on the dashboard (no N+1)', function (): void {
    $small = userWithTransactions(5);
    $large = userWithTransactions(25);
    warmUp($this, 'dashboard');

    $smallQueries = countQueries(fn () => $this->actingAs($small)->get(route('dashboard'))->assertOk());
    $largeQueries = countQueries(fn () => $this->actingAs($large)->get(route('dashboard'))->assertOk());

    if (getenv('QUERY_BUDGET_DUMP')) {
        fwrite(STDERR, "dashboard: 5 tx = {$smallQueries}, 25 tx = {$largeQueries}\n");
    }

    expect($largeQueries)->toBe($smallQueries)
        ->and($largeQueries)->toBeLessThanOrEqual(DASHBOARD_QUERY_BUDGET);
});

it('runs a constant number of queries on the transaction list (no N+1)', function (): void {
    $small = userWithTransactions(5);
    $large = userWithTransactions(25);
    warmUp($this, 'transactions.index');

    $smallQueries = countQueries(fn () => $this->actingAs($small)->get(route('transactions.index'))->assertOk());
    $largeQueries = countQueries(fn () => $this->actingAs($large)->get(route('transactions.index'))->assertOk());

    if (getenv('QUERY_BUDGET_DUMP')) {
        fwrite(STDERR, "transactions.index: 5 tx = {$smallQueries}, 25 tx = {$largeQueries}\n");
    }

    expect($largeQueries)->toBe($smallQueries)
        ->and($largeQueries)->toBeLessThanOrEqual(TRANSACTIONS_INDEX_QUERY_BUDGET);
});
