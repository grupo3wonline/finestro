<?php

namespace App\DTOs;

class GoalDTO
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?float $targetAmount = null,
        public readonly ?float $currentAmount = null,
        public readonly ?string $deadline = null,
        public readonly ?string $status = null,
        public readonly ?string $icon = null,
        public readonly ?string $color = null,
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
            description: $data['description'] ?? null,
            targetAmount: isset($data['target_amount']) ? (float) $data['target_amount'] : null,
            currentAmount: isset($data['current_amount']) ? (float) $data['current_amount'] : null,
            deadline: $data['deadline'] ?? null,
            status: $data['status'] ?? null,
            icon: $data['icon'] ?? null,
            color: $data['color'] ?? null,
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
            'description' => $this->description,
            'target_amount' => $this->targetAmount,
            'current_amount' => $this->currentAmount,
            'deadline' => $this->deadline,
            'status' => $this->status,
            'icon' => $this->icon,
            'color' => $this->color,
        ], fn($value) => !is_null($value));
    }
}
