<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Subscription status. Never exposes stripe_* identifiers.
 *
 * @mixin Subscription
 */
#[OA\Schema(
    schema: 'Subscription',
    description: 'Situação da assinatura (sem identificadores do Stripe).',
    properties: [
        new OA\Property(property: 'plan', description: 'Plano atual.', type: 'string', example: 'professional', enum: ['free', 'professional']),
        new OA\Property(property: 'status', description: 'null quando o usuário nunca assinou.', type: 'string', example: 'active', nullable: true, enum: ['active', 'trialing', 'past_due', 'unpaid', 'canceled', 'incomplete', 'incomplete_expired']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Ativa'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'cancel_at_period_end', type: 'boolean', example: false),
        new OA\Property(property: 'current_period_start', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'current_period_end', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'days_remaining', type: 'integer', example: 21, nullable: true),
        new OA\Property(property: 'canceled_at', type: 'string', format: 'date-time', example: null, nullable: true),
    ],
    type: 'object',
)]
class SubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'plan' => $this->plan,
            'status' => $this->status?->value,
            'status_label' => $this->status_label,
            'is_active' => $this->isActive(),
            'cancel_at_period_end' => (bool) $this->cancel_at_period_end,
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'days_remaining' => $this->days_remaining,
            'canceled_at' => $this->canceled_at?->toIso8601String(),
        ];
    }
}
