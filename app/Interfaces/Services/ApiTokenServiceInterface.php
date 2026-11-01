<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\ApiTokenData;
use App\Exceptions\PlanLimitException;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

interface ApiTokenServiceInterface
{
    /** @return Collection<int, PersonalAccessToken> */
    public function list(User $user): Collection;

    /**
     * Create a token and return its plain-text value (shown to the user once,
     * never logged or stored).
     *
     * @throws PlanLimitException when the plan has no API access
     */
    public function create(User $user, ApiTokenData $data): string;

    /** Delete one of the user's tokens; false when it is not theirs. */
    public function revoke(User $user, string $id): bool;
}
