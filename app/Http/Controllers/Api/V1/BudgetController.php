<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\BudgetDTO;
use App\Exceptions\PlanLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Resources\V1\BudgetResource;
use App\Interfaces\Services\BudgetServiceInterface;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Budgets are per month and bounded by the user's categories, so the list
 * is not paginated: `data` + `meta {year, month, overview}`.
 */
class BudgetController extends Controller
{
    public function __construct(
        protected BudgetServiceInterface $budgetService
    ) {}

    #[OA\Get(
        path: '/api/v1/budgets',
        operationId: 'budgetsIndex',
        description: 'Orçamentos do mês com o uso (spent, remaining, percent, health) e `meta.overview` com o planejado × gasto. Sem paginação: um mês tem no máximo um orçamento por categoria. Ability: `budgets:read`.',
        summary: 'Listar orçamentos do mês',
        security: [['bearerAuth' => []]],
        tags: ['Orçamentos'],
        parameters: [
            new OA\Parameter(name: 'year', description: 'Padrão: ano atual.', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 2100, minimum: 2000), example: 2026),
            new OA\Parameter(name: 'month', description: 'Padrão: mês atual.', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 12, minimum: 1), example: 5),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Orçamentos do mês.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Budget')),
                new OA\Property(property: 'meta', properties: [
                    new OA\Property(property: 'year', type: 'integer', example: 2026),
                    new OA\Property(property: 'month', type: 'integer', example: 5),
                    new OA\Property(property: 'overview', properties: [
                        new OA\Property(property: 'planned', type: 'number', example: 2500),
                        new OA\Property(property: 'spent', type: 'number', example: 1320.4),
                        new OA\Property(property: 'remaining', type: 'number', example: 1179.6),
                        new OA\Property(property: 'percent', type: 'number', example: 52.8),
                        new OA\Property(property: 'unbudgeted', description: 'Despesas em categorias sem orçamento.', type: 'number', example: 150),
                    ], type: 'object'),
                ], type: 'object'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Budget::class);

        $data = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ], [
            'month.*' => 'Mês inválido. Use um número de 1 a 12.',
            'year.*' => 'Ano inválido. Use um ano entre 2000 e 2100.',
        ]);

        $year = (int) ($data['year'] ?? now()->year);
        $month = (int) ($data['month'] ?? now()->month);

        return BudgetResource::collection($this->budgetService->getBudgetsWithUsage($year, $month))
            ->additional(['meta' => [
                'year' => $year,
                'month' => $month,
                'overview' => $this->budgetService->getMonthOverview($year, $month),
            ]]);
    }

    #[OA\Post(
        path: '/api/v1/budgets',
        operationId: 'budgetsStore',
        description: 'Cria ou substitui o orçamento da categoria (ou o geral, sem category_id) no mês. Com `repeat_until_december=true` salva também os meses seguintes até dezembro. Ability: `budgets:write`.',
        summary: 'Salvar orçamento',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/BudgetInput',
            example: ['category_id' => '5d6e7f80-1a2b-4c3d-9e8f-7a6b5c4d3e2f', 'year' => 2026, 'month' => 5, 'amount' => 800, 'alert_threshold' => 80],
        )),
        tags: ['Orçamentos'],
        responses: [
            new OA\Response(response: 201, description: 'Orçamento salvo.', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'saved_months', type: 'integer', example: 1),
                    ], type: 'object'),
                    new OA\Property(property: 'message', type: 'string', example: 'Orçamento salvo com sucesso!'),
                ],
            )),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $this->authorize('create', Budget::class);

        $dto = BudgetDTO::fromArray($request->validated());
        $limit = $request->user()->plan_limits['budgets_per_month'];

        if (! $this->budgetService->canAddBudget($dto, $limit)) {
            throw new PlanLimitException('O plano gratuito permite até '.$limit
                .' orçamentos por mês. Faça upgrade para o plano Profissional para criar orçamentos ilimitados.');
        }

        $saved = $this->budgetService->saveBudget($dto, $limit);

        return response()->json([
            'data' => ['saved_months' => $saved],
            'message' => $saved > 1 ? "Orçamento salvo para {$saved} meses." : 'Orçamento salvo com sucesso!',
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/budgets/{budget}',
        operationId: 'budgetsUpdatePut',
        description: 'Altera valor, alerta e observações de um orçamento próprio. category_id, year e month não podem ser enviados. Ability: `budgets:write`.',
        summary: 'Atualizar orçamento (PUT)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/BudgetUpdateInput',
            example: ['amount' => 950, 'alert_threshold' => 90],
        )),
        tags: ['Orçamentos'],
        parameters: [
            new OA\Parameter(name: 'budget', description: 'UUID do orçamento.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Orçamento atualizado.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Budget'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/budgets/{budget}',
        operationId: 'budgetsUpdatePatch',
        description: 'Mesmo comportamento e validação do PUT. Ability: `budgets:write`.',
        summary: 'Atualizar orçamento (PATCH)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/BudgetUpdateInput',
            example: ['amount' => 950],
        )),
        tags: ['Orçamentos'],
        parameters: [
            new OA\Parameter(name: 'budget', description: 'UUID do orçamento.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Orçamento atualizado.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Budget'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function update(StoreBudgetRequest $request, string $budget): BudgetResource
    {
        $model = $this->budgetService->getBudgetById($budget) ?? abort(404);
        $this->authorize('update', $model);

        $updated = $this->budgetService->updateBudget(
            $budget,
            BudgetDTO::fromArray($request->validated()),
            $request->user()->plan_limits['budgets_per_month']
        );

        return new BudgetResource($updated->load('category'));
    }

    #[OA\Delete(
        path: '/api/v1/budgets/{budget}',
        operationId: 'budgetsDestroy',
        description: 'Exclui um orçamento próprio. Ability: `budgets:write`.',
        summary: 'Excluir orçamento',
        security: [['bearerAuth' => []]],
        tags: ['Orçamentos'],
        parameters: [
            new OA\Parameter(name: 'budget', description: 'UUID do orçamento.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Orçamento excluído.'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function destroy(string $budget): Response
    {
        $this->authorize('delete', $this->budgetService->getBudgetById($budget) ?? abort(404));

        $this->budgetService->deleteBudget($budget);

        return response()->noContent();
    }
}
