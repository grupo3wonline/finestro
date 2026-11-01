<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Category
 */
#[OA\Schema(
    schema: 'Category',
    description: 'Categoria de receita ou despesa.',
    properties: [
        new OA\Property(property: 'id', description: 'Identificador (UUID).', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
        new OA\Property(property: 'type', type: 'string', example: 'expense', enum: ['income', 'expense']),
        new OA\Property(property: 'icon', type: 'string', example: 'fa-utensils', nullable: true),
        new OA\Property(property: 'color', type: 'string', example: '#f59e0b', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'icon' => $this->icon,
            'color' => $this->color,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
