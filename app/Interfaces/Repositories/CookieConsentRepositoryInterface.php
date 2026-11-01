<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\ConsentData;
use App\Models\CookieConsent;
use Illuminate\Support\Collection;

interface CookieConsentRepositoryInterface
{
    /**
     * Store one consent decision of the user (proof of consent).
     */
    public function record(string $userId, ConsentData $consent, string $source): CookieConsent;

    /**
     * The user's consent decisions, newest first.
     *
     * @return Collection<int, CookieConsent>
     */
    public function historyFor(string $userId, int $limit = 50): Collection;
}
