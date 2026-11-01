<?php

use App\Models\Goal;

/*
 * /api/v1/goals: full CRUD, exact keys, pagination and pt-BR errors.
 */

const GOAL_API_KEYS = [
    'id', 'name', 'description', 'target_amount', 'current_amount', 'progress', 'deadline', 'status', 'icon', 'color',
    'created_at', 'updated_at',
];

beforeEach(function () {
    $this->user = proUser();
    $this->token = apiToken($this->user, ['goals:read', 'goals:write']);
});

it('lists own goals with pagination', function () {
    Goal::factory()->count(3)->create(['user_id' => $this->user->id]);
    $foreign = Goal::factory()->create(['user_id' => proUser()->id]);

    $response = $this->withToken($this->token)->getJson('/api/v1/goals?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);

    expect(array_keys($response->json('data.0')))->toEqualCanonicalizing(GOAL_API_KEYS)
        ->and($response->getContent())->not->toContain($foreign->id)->not->toContain('user_id');

    $this->withToken($this->token)->getJson('/api/v1/goals?per_page=101')
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed');
});

it('creates a goal (201) and validates in pt-BR', function () {
    $response = $this->withToken($this->token)->postJson('/api/v1/goals', [
        'name' => 'Reserva',
        'target_amount' => 1000,
        'current_amount' => 250,
        'deadline' => now()->addYear()->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.target_amount', '1000.00')
        ->assertJsonPath('data.progress', 25);

    expect(array_keys($response->json('data')))->toEqualCanonicalizing(GOAL_API_KEYS);

    $this->withToken($this->token)->postJson('/api/v1/goals', ['target_amount' => 0])
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.name.0', 'O nome da meta é obrigatório.')
        ->assertJsonPath('errors.target_amount.0', 'O valor alvo deve ser maior que zero.');
});

it('shows, updates (PUT/PATCH) and deletes a goal', function () {
    $goal = Goal::factory()->create(['user_id' => $this->user->id, 'name' => 'Viagem']);

    $this->withToken($this->token)->getJson("/api/v1/goals/{$goal->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Viagem');

    $this->withToken($this->token)->putJson("/api/v1/goals/{$goal->id}", ['name' => 'Viagem 2027'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Viagem 2027');

    $this->withToken($this->token)->patchJson("/api/v1/goals/{$goal->id}", ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->withToken($this->token)->patchJson("/api/v1/goals/{$goal->id}", ['status' => 'foo'])
        ->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Status inválido.');

    $this->withToken($this->token)->deleteJson("/api/v1/goals/{$goal->id}")->assertNoContent();
    $this->assertSoftDeleted('goals', ['id' => $goal->id]);
});
