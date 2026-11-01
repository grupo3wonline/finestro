<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Goal
 */
#[OA\Schema(
    schema: 'Goal',
    description: 'Meta financeira com progresso.',
    properties: [
        new OA\Property(property: 'id', description: 'Identificador (UUID).', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Reserva de emergência'),
        new OA\Property(property: 'description', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'target_amount', description: 'Decimal com 2 casas.', type: 'string', example: '10000.00'),
        new OA\Property(property: 'current_amount', type: 'string', example: '2500.00'),
        new OA\Property(property: 'progress', description: 'Percentual concluído.', type: 'number', example: 25),
        new OA\Property(property: 'deadline', type: 'string', format: 'date', example: '2027-12-31', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-piggy-bank', nullable: true),
        new OA\Property(property: 'color', type: 'string', example: '#6366f1', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class GoalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'target_amount' => $this->target_amount,
            'current_amount' => $this->current_amount,
            'progress' => (float) $this->progress,
            'deadline' => $this->deadline?->toDateString(),
            'status' => $this->status,
            'icon' => $this->icon,
            'color' => $this->color,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
