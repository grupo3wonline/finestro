<?php

namespace App\DTOs;

class TransactionDTO
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $walletId = null,
        public readonly ?string $categoryId = null,
        public readonly ?string $type = null,
        public readonly ?float $amount = null,
        public readonly ?string $description = null,
        public readonly ?string $date = null,
        public readonly ?bool $isRecurring = null,
        public readonly ?string $recurringFrequency = null,
        public readonly ?string $receiptPath = null,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Create DTO from request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'] ?? null,
            walletId: $data['wallet_id'] ?? null,
            categoryId: $data['category_id'] ?? null,
            type: $data['type'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            description: $data['description'] ?? null,
            date: $data['date'] ?? null,
            isRecurring: isset($data['is_recurring']) ? (bool) $data['is_recurring'] : null,
            recurringFrequency: $data['recurring_frequency'] ?? null,
            receiptPath: $data['receipt_path'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert DTO to array for model operations.
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'wallet_id' => $this->walletId,
            'category_id' => $this->categoryId,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date,
            'is_recurring' => $this->isRecurring,
            'recurring_frequency' => $this->recurringFrequency,
            'receipt_path' => $this->receiptPath,
            'notes' => $this->notes,
        ], fn($value) => !is_null($value));
    }
}
