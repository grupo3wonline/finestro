<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\ConsentData;
use App\Interfaces\Repositories\CookieConsentRepositoryInterface;
use App\Models\CookieConsent;
use Illuminate\Support\Collection;

class CookieConsentRepository implements CookieConsentRepositoryInterface
{
    public function record(string $userId, ConsentData $consent, string $source): CookieConsent
    {
        return CookieConsent::query()->create([
            'user_id' => $userId,
            'version' => $consent->version,
            'analytics' => $consent->analytics,
            'marketing' => $consent->marketing,
            'gpc' => $consent->gpc,
            'source' => $source,
        ]);
    }

    public function historyFor(string $userId, int $limit = 50): Collection
    {
        // Explicit user filter: the BelongsToUser scope needs an authenticated user.
        return CookieConsent::withoutGlobalScope('user')
            ->where('user_id', $userId)
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
