<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Explicit allowlist. `company_document` is only sent to the owner (a shared
 * wallet member never receives it); user_id/deleted_at are never sent.
 *
 * @mixin Wallet
 */
#[OA\Schema(
    schema: 'Wallet',
    description: 'Carteira (conta financeira).',
    properties: [
        new OA\Property(property: 'id', description: 'Identificador (UUID).', type: 'string', format: 'uuid', example: '9f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f'),
        new OA\Property(property: 'name', type: 'string', example: 'Conta Corrente'),
        new OA\Property(property: 'type', type: 'string', example: 'checking', enum: ['checking', 'savings', 'credit', 'investment', 'cash']),
        new OA\Property(property: 'ownership', type: 'string', example: 'personal', enum: ['personal', 'business']),
        new OA\Property(property: 'company_name', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'company_document', description: 'Somente para o dono da carteira.', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'balance', description: 'Decimal com 2 casas.', type: 'string', example: '1500.50'),
        new OA\Property(property: 'credit_limit', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'closing_day', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'due_day', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'currency', type: 'string', example: 'BRL'),
        new OA\Property(property: 'color', type: 'string', example: '#10b981', nullable: true),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-building-columns', nullable: true),
        new OA\Property(property: 'description', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_shared', type: 'boolean', example: false),
        new OA\Property(property: 'is_owner', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-05-10T14:30:00-03:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-05-10T14:30:00-03:00'),
    ],
    type: 'object',
)]
class WalletResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user instanceof User && $this->isOwnedBy($user);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'ownership' => $this->ownership,
            'company_name' => $this->company_name,
            'company_document' => $this->when($isOwner, fn () => $this->company_document),
            'balance' => $this->balance,
            'credit_limit' => $this->credit_limit,
            'closing_day' => $this->closing_day,
            'due_day' => $this->due_day,
            'currency' => $this->currency,
            'color' => $this->color,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_shared' => (bool) $this->is_shared,
            'is_owner' => $isOwner,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
