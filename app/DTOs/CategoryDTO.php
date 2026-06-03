<?php

namespace App\DTOs;

class CategoryDTO
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $name = null,
        public readonly ?string $type = null,
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
            type: $data['type'] ?? null,
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
            'type' => $this->type,
            'icon' => $this->icon,
            'color' => $this->color,
        ], fn($value) => !is_null($value));
    }
}
