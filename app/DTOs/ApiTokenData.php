<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;

/**
 * Validated input for a new personal API token.
 */
class ApiTokenData extends Data
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public readonly string $name,
        public readonly array $abilities,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) $data['name']),
            abilities: array_values(array_unique(array_map(strval(...), (array) $data['abilities']))),
        );
    }
}
