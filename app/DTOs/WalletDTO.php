<?php

namespace App\DTOs;

class WalletDTO
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?float $balance = null,
        public readonly ?string $currency = null,
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
        public readonly ?string $description = null,
        public readonly ?bool $isActive = null,
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
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            currency: $data['currency'] ?? null,
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            description: $data['description'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
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
            'balance' => $this->balance,
            'currency' => $this->currency,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ], fn($value) => !is_null($value));
    }
}
