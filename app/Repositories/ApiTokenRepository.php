<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\ApiTokenData;
use App\Interfaces\Repositories\ApiTokenRepositoryInterface;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenRepository implements ApiTokenRepositoryInterface
{
    public function listFor(User $user): Collection
    {
        /** @var Collection<int, PersonalAccessToken> $tokens */
        $tokens = $user->tokens()->orderByDesc('created_at')->orderByDesc('id')->get();

        return $tokens;
    }

    public function create(User $user, ApiTokenData $data, ?CarbonInterface $expiresAt): NewAccessToken
    {
        return $user->createToken($data->name, $data->abilities, $expiresAt);
    }

    public function findFor(User $user, string $id): ?PersonalAccessToken
    {
        /** @var PersonalAccessToken|null $token */
        $token = $user->tokens()->whereKey($id)->first();

        return $token;
    }

    public function delete(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}
