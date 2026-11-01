<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;

/**
 * One row of the "Atividade da conta" page, already translated to pt-BR.
 */
class ActivityEntryData extends Data
{
    /**
     * @param  list<array{field: string, old: string|null, new: string|null}>  $changes
     */
    public function __construct(
        public readonly string $date,
        public readonly string $event,
        public readonly string $eventLabel,
        public readonly string $subjectLabel,
        public readonly array $changes,
        public readonly ?string $ip,
        public readonly bool $viaSystem,
    ) {}
}
