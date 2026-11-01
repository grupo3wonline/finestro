<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;

/*
 * /api/v1/budgets: month list with usage + overview (not paginated),
 * create, update, delete and pt-BR validation.
 */

beforeEach(function () {
    $this->user = proUser();
    $this->token = apiToken($this->user, ['budgets:read', 'budgets:write']);
});

it('lists the month budgets with usage and the overview', function () {
    $category = Category::factory()->create(['user_id' => $this->user->id, 'type' => 'expense', 'name' => 'Mercado']);
    Budget::factory()->forCategory($category->id)->forPeriod(2026, 3)->create(['user_id' => $this->user->id, 'amount' => 500]);
    Transaction::factory()->create([
        'user_id' => $this->user->id,
        'wallet_id' => Wallet::factory()->for($this->user)->create()->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'status' => 'paid',
        'amount' => 125,
        'date' => '2026-03-10',
        'is_recurring' => false,
    ]);
    Budget::factory()->forPeriod(2026, 3)->create(['user_id' => proUser()->id]);

    $response = $this->withToken($this->token)->getJson('/api/v1/budgets?year=2026&month=3')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.label', 'Mercado')
        ->assertJsonPath('data.0.category.name', 'Mercado')
        ->assertJsonPath('data.0.amount', '500.00')
        ->assertJsonPath('data.0.spent', 125)
        ->assertJsonPath('data.0.percent', 25)
        ->assertJsonPath('data.0.health', 'ok')
        ->assertJsonPath('meta.year', 2026)
        ->assertJsonPath('meta.month', 3)
        ->assertJsonStructure(['meta' => ['overview' => ['planned', 'spent', 'remaining', 'percent', 'unbudgeted']]]);

    expect(array_keys($response->json('data.0')))->toEqualCanonicalizing([
        'id', 'category_id', 'category', 'label', 'year', 'month', 'amount', 'alert_threshold', 'notes',
        'spent', 'remaining', 'percent', 'health',
    ])->and($response->getContent())->not->toContain('user_id');

    $this->withToken($this->token)->getJson('/api/v1/budgets?month=13')
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed');
});

it('creates a budget (201) and validates in pt-BR', function () {
    $category = Category::factory()->create(['user_id' => $this->user->id, 'type' => 'expense']);

    $this->withToken($this->token)->postJson('/api/v1/budgets', [
        'category_id' => $category->id, 'year' => 2026, 'month' => 10, 'amount' => 300, 'repeat_until_december' => true,
    ])->assertCreated()
        ->assertJsonPath('data.saved_months', 3)
        ->assertJsonPath('message', 'Orçamento salvo para 3 meses.');

    expect(Budget::query()->where('user_id', $this->user->id)->count())->toBe(3);

    $this->withToken($this->token)->postJson('/api/v1/budgets', ['year' => 2026, 'month' => 1])
        ->assertStatus(422)
        ->assertJsonPath('errors.amount.0', 'Informe o valor do orçamento.');

    $foreignCategory = Category::factory()->create(['user_id' => proUser()->id, 'type' => 'expense']);
    $this->withToken($this->token)->postJson('/api/v1/budgets', [
        'category_id' => $foreignCategory->id, 'year' => 2026, 'month' => 1, 'amount' => 100,
    ])->assertStatus(422)
        ->assertJsonPath('errors.category_id.0', 'Selecione uma categoria de despesa válida.');
});

it('updates (PUT/PATCH) and deletes a budget', function () {
    $budget = Budget::factory()->forPeriod(2026, 5)->create(['user_id' => $this->user->id, 'amount' => 100]);

    $response = $this->withToken($this->token)->putJson("/api/v1/budgets/{$budget->id}", ['amount' => 450])
        ->assertOk()
        ->assertJsonPath('data.amount', '450.00')
        ->assertJsonMissingPath('data.spent');

    expect($response->json('data.label'))->toBe('Orçamento geral');

    $this->withToken($this->token)->patchJson("/api/v1/budgets/{$budget->id}", ['amount' => 500, 'alert_threshold' => 90])
        ->assertOk()
        ->assertJsonPath('data.alert_threshold', 90);

    $this->withToken($this->token)->patchJson("/api/v1/budgets/{$budget->id}", ['amount' => 500, 'month' => 6])
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonStructure(['errors' => ['month']]);

    $this->withToken($this->token)->deleteJson("/api/v1/budgets/{$budget->id}")->assertNoContent();
    $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
});
