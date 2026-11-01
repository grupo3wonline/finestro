<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\WalletDTO;
use App\Exceptions\PlanLimitException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WalletController as WebWalletController;
use App\Http\Requests\Api\V1\ListRequest;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Http\Resources\V1\WalletResource;
use App\Interfaces\Services\WalletServiceInterface;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class WalletController extends Controller
{
    public function __construct(
        protected WalletServiceInterface $walletService
    ) {}

    /** The user's own wallets (shared wallets are reachable through show). */
    #[OA\Get(
        path: '/api/v1/wallets',
        operationId: 'walletsIndex',
        description: 'Carteiras do próprio usuário, ordenadas por nome e paginadas. Carteiras compartilhadas com você são acessíveis apenas pelo detalhe. Ability: `wallets:read`.',
        summary: 'Listar carteiras',
        security: [['bearerAuth' => []]],
        tags: ['Carteiras'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['checking', 'savings', 'credit', 'investment', 'cash'])),
            new OA\Parameter(name: 'ownership', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['personal', 'business'])),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Página de carteiras.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Wallet')),
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
        $this->authorize('viewAny', Wallet::class);

        return WalletResource::collection($this->walletService->getPaginatedWallets(
            $request->only(['type', 'ownership', 'is_active']),
            $request->perPage()
        ));
    }

    #[OA\Post(
        path: '/api/v1/wallets',
        operationId: 'walletsStore',
        description: 'Cria uma carteira. Ability: `wallets:write`.',
        summary: 'Criar carteira',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/WalletInput',
            example: ['name' => 'Conta Corrente', 'type' => 'checking', 'ownership' => 'personal', 'balance' => 1500.5, 'currency' => 'BRL'],
        )),
        tags: ['Carteiras'],
        responses: [
            new OA\Response(response: 201, description: 'Carteira criada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Wallet'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function store(StoreWalletRequest $request): JsonResponse
    {
        if (! $request->user()->canCreate('wallet')) {
            throw new PlanLimitException(WebWalletController::WALLET_LIMIT_MESSAGE);
        }

        $wallet = $this->walletService->createWallet(WalletDTO::fromArray($request->validated()));

        return (new WalletResource($wallet))->response()->setStatusCode(201);
    }

    /** Owners and accepted shared-wallet members; anyone else gets 404. */
    #[OA\Get(
        path: '/api/v1/wallets/{wallet}',
        operationId: 'walletsShow',
        description: 'Detalhe de uma carteira própria ou compartilhada com você (`is_owner=false`, sem `company_document`). Carteira de outro usuário: 404. Ability: `wallets:read`.',
        summary: 'Detalhar carteira',
        security: [['bearerAuth' => []]],
        tags: ['Carteiras'],
        parameters: [
            new OA\Parameter(name: 'wallet', description: 'UUID da carteira.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carteira.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Wallet'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function show(Request $request, string $wallet): WalletResource
    {
        $model = $this->walletService->getAccessibleWallet($wallet, $request->user()) ?? abort(404);
        $this->authorize('view', $model);

        return new WalletResource($model);
    }

    #[OA\Put(
        path: '/api/v1/wallets/{wallet}',
        operationId: 'walletsUpdatePut',
        description: 'Atualiza uma carteira própria. Aceita corpo parcial: campos omitidos mantêm o valor atual; null limpa company_name, company_document, credit_limit, closing_day e due_day. Ability: `wallets:write`.',
        summary: 'Atualizar carteira (PUT)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/WalletUpdateInput',
            example: ['name' => 'Cartão Principal', 'type' => 'credit', 'credit_limit' => 5000, 'closing_day' => 5, 'due_day' => 15],
        )),
        tags: ['Carteiras'],
        parameters: [
            new OA\Parameter(name: 'wallet', description: 'UUID da carteira.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carteira atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Wallet'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/wallets/{wallet}',
        operationId: 'walletsUpdatePatch',
        description: 'Mesmo comportamento e validação do PUT (corpo parcial; campos omitidos mantêm o valor atual; null limpa os campos de empresa e de cartão). Ability: `wallets:write`.',
        summary: 'Atualizar carteira (PATCH)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            ref: '#/components/schemas/WalletUpdateInput',
            example: ['credit_limit' => 5000, 'closing_day' => 5, 'due_day' => 15],
        )),
        tags: ['Carteiras'],
        parameters: [
            new OA\Parameter(name: 'wallet', description: 'UUID da carteira.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carteira atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Wallet'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function update(UpdateWalletRequest $request, string $wallet): WalletResource
    {
        $model = $this->walletService->getWalletById($wallet) ?? abort(404);
        $this->authorize('update', $model);

        return new WalletResource($this->walletService->updateWallet($wallet, WalletDTO::forUpdate($request->validated())));
    }

    #[OA\Delete(
        path: '/api/v1/wallets/{wallet}',
        operationId: 'walletsDestroy',
        description: 'Move uma carteira própria para a lixeira. Ability: `wallets:write`.',
        summary: 'Excluir carteira',
        security: [['bearerAuth' => []]],
        tags: ['Carteiras'],
        parameters: [
            new OA\Parameter(name: 'wallet', description: 'UUID da carteira.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Carteira excluída.'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function destroy(string $wallet): Response
    {
        $model = $this->walletService->getWalletById($wallet) ?? abort(404);
        $this->authorize('delete', $model);

        $this->walletService->deleteWallet($wallet);

        return response()->noContent();
    }
}
