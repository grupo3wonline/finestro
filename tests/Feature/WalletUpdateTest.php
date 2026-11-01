<?php

use App\Models\Wallet;

/*
 * Web wallet update (PUT wallets.update + edit form): the business and
 * credit-card fields are accepted, validated with the store rules and
 * rendered with their current values.
 */

beforeEach(function (): void {
    $this->user = proUser();
    $this->wallet = Wallet::factory()->for($this->user)->create([
        'name' => 'Cartão Roxo',
        'type' => 'credit',
        'ownership' => 'business',
        'company_name' => 'ACME Ltda',
        'company_document' => '12.345.678/0001-90',
        'credit_limit' => 3000,
        'closing_day' => 7,
        'due_day' => 17,
    ]);
});

it('renders the business and credit-card inputs with the current values', function (): void {
    $this->actingAs($this->user)
        ->get(route('wallets.edit', $this->wallet))
        ->assertOk()
        ->assertSee('name="company_name"', false)
        ->assertSee('value="ACME Ltda"', false)
        ->assertSee('name="company_document"', false)
        ->assertSee('value="12.345.678/0001-90"', false)
        ->assertSee('name="credit_limit"', false)
        ->assertSee('value="3000.00"', false)
        ->assertSee('name="closing_day"', false)
        ->assertSee('value="7"', false)
        ->assertSee('name="due_day"', false)
        ->assertSee('value="17"', false);
});

it('persists the business and credit-card fields', function (): void {
    $this->actingAs($this->user)
        ->put(route('wallets.update', $this->wallet), [
            'name' => 'Cartão Roxo',
            'type' => 'credit',
            'ownership' => 'business',
            'company_name' => 'Nova Razão',
            'company_document' => '98.765.432/0001-10',
            'credit_limit' => '4500.50',
            'closing_day' => '5',
            'due_day' => '15',
        ])
        ->assertRedirect(route('wallets.index'))
        ->assertSessionHasNoErrors();

    $fresh = $this->wallet->fresh();

    expect($fresh->company_name)->toBe('Nova Razão')
        ->and($fresh->company_document)->toBe('98.765.432/0001-10')
        ->and($fresh->credit_limit)->toBe('4500.50')
        ->and($fresh->closing_day)->toBe(5)
        ->and($fresh->due_day)->toBe(15);
});

it('rejects invalid values with session errors', function (string $field, mixed $value): void {
    $this->actingAs($this->user)
        ->from(route('wallets.edit', $this->wallet))
        ->put(route('wallets.update', $this->wallet), ['name' => 'Cartão Roxo', $field => $value])
        ->assertRedirect(route('wallets.edit', $this->wallet))
        ->assertSessionHasErrors([$field]);

    expect($this->wallet->fresh()->closing_day)->toBe(7);
})->with([
    'credit_limit negative' => ['credit_limit', -1],
    'closing_day zero' => ['closing_day', 0],
    'due_day 32' => ['due_day', 32],
    'company_name 256 chars' => ['company_name', str_repeat('a', 256)],
    'company_document 21 chars' => ['company_document', str_repeat('1', 21)],
]);

it('clears the business fields of a non-card wallet when the inputs are left blank', function (): void {
    $wallet = Wallet::factory()->for($this->user)->create([
        'name' => 'Conta PJ', 'type' => 'checking', 'ownership' => 'business',
        'company_name' => 'ACME Ltda', 'company_document' => '12.345.678/0001-90',
    ]);

    $this->actingAs($this->user)
        ->put(route('wallets.update', $wallet), [
            'name' => 'Conta PJ',
            'type' => 'checking',
            'ownership' => 'personal',
            'company_name' => '',
            'company_document' => '',
        ])
        ->assertRedirect(route('wallets.index'))
        ->assertSessionHasNoErrors();

    expect($wallet->fresh())
        ->ownership->toBe('personal')
        ->company_name->toBeNull()
        ->company_document->toBeNull();
});

it('clears blank credit-card inputs (nullable in the store rules) and the card falls back to "no invoice cycle"', function (): void {
    $this->actingAs($this->user)
        ->put(route('wallets.update', $this->wallet), [
            'name' => 'Cartão Roxo',
            'type' => 'credit',
            'ownership' => 'business',
            'company_name' => 'ACME Ltda',
            'company_document' => '12.345.678/0001-90',
            'credit_limit' => '',
            'closing_day' => '',
            'due_day' => '',
        ])
        ->assertRedirect(route('wallets.index'))
        ->assertSessionHasNoErrors();

    $fresh = $this->wallet->fresh();
    expect($fresh->credit_limit)->toBeNull()
        ->and($fresh->closing_day)->toBeNull()
        ->and($fresh->due_day)->toBeNull()
        ->and($fresh->company_name)->toBe('ACME Ltda')
        ->and($fresh->hasInvoiceCycle())->toBeFalse();

    $this->get(route('wallets.invoice', $this->wallet))
        ->assertRedirect(route('wallets.edit', $this->wallet))
        ->assertSessionHas('error', 'Informe o dia de fechamento e o dia de vencimento do cartão para ver a fatura.');
});

it('keeps fields that the request omits', function (): void {
    $this->actingAs($this->user)
        ->put(route('wallets.update', $this->wallet), ['name' => 'Cartão Renomeado'])
        ->assertSessionHasNoErrors();

    expect($this->wallet->fresh())
        ->name->toBe('Cartão Renomeado')
        ->company_name->toBe('ACME Ltda')
        ->closing_day->toBe(7)
        ->due_day->toBe(17);
});

it('still answers 404 for a foreign wallet', function (): void {
    $this->actingAs(proUser())
        ->put(route('wallets.update', $this->wallet), ['closing_day' => 1])
        ->assertNotFound();

    expect($this->wallet->fresh()->closing_day)->toBe(7);
});
