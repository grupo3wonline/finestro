<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Transaction search (Laravel Scout, database engine) on the web index and
 * GET /api/v1/transactions?search=: only the current user's rows, matching
 * description, notes, category name and wallet name; LIKE wildcards are
 * literal; soft-deleted rows never appear.
 */

function searchRow(User $user, Wallet $wallet, array $attributes = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => null,
        'type' => 'expense',
        'status' => 'paid',
        'amount' => 100,
        'date' => now()->toDateString(),
        'is_recurring' => false,
        'notes' => null,
    ], $attributes));
}

beforeEach(function (): void {
    $this->alice = proUser();
    $this->bob = proUser();
    $this->aliceWallet = Wallet::factory()->for($this->alice)->create(['name' => 'Conta Alfa', 'type' => 'checking']);
    $this->bobWallet = Wallet::factory()->for($this->bob)->create(['name' => 'Conta Beta', 'type' => 'checking']);

    $this->aliceMarket = searchRow($this->alice, $this->aliceWallet, ['description' => 'Mercado Central Alice']);
    $this->bobMarket = searchRow($this->bob, $this->bobWallet, ['description' => 'Mercado Central Bob']);
    $this->aliceOther = searchRow($this->alice, $this->aliceWallet, ['description' => 'Padaria Esquina']);
});

it('returns only the current user matches on the web index and counts only them in the summary', function (): void {
    $this->actingAs($this->alice)
        ->get(route('transactions.index', ['search' => 'mercado']))
        ->assertOk()
        ->assertSee('Mercado Central Alice')
        ->assertDontSee('Mercado Central Bob')
        ->assertDontSee('Padaria Esquina')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1);

    $this->actingAs($this->bob)
        ->get(route('transactions.index', ['search' => 'mercado']))
        ->assertOk()
        ->assertSee('Mercado Central Bob')
        ->assertDontSee('Mercado Central Alice');
});

it('matches notes, category name and wallet name', function (): void {
    $category = Category::factory()->for($this->alice)->create(['name' => 'Farmacia Saude', 'type' => 'expense']);
    searchRow($this->alice, $this->aliceWallet, ['description' => 'Remedio', 'category_id' => $category->id]);
    searchRow($this->alice, $this->aliceWallet, ['description' => 'Presente', 'notes' => 'aniversario da Clara']);
    $savings = Wallet::factory()->for($this->alice)->create(['name' => 'Poupanca Viagem', 'type' => 'savings']);
    searchRow($this->alice, $savings, ['description' => 'Deposito']);

    $this->actingAs($this->alice);

    $this->get(route('transactions.index', ['search' => 'farmacia']))
        ->assertOk()->assertSee('Remedio')->assertDontSee('Padaria Esquina');
    $this->get(route('transactions.index', ['search' => 'clara']))
        ->assertOk()->assertSee('Presente')->assertDontSee('Padaria Esquina');
    $this->get(route('transactions.index', ['search' => 'viagem']))
        ->assertOk()->assertSee('Deposito')->assertDontSee('Padaria Esquina');
});

it('treats % and _ literally instead of matching every row', function (string $term): void {
    $this->actingAs($this->alice)
        ->get(route('transactions.index', ['search' => $term]))
        ->assertOk()
        ->assertDontSee('Mercado Central Alice')
        ->assertDontSee('Padaria Esquina');
})->with(['%', '_', '%%']);

it('never returns soft-deleted transactions', function (): void {
    $this->aliceMarket->delete();

    $this->actingAs($this->alice)
        ->get(route('transactions.index', ['search' => 'mercado']))
        ->assertOk()
        ->assertDontSee('Mercado Central Alice')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 0);
});

