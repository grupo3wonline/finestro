<?php

namespace App\DTOs;

class WalletDTO
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $ownership = null,
        public readonly ?string $companyName = null,
        public readonly ?string $companyDocument = null,
        public readonly ?float $balance = null,
        public readonly ?float $creditLimit = null,
        public readonly ?int $closingDay = null,
        public readonly ?int $dueDay = null,
        public readonly ?string $currency = null,
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
        public readonly ?string $description = null,
        public readonly ?bool $isActive = null,
        public readonly ?bool $isShared = null,
    ) {}

    /**
     * Create DTO from request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'] ?? null,
            name: $data['name'] ?? null,
            type: $data['type'] ?? null,
            ownership: $data['ownership'] ?? null,
            companyName: $data['company_name'] ?? null,
            companyDocument: $data['company_document'] ?? null,
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            creditLimit: isset($data['credit_limit']) ? (float) $data['credit_limit'] : null,
            closingDay: isset($data['closing_day']) ? (int) $data['closing_day'] : null,
            dueDay: isset($data['due_day']) ? (int) $data['due_day'] : null,
            currency: $data['currency'] ?? null,
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            description: $data['description'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            isShared: isset($data['is_shared']) ? (bool) $data['is_shared'] : null,
        );
    }

    /**
     * Convert DTO to array for model operations.
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'name' => $this->name,
            'type' => $this->type,
            'ownership' => $this->ownership,
            'company_name' => $this->companyName,
            'company_document' => $this->companyDocument,
            'balance' => $this->balance,
            'credit_limit' => $this->creditLimit,
            'closing_day' => $this->closingDay,
            'due_day' => $this->dueDay,
            'currency' => $this->currency,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'is_shared' => $this->isShared,
        ], fn($value) => !is_null($value));
    }
}
