<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CategoryDTO;
use App\Exceptions\PlanLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\V1\CategoryResource;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryServiceInterface $categoryService
    ) {}

    /** Optional filter: `type` (income|expense). */
    #[OA\Get(
        path: '/api/v1/categories',
        operationId: 'categoriesIndex',
        description: 'Categorias do próprio usuário, ordenadas por nome e paginadas. Ability: `categories:read`.',
        summary: 'Listar categorias',
        security: [['bearerAuth' => []]],
        tags: ['Categorias'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['income', 'expense'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Página de categorias.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
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
        $this->authorize('viewAny', Category::class);

        return CategoryResource::collection(
            $this->categoryService->getPaginatedCategories($request->only(['type']), $request->perPage())
        );
    }

    #[OA\Post(
        path: '/api/v1/categories',
        operationId: 'categoriesStore',
        description: 'Cria uma categoria. Ability: `categories:write`.',
        summary: 'Criar categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/CategoryInput',
            example: ['name' => 'Alimentação', 'type' => 'expense', 'icon' => 'fa-utensils', 'color' => '#f59e0b'],
        )),
        tags: ['Categorias'],
        responses: [
            new OA\Response(response: 201, description: 'Categoria criada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        if (! $request->user()->canCreate('category')) {
            throw new PlanLimitException('Limite de categorias atingido. Faça upgrade para o plano Profissional.');
        }

        $category = $this->categoryService->createCategory(CategoryDTO::fromArray($request->validated()));

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/v1/categories/{category}',
        operationId: 'categoriesUpdatePut',
        description: 'Atualiza uma categoria própria. Ability: `categories:write`.',
        summary: 'Atualizar categoria (PUT)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/CategoryInput',
            example: ['name' => 'Supermercado', 'type' => 'expense'],
        )),
        tags: ['Categorias'],
        parameters: [
            new OA\Parameter(name: 'category', description: 'UUID da categoria.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categoria atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/categories/{category}',
        operationId: 'categoriesUpdatePatch',
        description: 'Mesmo comportamento e validação do PUT. Ability: `categories:write`.',
        summary: 'Atualizar categoria (PATCH)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/CategoryInput',
            example: ['name' => 'Supermercado', 'type' => 'expense'],
        )),
        tags: ['Categorias'],
        parameters: [
            new OA\Parameter(name: 'category', description: 'UUID da categoria.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categoria atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function update(UpdateCategoryRequest $request, string $category): CategoryResource
    {
        $this->authorize('update', $this->categoryService->getCategoryById($category) ?? abort(404));

        return new CategoryResource(
            $this->categoryService->updateCategory($category, CategoryDTO::fromArray($request->validated()))
        );
    }

    #[OA\Delete(
        path: '/api/v1/categories/{category}',
        operationId: 'categoriesDestroy',
        description: 'Exclui uma categoria própria. Ability: `categories:write`.',
        summary: 'Excluir categoria',
        security: [['bearerAuth' => []]],
        tags: ['Categorias'],
        parameters: [
            new OA\Parameter(name: 'category', description: 'UUID da categoria.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Categoria excluída.'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function destroy(string $category): Response
    {
        $this->authorize('delete', $this->categoryService->getCategoryById($category) ?? abort(404));

        $this->categoryService->deleteCategory($category);

        return response()->noContent();
    }
}