it('combines search with type and month filters', function (): void {
    searchRow($this->alice, $this->aliceWallet, ['description' => 'Mercado Reembolso', 'type' => 'income']);
    searchRow($this->alice, $this->aliceWallet, [
        'description' => 'Mercado Antigo',
        'date' => now()->subMonthsNoOverflow(2)->toDateString(),
    ]);

    $this->actingAs($this->alice)
        ->get(route('transactions.index', ['search' => 'mercado', 'type' => 'expense']))
        ->assertOk()
        ->assertSee('Mercado Central Alice')
        ->assertDontSee('Mercado Reembolso')
        ->assertDontSee('Mercado Antigo');

    $old = now()->subMonthsNoOverflow(2);
    $this->get(route('transactions.index', ['search' => 'mercado', 'year' => $old->year, 'month' => $old->month]))
        ->assertOk()
        ->assertSee('Mercado Antigo')
        ->assertDontSee('Mercado Central Alice');
});

/*
 * Regression: the month filter must match single-digit months passed as an
 * unpadded string (e.g. '3', not '03'), exactly as the controller sends them
 * from the request. Time is frozen to March so the test is deterministic and
 * cannot pass by luck in a two-digit month such as October.
 */
it('matches a single-digit month passed as an unpadded string', function (): void {
    $this->travelTo('2026-03-15 12:00:00');

    $march = searchRow($this->alice, $this->aliceWallet, [
        'description' => 'Mercado de Marco',
        'date' => '2026-03-10',
    ]);
    searchRow($this->alice, $this->aliceWallet, [
        'description' => 'Mercado de Fevereiro',
        'date' => '2026-02-10',
    ]);

    $this->actingAs($this->alice)
        ->get(route('transactions.index', ['search' => 'mercado', 'year' => '2026', 'month' => '3']))
        ->assertOk()
        ->assertSee('Mercado de Marco')
        ->assertDontSee('Mercado de Fevereiro')
        ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1);

    expect($march->refresh()->date->month)->toBe(3);
});

/** Bulk-inserts matching rows (raw insert keeps the 1 500-row setup fast). */
function bulkSearchRows(User $user, Wallet $wallet, int $count, float $amount): void
{
    $now = now();
    $rows = [];
    foreach (range(1, $count) as $i) {
        $rows[] = [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'status' => 'paid',
            'amount' => $amount,
            'description' => "Lote Volumoso {$i}",
            'date' => $now->toDateString(),
            'is_recurring' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 100) as $chunk) {
        DB::table('transactions')->insert($chunk);
    }
}

it('searches 1 500 matches through a subquery, scoped to the user, on web and API', function (): void {
    bulkSearchRows($this->alice, $this->aliceWallet, 1500, 2);
    bulkSearchRows($this->bob, $this->bobWallet, 10, 1000);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($this->alice)
        ->get(route('transactions.index', ['search' => 'volumoso']))
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1500
            && abs($summary['expense_paid'] - 3000.0) < 0.001);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect(collect($queries)->max(fn (array $q): int => count($q['bindings'])))->toBeLessThanOrEqual(50)
        ->and(collect($queries)->contains(fn (array $q): bool => str_contains(strtolower($q['query']), 'in (select')))->toBeTrue();

    $this->app['auth']->forgetGuards();

    $this->withToken(apiToken($this->alice, ['transactions:read']))
        ->getJson('/api/v1/transactions?search=volumoso')
        ->assertOk()
        ->assertJsonPath('meta.total', 1500);
});

it('scopes API search results to the token owner', function (): void {
    $aliceIds = collect($this->withToken(apiToken($this->alice, ['transactions:read']))
        ->getJson('/api/v1/transactions?search=mercado')
        ->assertOk()
        ->json('data'))->pluck('id')->all();

    expect($aliceIds)->toBe([$this->aliceMarket->id]);

    $this->app['auth']->forgetGuards();

    $bobIds = collect($this->withToken(apiToken($this->bob, ['transactions:read']))
        ->getJson('/api/v1/transactions?search=mercado')
        ->assertOk()
        ->json('data'))->pluck('id')->all();

    expect($bobIds)->toBe([$this->bobMarket->id]);
});
