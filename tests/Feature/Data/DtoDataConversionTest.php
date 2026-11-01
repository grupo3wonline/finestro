<?php

use App\DTOs\AiCredentialDTO;
use App\DTOs\BudgetDTO;
use App\DTOs\CategoryDTO;
use App\DTOs\GoalDTO;
use App\DTOs\SubscriptionDTO;
use App\DTOs\TransactionDTO;
use App\DTOs\WalletDTO;
use App\Enums\SubscriptionStatus;
use Spatie\LaravelData\Data;

it('makes every DTO a laravel-data object', function (string $class): void {
    expect(is_subclass_of($class, Data::class))->toBeTrue();
})->with([
    AiCredentialDTO::class,
    BudgetDTO::class,
    CategoryDTO::class,
    GoalDTO::class,
    SubscriptionDTO::class,
    TransactionDTO::class,
    WalletDTO::class,
]);

it('keeps TransactionDTO null handling and casts', function (): void {
    $dto = TransactionDTO::fromArray(['description' => 'x', 'notes' => null, 'amount' => '10.5']);

    expect($dto)->toBeInstanceOf(Data::class)
        ->and($dto->toArray())->toBe(['amount' => 10.5, 'description' => 'x'])
        ->and($dto->toArray(includeProvidedNulls: true))->toBe([
            'amount' => 10.5,
            'description' => 'x',
            'notes' => null,
        ]);
});

it('copies TransactionDTO with overrides, keeping installments and reason', function (): void {
    $dto = TransactionDTO::fromArray([
        'description' => 'Compra',
        'amount' => 300,
        'installments' => 3,
        'reason' => '  ajuste  ',
        'receipt_path' => 'receipts/a.pdf',
    ]);

    $copy = $dto->withOverrides(['description' => 'Compra (1/3)', 'receipt_path' => null]);

    expect($copy)->not->toBe($dto)
        ->and($copy->installments)->toBe(3)
        ->and($copy->reason)->toBe('ajuste')
        ->and($copy->description)->toBe('Compra (1/3)')
        ->and($copy->amount)->toBe(300.0)
        ->and($copy->receiptPath)->toBeNull()
        ->and($dto->description)->toBe('Compra');
});

it('keeps BudgetDTO defaults and non-persisted flag', function (): void {
    $dto = BudgetDTO::fromArray(['category_id' => 'c1', 'year' => '2026', 'month' => '3', 'amount' => '500', 'repeat_until_december' => '1']);

    expect($dto->repeatUntilDecember)->toBeTrue()
        ->and($dto->toArray())->toBe([
            'category_id' => 'c1',
            'year' => 2026,
            'month' => 3,
            'amount' => 500.0,
            'alert_threshold' => 80,
            'notes' => null,
        ]);
});

it('normalizes SubscriptionDTO status and strips nulls', function (): void {
    $dto = SubscriptionDTO::fromArray(['status' => 'active', 'cancel_at_period_end' => false, 'plan' => 'pro']);

    expect($dto->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($dto->toArray())->toBe([
            'plan' => 'pro',
            'status' => 'active',
            'cancel_at_period_end' => false,
        ]);
});

it('strips nulls and casts numbers on Category, Goal and Wallet DTOs', function (): void {
    expect(CategoryDTO::fromArray(['name' => 'Mercado', 'icon' => null])->toArray())
        ->toBe(['name' => 'Mercado'])
        ->and(GoalDTO::fromArray(['name' => 'Viagem', 'target_amount' => '1000', 'deadline' => null])->toArray())
        ->toBe(['name' => 'Viagem', 'target_amount' => 1000.0])
        ->and(WalletDTO::fromArray(['name' => 'Conta', 'balance' => '12.3', 'due_day' => '10', 'is_active' => 0, 'color' => null])->toArray())
        ->toBe(['name' => 'Conta', 'balance' => 12.3, 'due_day' => 10, 'is_active' => false])
        ->and(WalletDTO::forUpdate(['name' => 'Conta', 'company_name' => null, 'closing_day' => null, 'color' => null])->toArray())
        ->toBe(['name' => 'Conta', 'company_name' => null, 'closing_day' => null]);
});

it('keeps AiCredentialDTO nulls for persistence and hides keys from JSON', function (): void {
    $dto = AiCredentialDTO::fromArray([
        'user_id' => 'u1',
        'preferred_provider' => 'gemini',
        'gemini_api_key' => 'secret-gemini-value',
    ]);

    expect($dto->toArray())->toBe([
        'gemini_api_key' => 'secret-gemini-value',
        'groq_api_key' => null,
        'openrouter_api_key' => null,
        'preferred_provider' => 'gemini',
    ]);

    $json = $dto->toJson();

    expect($json)->not->toContain('secret-gemini-value')
        ->and($json)->not->toContain('ApiKey')
        ->and($json)->not->toContain('api_key')
        ->and($dto->transform())->toBe(['userId' => 'u1', 'preferredProvider' => 'gemini']);
});
