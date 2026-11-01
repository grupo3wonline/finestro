<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\GoalDTO;
use App\Exceptions\PlanLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListRequest;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\V1\GoalResource;
use App\Interfaces\Services\GoalServiceInterface;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class GoalController extends Controller
{
    public function __construct(
        protected GoalServiceInterface $goalService
    ) {}

    #[OA\Get(
        path: '/api/v1/goals',
        operationId: 'goalsIndex',
        description: 'Metas do próprio usuário, ordenadas pelo prazo e paginadas. Ability: `goals:read`.',
        summary: 'Listar metas',
        security: [['bearerAuth' => []]],
        tags: ['Metas'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'active'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Página de metas.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Goal')),
                new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function index(ListRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Goal::class);

        return GoalResource::collection(
            $this->goalService->getPaginatedGoals($request->only(['status']), $request->perPage())
        );
    }

    #[OA\Post(
        path: '/api/v1/goals',
        operationId: 'goalsStore',
        description: 'Cria uma meta. Ability: `goals:write`.',
        summary: 'Criar meta',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/GoalInput',
            example: ['name' => 'Reserva de emergência', 'target_amount' => 10000, 'current_amount' => 2500, 'deadline' => '2027-12-31'],
        )),
        tags: ['Metas'],
        responses: [
            new OA\Response(response: 201, description: 'Meta criada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Goal'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function store(StoreGoalRequest $request): JsonResponse
    {
        if (! $request->user()->canCreate('goal')) {
            throw new PlanLimitException('Limite de metas atingido. Faça upgrade para o plano Profissional.');
        }

        $goal = $this->goalService->createGoal(GoalDTO::fromArray($request->validated()));

        return (new GoalResource($goal))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/goals/{goal}',
        operationId: 'goalsShow',
        description: 'Detalhe de uma meta própria. Ability: `goals:read`.',
        summary: 'Detalhar meta',
        security: [['bearerAuth' => []]],
        tags: ['Metas'],
        parameters: [
            new OA\Parameter(name: 'goal', description: 'UUID da meta.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Meta.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Goal'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function show(string $goal): GoalResource
    {
        $model = $this->goalService->getGoalById($goal) ?? abort(404);
        $this->authorize('view', $model);

        return new GoalResource($model);
    }

    #[OA\Put(
        path: '/api/v1/goals/{goal}',
        operationId: 'goalsUpdatePut',
        description: 'Atualiza uma meta própria. Ability: `goals:write`.',
        summary: 'Atualizar meta (PUT)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/GoalInput',
            example: ['name' => 'Reserva de emergência', 'target_amount' => 12000, 'current_amount' => 3000],
        )),
        tags: ['Metas'],
        parameters: [
            new OA\Parameter(name: 'goal', description: 'UUID da meta.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Meta atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Goal'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/goals/{goal}',
        operationId: 'goalsUpdatePatch',
        description: 'Mesmo comportamento e validação do PUT. Ability: `goals:write`.',
        summary: 'Atualizar meta (PATCH)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/GoalInput',
            example: ['name' => 'Reserva de emergência', 'target_amount' => 12000, 'current_amount' => 3000],
        )),
        tags: ['Metas'],
        parameters: [
            new OA\Parameter(name: 'goal', description: 'UUID da meta.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Meta atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Goal'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function update(UpdateGoalRequest $request, string $goal): GoalResource
    {
        $this->authorize('update', $this->goalService->getGoalById($goal) ?? abort(404));

        return new GoalResource($this->goalService->updateGoal($goal, GoalDTO::fromArray($request->validated())));
    }

    #[OA\Delete(
        path: '/api/v1/goals/{goal}',
        operationId: 'goalsDestroy',
        description: 'Exclui uma meta própria. Ability: `goals:write`.',
        summary: 'Excluir meta',
        security: [['bearerAuth' => []]],
        tags: ['Metas'],
        parameters: [
            new OA\Parameter(name: 'goal', description: 'UUID da meta.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Meta excluída.'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function destroy(string $goal): Response
    {
        $this->authorize('delete', $this->goalService->getGoalById($goal) ?? abort(404));

        $this->goalService->deleteGoal($goal);

        return response()->noContent();
    }
}
