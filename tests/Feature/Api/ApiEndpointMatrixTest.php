<?php

use App\Enums\ApiTokenAbility;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;

/*
 * Every api.v1.* endpoint answers the same way to: no token (401), a token
 * without the ability (403 missing_ability), a role without the Spatie
 * permission (403 forbidden), a Free user (403 plan_upgrade_required), the
 * rate limit (429) and another user's record (404, record unchanged).
 */

/**
 * route name (without "api.v1.") => [method, record type, ability, permission, body]
 *
 * @return array<string, array{0: string, 1: string|null, 2: string, 3: string|null, 4: array<string, mixed>}>
 */
function apiEndpoints(): array
{
    return [
        'auth.me' => ['GET', null, 'account:read', null, []],
        'subscription.show' => ['GET', null, 'account:read', null, []],

        'wallets.index' => ['GET', null, 'wallets:read', 'wallets.view', []],
        'wallets.store' => ['POST', null, 'wallets:write', 'wallets.create', ['name' => 'Nova', 'type' => 'checking', 'ownership' => 'personal']],
        'wallets.show' => ['GET', 'wallet', 'wallets:read', 'wallets.view', []],
        'wallets.update' => ['PUT', 'wallet', 'wallets:write', 'wallets.update', ['name' => 'Invasão']],
        'wallets.destroy' => ['DELETE', 'wallet', 'wallets:write', 'wallets.delete', []],

        'transactions.index' => ['GET', null, 'transactions:read', 'transactions.view', []],
        'transactions.store' => ['POST', null, 'transactions:write', 'transactions.create', ['type' => 'expense', 'amount' => 10, 'description' => 'X', 'date' => '2026-01-10']],
        'transactions.show' => ['GET', 'transaction', 'transactions:read', 'transactions.view', []],
        'transactions.update' => ['PATCH', 'transaction', 'transactions:write', 'transactions.update', ['description' => 'Invasão']],
        'transactions.destroy' => ['DELETE', 'transaction', 'transactions:write', 'transactions.delete', []],
        'transactions.pay' => ['PATCH', 'transaction', 'transactions:write', 'transactions.update', []],
        'transactions.unpay' => ['PATCH', 'transaction', 'transactions:write', 'transactions.update', []],
        'transactions.receipt' => ['GET', 'transaction', 'transactions:read', 'transactions.view', []],

        'goals.index' => ['GET', null, 'goals:read', 'goals.view', []],
        'goals.store' => ['POST', null, 'goals:write', 'goals.create', ['name' => 'Viagem', 'target_amount' => 1000]],
        'goals.show' => ['GET', 'goal', 'goals:read', 'goals.view', []],
        'goals.update' => ['PUT', 'goal', 'goals:write', 'goals.update', ['name' => 'Invasão']],
        'goals.destroy' => ['DELETE', 'goal', 'goals:write', 'goals.delete', []],

        'budgets.index' => ['GET', null, 'budgets:read', 'budgets.view', []],
        'budgets.store' => ['POST', null, 'budgets:write', 'budgets.create', ['year' => 2026, 'month' => 1, 'amount' => 500]],
        'budgets.update' => ['PUT', 'budget', 'budgets:write', 'budgets.update', ['amount' => 1]],
        'budgets.destroy' => ['DELETE', 'budget', 'budgets:write', 'budgets.delete', []],

        'categories.index' => ['GET', null, 'categories:read', 'categories.view', []],
        'categories.store' => ['POST', null, 'categories:write', 'categories.create', ['name' => 'Pets', 'type' => 'expense']],
        'categories.update' => ['PUT', 'category', 'categories:write', 'categories.update', ['name' => 'Invasão', 'type' => 'income']],
        'categories.destroy' => ['DELETE', 'category', 'categories:write', 'categories.delete', []],

        'reports.summary' => ['GET', null, 'reports:read', 'reports.view', []],
    ];
}

// Each case receives the route name first, then the row.
dataset('apiEndpoints', collect(apiEndpoints())
    ->mapWithKeys(fn (array $row, string $name): array => [$name => [$name, ...$row]])
    ->all());

function matrixRecord(?string $type, User $owner): ?Model
{
    return match ($type) {
        null => null,
        'wallet' => Wallet::factory()->for($owner)->create(['type' => 'checking', 'balance' => 1000]),
        'transaction' => Transaction::factory()->create([
            'user_id' => $owner->id,
            'wallet_id' => Wallet::factory()->for($owner)->create(['type' => 'checking', 'balance' => 1000])->id,
            'type' => 'expense',
            'status' => 'pending',
            'amount' => 100,
            'date' => now()->toDateString(),
            'is_recurring' => false,
        ]),
        'goal' => Goal::factory()->create(['user_id' => $owner->id]),
        'budget' => Budget::factory()->create(['user_id' => $owner->id]),
        'category' => Category::factory()->create(['user_id' => $owner->id]),
    };
}

