<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\DTOs\ApiTokenData;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Personal API tokens, always queried through $user->tokens().
 */
interface ApiTokenRepositoryInterface
{
    /** @return Collection<int, PersonalAccessToken> newest first */
    public function listFor(User $user): Collection;

    public function create(User $user, ApiTokenData $data, ?CarbonInterface $expiresAt): NewAccessToken;

    public function findFor(User $user, string $id): ?PersonalAccessToken;

    public function delete(PersonalAccessToken $token): void;
}
