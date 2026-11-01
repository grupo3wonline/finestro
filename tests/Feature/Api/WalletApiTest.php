<?php

use App\Models\Wallet;
use App\Models\WalletMember;

/*
 * /api/v1/wallets: own wallets only, exact resource keys, pagination,
 * pt-BR validation errors and shared-wallet read access.
 */

const WALLET_API_KEYS = [
    'id', 'name', 'type', 'ownership', 'company_name', 'company_document', 'balance', 'credit_limit', 'closing_day',
    'due_day', 'currency', 'color', 'icon', 'description', 'is_active', 'is_shared', 'is_owner', 'created_at', 'updated_at',
];

beforeEach(function () {
    $this->user = proUser();
    $this->token = apiToken($this->user, ['wallets:read', 'wallets:write']);
});

it('lists only the user\'s own wallets with pagination', function () {
    Wallet::factory()->for($this->user)->count(3)->create();
    $foreign = Wallet::factory()->for(proUser())->create(['name' => 'Carteira Alheia']);

    $response = $this->withToken($this->token)->getJson('/api/v1/wallets?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta']);

    expect(array_keys($response->json('data.0')))->toEqualCanonicalizing(WALLET_API_KEYS)
        ->and($response->getContent())->not->toContain($foreign->id)
        ->not->toContain('user_id')
        ->not->toContain('deleted_at');

    $this->withToken($this->token)->getJson('/api/v1/wallets?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

it('rejects per_page above the maximum', function () {
    $this->withToken($this->token)->getJson('/api/v1/wallets?per_page=101')
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.per_page.0', 'O parâmetro per_page deve ser no máximo 100.');
});

it('creates a wallet and answers 201 with the resource', function () {
    $response = $this->withToken($this->token)->postJson('/api/v1/wallets', [
        'name' => 'Conta PJ',
        'type' => 'checking',
        'ownership' => 'business',
        'company_name' => 'ACME Ltda',
        'company_document' => '12.345.678/0001-90',
        'balance' => 250.5,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Conta PJ')
        ->assertJsonPath('data.balance', '250.50')
        ->assertJsonPath('data.company_document', '12.345.678/0001-90')
        ->assertJsonPath('data.is_owner', true);

    expect(array_keys($response->json('data')))->toEqualCanonicalizing(WALLET_API_KEYS);
    $this->assertDatabaseHas('wallets', ['id' => $response->json('data.id'), 'user_id' => $this->user->id]);
});

it('returns pt-BR validation errors on store', function () {
    $this->withToken($this->token)->postJson('/api/v1/wallets', ['type' => 'foo'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('message', 'Os dados enviados são inválidos.')
        ->assertJsonPath('errors.name.0', 'O nome da carteira é obrigatório.')
        ->assertJsonPath('errors.type.0', 'Tipo de carteira inválido.');
});

it('shows an own wallet', function () {
    $wallet = Wallet::factory()->for($this->user)->create(['company_document' => '123']);

    $response = $this->withToken($this->token)->getJson("/api/v1/wallets/{$wallet->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $wallet->id)
        ->assertJsonPath('data.company_document', '123');

    expect(array_keys($response->json('data')))->toEqualCanonicalizing(WALLET_API_KEYS);
});

it('updates with PUT and PATCH and validates in pt-BR', function () {
    $wallet = Wallet::factory()->for($this->user)->create(['name' => 'Antiga']);

    $this->withToken($this->token)->putJson("/api/v1/wallets/{$wallet->id}", ['name' => 'Nova'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nova');

    $this->withToken($this->token)->patchJson("/api/v1/wallets/{$wallet->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($this->token)->patchJson("/api/v1/wallets/{$wallet->id}", ['type' => 'foo'])
        ->assertStatus(422)
        ->assertJsonPath('errors.type.0', 'Tipo de carteira inválido.');
});

it('persists and returns each business/credit-card field on PATCH and PUT', function (string $field, mixed $value, mixed $expected) {
    $wallet = Wallet::factory()->for($this->user)->create(['name' => 'Cartão', 'type' => 'credit']);

    foreach (['patchJson', 'putJson'] as $verb) {
        $this->withToken($this->token)->{$verb}("/api/v1/wallets/{$wallet->id}", [$field => $value])
            ->assertOk()
            ->assertJsonPath("data.{$field}", $expected)
            ->assertJsonPath('data.name', 'Cartão');
    }

    expect($wallet->fresh()->{$field})->toEqual($expected);
})->with([
    'company_name' => ['company_name', 'ACME Ltda', 'ACME Ltda'],
    'company_document' => ['company_document', '12.345.678/0001-90', '12.345.678/0001-90'],
    'credit_limit' => ['credit_limit', 5000, '5000.00'],
    'closing_day' => ['closing_day', 5, 5],
    'due_day' => ['due_day', 15, 15],
]);

it('rejects invalid business/credit-card values with 422', function (string $field, mixed $value) {
    $wallet = Wallet::factory()->for($this->user)->create();

    foreach (['patchJson', 'putJson'] as $verb) {
        $this->withToken($this->token)->{$verb}("/api/v1/wallets/{$wallet->id}", [$field => $value])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors([$field]);
    }
})->with([
    'credit_limit negative' => ['credit_limit', -1],
    'closing_day zero' => ['closing_day', 0],
    'due_day 32' => ['due_day', 32],
    'company_name 256 chars' => ['company_name', str_repeat('a', 256)],
    'company_document 21 chars' => ['company_document', str_repeat('1', 21)],
]);

it('keeps the other fields when PATCH sends only the name', function () {
    $wallet = Wallet::factory()->for($this->user)->create([
        'type' => 'credit', 'credit_limit' => 1000, 'closing_day' => 3, 'due_day' => 10,
        'company_name' => 'ACME', 'company_document' => '123',
    ]);

    $this->withToken($this->token)->patchJson("/api/v1/wallets/{$wallet->id}", ['name' => 'Renomeada'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renomeada')
        ->assertJsonPath('data.credit_limit', '1000.00')
        ->assertJsonPath('data.closing_day', 3)
        ->assertJsonPath('data.due_day', 10)
        ->assertJsonPath('data.company_name', 'ACME')
        ->assertJsonPath('data.company_document', '123');
});

it('clears the business fields of a non-card wallet when PATCH or PUT sends null', function (string $verb) {
    $wallet = Wallet::factory()->for($this->user)->create([
        'name' => 'Conta PJ', 'type' => 'checking', 'ownership' => 'business',
        'company_name' => 'ACME', 'company_document' => '123', 'description' => 'Principal',
    ]);

    $this->withToken($this->token)->{$verb}("/api/v1/wallets/{$wallet->id}", [
        'ownership' => 'personal', 'company_name' => null, 'company_document' => null, 'description' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.ownership', 'personal')
        ->assertJsonPath('data.company_name', null)
        ->assertJsonPath('data.company_document', null)
        ->assertJsonPath('data.name', 'Conta PJ');

    // description is not a clearable field: null keeps the current value.
    expect($wallet->fresh())
        ->company_name->toBeNull()
        ->company_document->toBeNull()
        ->description->toBe('Principal');
})->with(['patchJson', 'putJson']);

it('clears the credit-card fields when sent as null (nullable in the store rules) and keeps omitted ones', function (string $verb) {
    $wallet = Wallet::factory()->for($this->user)->create([
        'name' => 'Cartão', 'type' => 'credit', 'credit_limit' => 1000, 'closing_day' => 3, 'due_day' => 10,
    ]);

    $this->withToken($this->token)->{$verb}("/api/v1/wallets/{$wallet->id}", ['credit_limit' => null, 'closing_day' => null])
        ->assertOk()
        ->assertJsonPath('data.credit_limit', null)
        ->assertJsonPath('data.closing_day', null)
        ->assertJsonPath('data.due_day', 10);

    // A card without both days has no invoice cycle yet; it is not broken.
    expect($wallet->fresh()->hasInvoiceCycle())->toBeFalse();
})->with(['patchJson', 'putJson']);

it('deletes with 204', function () {
    $wallet = Wallet::factory()->for($this->user)->create();

    $this->withToken($this->token)->deleteJson("/api/v1/wallets/{$wallet->id}")->assertNoContent();

    $this->assertSoftDeleted('wallets', ['id' => $wallet->id]);
});

it('shows a shared wallet to an accepted member without the company document', function () {
    $owner = proUser();
    $wallet = Wallet::factory()->for($owner)->create(['company_document' => '99.999.999/0001-99', 'is_shared' => true]);
    WalletMember::factory()->create(['wallet_id' => $wallet->id, 'user_id' => $this->user->id, 'accepted_at' => now()]);

    $response = $this->withToken($this->token)->getJson("/api/v1/wallets/{$wallet->id}")
        ->assertOk()
        ->assertJsonPath('data.is_owner', false)
        ->assertJsonMissingPath('data.company_document');

    expect($response->getContent())->not->toContain('99.999.999/0001-99');

    // Members can read, never change, the owner's wallet.
    $this->withToken($this->token)->patchJson("/api/v1/wallets/{$wallet->id}", ['name' => 'X'])->assertNotFound();
});
