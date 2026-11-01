<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Usage keys (spent, remaining, percent, health) are present only when the
 * budget was loaded with its month usage (BudgetService::getBudgetsWithUsage).
 *
 * @mixin Budget
 */
#[OA\Schema(
    schema: 'Budget',
    description: 'Orçamento mensal. spent, remaining, percent e health aparecem somente na listagem.',
    properties: [
        new OA\Property(property: 'id', description: 'Identificador (UUID).', type: 'string', format: 'uuid'),
        new OA\Property(property: 'category_id', description: 'null = orçamento geral do mês.', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'category', properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'label', type: 'string', example: 'Alimentação'),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 5),
        new OA\Property(property: 'amount', description: 'Decimal com 2 casas.', type: 'string', example: '800.00'),
        new OA\Property(property: 'alert_threshold', type: 'integer', example: 80),
        new OA\Property(property: 'notes', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'spent', description: 'Somente na listagem.', type: 'number', example: 420.5),
        new OA\Property(property: 'remaining', description: 'Somente na listagem.', type: 'number', example: 379.5),
        new OA\Property(property: 'percent', description: 'Somente na listagem.', type: 'number', example: 52.6),
        new OA\Property(property: 'health', description: 'Somente na listagem.', type: 'string', example: 'ok'),
    ],
    type: 'object',
)]
class BudgetResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $hasUsage = array_key_exists('spent', $this->resource->getAttributes());

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
            'label' => $this->label,
            'year' => $this->year,
            'month' => $this->month,
            'amount' => $this->amount,
            'alert_threshold' => $this->alert_threshold,
            'notes' => $this->notes,
            'spent' => $this->when($hasUsage, fn (): float => (float) $this->resource->getAttribute('spent')),
            'remaining' => $this->when($hasUsage, fn (): float => (float) $this->resource->getAttribute('remaining')),
            'percent' => $this->when($hasUsage, fn (): float => (float) $this->resource->getAttribute('percent')),
            'health' => $this->when($hasUsage, fn (): string => $this->health),
        ];
    }
}
