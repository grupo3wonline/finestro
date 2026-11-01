<?php

declare(strict_types=1);

namespace App\DTOs;

use Spatie\LaravelData\Data;

/**
 * A visitor's cookie choices for one policy version.
 *
 * Necessary cookies are always on and not part of the choice. decidedAt is
 * the unix time of the decision; null means "no valid decision yet" (the
 * banner is shown and only necessary cookies are used).
 */
class ConsentData extends Data
{
    public function __construct(
        public readonly string $version,
        public readonly bool $analytics,
        public readonly bool $marketing,
        public readonly bool $gpc,
        public readonly ?int $decidedAt,
    ) {}

    /**
     * No decision: everything optional is off.
     */
    public static function none(string $version): self
    {
        return new self($version, false, false, false, null);
    }

    public function isDecided(): bool
    {
        return $this->decidedAt !== null;
    }

    /**
     * JSON stored in the first-party consent cookie (read by resources/js/consent.js).
     */
    public function toCookieValue(): string
    {
        return json_encode([
            'v' => $this->version,
            'a' => $this->analytics ? 1 : 0,
            'm' => $this->marketing ? 1 : 0,
            'g' => $this->gpc ? 1 : 0,
            't' => $this->decidedAt,
        ], JSON_THROW_ON_ERROR);
    }
}
