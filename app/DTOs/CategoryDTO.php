<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class CategoryDTO extends Data
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
     *
     * @param  array<string, mixed>  $data
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
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'name' => $this->name,
            'type' => $this->type,
            'icon' => $this->icon,
            'color' => $this->color,
        ], fn (?string $value): bool => ! is_null($value));
    }
}
