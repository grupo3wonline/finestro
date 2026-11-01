<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiTokenData;
use App\Exceptions\PlanLimitException;
use App\Interfaces\Repositories\ApiTokenRepositoryInterface;
use App\Interfaces\Services\ApiTokenServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenService implements ApiTokenServiceInterface
{
    public function __construct(
        protected ApiTokenRepositoryInterface $tokens,
        protected SecurityActivityLoggerInterface $security,
    ) {}

    public function list(User $user): Collection
    {
        return $this->tokens->listFor($user);
    }

    public function create(User $user, ApiTokenData $data): string
    {
        if (! $user->hasFeature('api_access')) {
            throw new PlanLimitException(
                'O acesso à API está disponível apenas no plano Profissional. Faça upgrade para criar tokens.',
                'plan_upgrade_required',
            );
        }

        $minutes = (int) config('sanctum.expiration');
        $expiresAt = $minutes > 0 ? now()->addMinutes($minutes) : null;

        $token = $this->tokens->create($user, $data, $expiresAt);

        // Only the name and abilities are logged; never the token value or hash.
        $this->security->log('api_token_created', $user, [
            'token_name' => $data->name,
            'abilities' => $data->abilities,
        ]);

        return $token->plainTextToken;
    }

    public function revoke(User $user, string $id): bool
    {
        $token = $this->tokens->findFor($user, $id);

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        $this->tokens->delete($token);

        $this->security->log('api_token_revoked', $user, [
            'token_name' => $token->name,
            'abilities' => $token->abilities ?? [],
        ]);

        return true;
    }
}
