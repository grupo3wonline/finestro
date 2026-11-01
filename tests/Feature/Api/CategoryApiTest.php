<?php

use App\Models\Category;

/*
 * /api/v1/categories: list (paginated, type filter), create, update, delete.
 */

const CATEGORY_API_KEYS = ['id', 'name', 'type', 'icon', 'color', 'created_at', 'updated_at'];

beforeEach(function () {
    $this->user = proUser();
    $this->token = apiToken($this->user, ['categories:read', 'categories:write']);
});

it('lists own categories with pagination and the type filter', function () {
    Category::factory()->count(2)->create(['user_id' => $this->user->id, 'type' => 'expense']);
    Category::factory()->create(['user_id' => $this->user->id, 'type' => 'income']);
    $foreign = Category::factory()->create(['user_id' => proUser()->id]);

    $response = $this->withToken($this->token)->getJson('/api/v1/categories?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);

    expect(array_keys($response->json('data.0')))->toEqualCanonicalizing(CATEGORY_API_KEYS)
        ->and($response->getContent())->not->toContain($foreign->id)->not->toContain('user_id');

    $this->withToken($this->token)->getJson('/api/v1/categories?type=income')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'income');

    $this->withToken($this->token)->getJson('/api/v1/categories?per_page=101')->assertStatus(422);
});

it('creates a category (201) and validates in pt-BR', function () {
    $response = $this->withToken($this->token)->postJson('/api/v1/categories', ['name' => 'Pets', 'type' => 'expense'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Pets');

    expect(array_keys($response->json('data')))->toEqualCanonicalizing(CATEGORY_API_KEYS);
    $this->assertDatabaseHas('categories', ['name' => 'Pets', 'user_id' => $this->user->id]);

    $this->withToken($this->token)->postJson('/api/v1/categories', ['type' => 'foo'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.name.0', 'O nome da categoria é obrigatório.')
        ->assertJsonPath('errors.type.0', 'O tipo deve ser receita ou despesa.');
});

it('updates (PUT/PATCH) and deletes a category', function () {
    $category = Category::factory()->create(['user_id' => $this->user->id, 'type' => 'expense']);

    $this->withToken($this->token)->putJson("/api/v1/categories/{$category->id}", ['name' => 'Casa', 'type' => 'expense'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Casa');

    $this->withToken($this->token)->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Lar', 'type' => 'expense'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Lar');

    $this->withToken($this->token)->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Lar'])
        ->assertStatus(422)
        ->assertJsonPath('errors.type.0', 'O tipo da categoria é obrigatório.');

    $this->withToken($this->token)->deleteJson("/api/v1/categories/{$category->id}")->assertNoContent();
    $this->assertSoftDeleted('categories', ['id' => $category->id]);
});
