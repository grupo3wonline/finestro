<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

/**
 * The authenticated account, its plan and the token in use.
 * Explicit allowlist: never password, remember_token, role or Stripe data.
 *
 * @mixin User
 */
#[OA\Schema(
    schema: 'User',
    description: 'Conta autenticada, recursos e limites do plano e o token em uso.',
    properties: [
        new OA\Property(property: 'id', description: 'Identificador (UUID).', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Maria Silva'),
        new OA\Property(property: 'username', type: 'string', example: 'maria'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@example.com'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'plan', type: 'string', example: 'professional', enum: ['free', 'professional']),
        new OA\Property(property: 'plan_name', type: 'string', example: 'Profissional'),
        new OA\Property(property: 'features', properties: [
            new OA\Property(property: 'simulators', type: 'boolean', example: true),
            new OA\Property(property: 'reports_export', type: 'boolean', example: true),
            new OA\Property(property: 'ai_insights', type: 'boolean', example: true),
            new OA\Property(property: 'wallet_sharing', type: 'boolean', example: true),
            new OA\Property(property: 'api_access', type: 'boolean', example: true),
        ], type: 'object'),
        new OA\Property(property: 'limits', description: 'null = ilimitado.', properties: [
            new OA\Property(property: 'wallets', type: 'integer', example: null, nullable: true),
            new OA\Property(property: 'transactions_per_month', type: 'integer', example: null, nullable: true),
            new OA\Property(property: 'goals', type: 'integer', example: null, nullable: true),
            new OA\Property(property: 'categories', type: 'integer', example: null, nullable: true),
            new OA\Property(property: 'budgets_per_month', type: 'integer', example: null, nullable: true),
        ], type: 'object'),
        new OA\Property(property: 'token', description: 'Token usado nesta requisição.', properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Integração planilha'),
            new OA\Property(property: 'abilities', type: 'array', items: new OA\Items(type: 'string'), example: ['account:read', 'transactions:read']),
            new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        ], type: 'object', nullable: true),
    ],
    type: 'object',
)]
class UserResource extends JsonResource
{
    private const array FEATURES = ['simulators', 'reports_export', 'ai_insights', 'wallet_sharing', 'api_access'];

    private const array LIMITS = ['wallets', 'transactions_per_month', 'goals', 'categories', 'budgets_per_month'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $limits = $this->plan_limits;
        $plan = $this->current_plan;
        // Null (or a TransientToken) outside token-authenticated requests.
        /** @var HasAbilities|null $token */
        $token = $this->currentAccessToken();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'plan' => $plan,
            'plan_name' => config("stripe.plans.{$plan}.name"),
            'features' => collect(self::FEATURES)
                ->mapWithKeys(fn (string $feature): array => [$feature => $this->hasFeature($feature)])
                ->all(),
            // PHP_INT_MAX means "unlimited" and is exposed as null.
            'limits' => collect(self::LIMITS)
                ->mapWithKeys(fn (string $key): array => [$key => $limits[$key] === PHP_INT_MAX ? null : $limits[$key]])
                ->all(),
            'token' => $token instanceof PersonalAccessToken ? [
                'name' => $token->name,
                'abilities' => $token->abilities,
                'expires_at' => $token->expires_at?->toIso8601String(),
            ] : null,
        ];
    }
}