/**
 * @param  array<string, mixed>  $body
 */
function matrixCall(string $method, string $name, ?Model $record, array $body, ?string $token = null): TestResponse
{
    $test = test();

    if ($token !== null) {
        $test->withToken($token);
    }

    return $test->json($method, route('api.v1.'.$name, $record instanceof Model ? [$record->getKey()] : []), $body);
}

/** Raw row (no global scopes), to prove a foreign record was not touched. */
function matrixRow(Model $record): array
{
    return $record->newQueryWithoutScopes()->whereKey($record->getKey())->firstOrFail()->getAttributes();
}

it('covers every registered api.v1 route', function () {
    $registered = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route): string => (string) $route->getName())
        ->filter(fn (string $name): bool => str_starts_with($name, 'api.v1.'))
        ->sort()->values()->all();

    $covered = collect(array_keys(apiEndpoints()))
        ->map(fn (string $name): string => 'api.v1.'.$name)
        ->sort()->values()->all();

    expect($covered)->toBe($registered);
});

it('answers 401 without a token', function (string $name, string $method, ?string $type, string $ability, ?string $permission, array $body) {
    $record = matrixRecord($type, proUser());

    matrixCall($method, $name, $record, $body)
        ->assertStatus(401)
        ->assertJsonPath('code', 'unauthenticated');
})->with('apiEndpoints');

it('answers 403 missing_ability without the route ability', function (string $name, string $method, ?string $type, string $ability, ?string $permission, array $body) {
    $user = proUser();
    $others = array_values(array_diff(ApiTokenAbility::values(), [$ability]));

    matrixCall($method, $name, matrixRecord($type, $user), $body, apiToken($user, $others))
        ->assertStatus(403)
        ->assertJsonPath('code', 'missing_ability')
        ->assertJsonPath('abilities', [$ability]);
})->with('apiEndpoints');

it('answers 403 forbidden when the role lacks the Spatie permission', function (string $name, string $method, ?string $type, string $ability, ?string $permission, array $body) {
    if ($permission === null) {
        $this->markTestSkipped('Endpoint without a Spatie permission.');
    }

    $user = proUser();
    $limited = Role::findOrCreate('limited', RolesAndPermissionsSeeder::GUARD);
    $limited->syncPermissions(array_values(array_diff(RolesAndPermissionsSeeder::PERMISSIONS, [$permission, 'admin.access'])));
    $user->syncRoles([$limited]);

    $response = matrixCall($method, $name, matrixRecord($type, $user), $body, apiToken($user))
        ->assertStatus(403)
        ->assertJsonPath('code', 'forbidden');

    expect($response->json('message'))->toBe('Você não tem permissão para realizar esta ação.');
})->with('apiEndpoints');

it('answers 403 plan_upgrade_required for Free users', function (string $name, string $method, ?string $type, string $ability, ?string $permission, array $body) {
    $user = freeUser();

    matrixCall($method, $name, matrixRecord($type, $user), $body, apiToken($user))
        ->assertStatus(403)
        ->assertJsonPath('code', 'plan_upgrade_required');
})->with('apiEndpoints');

it('answers 429 over the rate limit', function (string $name, string $method, ?string $type, string $ability, ?string $permission, array $body) {
    config(['api.rate_limit_per_minute' => 1]);
    $user = proUser();
    $token = apiToken($user);
    $record = matrixRecord($type, $user);

    matrixCall($method, $name, $record, $body, $token);

    matrixCall($method, $name, $record, $body, $token)
        ->assertStatus(429)
        ->assertJsonPath('code', 'too_many_requests')
        ->assertHeader('Retry-After');
})->with('apiEndpoints');

it('answers 404 for another user\'s record and leaves it unchanged', function (string $name, string $method, ?string $type, string $ability, ?string $permission, array $body) {
    if ($type === null) {
        $this->markTestSkipped('Endpoint without a record id.');
    }

    $record = matrixRecord($type, proUser());
    $before = matrixRow($record);

    $intruder = proUser();

    matrixCall($method, $name, $record, $body, apiToken($intruder))
        ->assertStatus(404)
        ->assertJsonPath('code', 'not_found')
        ->assertJsonPath('message', 'Recurso não encontrado.');

    expect(matrixRow($record))->toBe($before);
})->with('apiEndpoints');
