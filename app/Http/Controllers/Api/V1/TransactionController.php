<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TransactionDTO;
use App\Exceptions\PlanLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\V1\TransactionResource;
use App\Interfaces\Services\ReceiptServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    /** Relations rendered by TransactionResource. */
    private const array RELATIONS = ['wallet', 'category', 'media'];

    public function __construct(
        protected TransactionServiceInterface $transactionService,
        protected ReceiptServiceInterface $receiptService
    ) {}

    /**
     * Same filters as the web list (month/year, type, status, wallet,
     * category, payment method, search); `meta.summary` holds the totals.
     * viewAny is authorized by TransactionIndexRequest.
     */
    #[OA\Get(
        path: '/api/v1/transactions',
        operationId: 'transactionsIndex',
        description: 'Transações do próprio usuário com os mesmos filtros da tela web. `meta.summary` traz os totais do filtro. A busca (Laravel Scout) procura em descrição, observações, nome da categoria e nome da carteira. Ability: `transactions:read`.',
        summary: 'Listar transações',
        security: [['bearerAuth' => []]],
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(name: 'month', description: '1 a 12 ou "all" (padrão: mês atual).', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: '5'),
            new OA\Parameter(name: 'year', description: '2000 a 2100 (padrão: ano atual).', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), example: 2026),
            new OA\Parameter(name: 'search', description: 'Até 100 caracteres.', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 100), example: 'mercado'),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['income', 'expense'])),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['paid', 'pending', 'overdue'])),
            new OA\Parameter(name: 'wallet_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'payment_method', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pix', 'cash', 'debit_card', 'credit_card', 'boleto', 'transfer', 'auto_debit', 'other'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Página de transações.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Transaction')),
                new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                new OA\Property(property: 'meta', allOf: [
                    new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'summary', description: 'Totais pagos/pendentes de receitas e despesas do filtro.', type: 'object'),
                    ], type: 'object'),
                ]),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function index(TransactionIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        return TransactionResource::collection(
            $this->transactionService->getPaginatedTransactions($filters, $request->perPage())
        )->additional(['meta' => ['summary' => $this->transactionService->getSummary($filters)]]);
    }

    /** JSON or multipart (with a `receipt` file). */
    #[OA\Post(
        path: '/api/v1/transactions',
        operationId: 'transactionsStore',
        description: 'Cria uma transação (JSON) ou uma transação com comprovante (multipart/form-data, campo `receipt`). Com `installments` > 1 cria as parcelas e devolve a primeira com `meta.installments`. A carteira e a categoria precisam ser do próprio usuário (senão 422). Ability: `transactions:write`.',
        summary: 'Criar transação',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: [
            new OA\JsonContent(ref: '#/components/schemas/TransactionInput', example: [
                'wallet_id' => '9f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f',
                'type' => 'expense',
                'amount' => 89.9,
                'description' => 'Mercado Central',
                'date' => '2026-05-10',
                'status' => 'paid',
                'payment_method' => 'pix',
            ]),
            new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/TransactionMultipartInput')),
        ]),
        tags: ['Transações'],
        responses: [
            new OA\Response(response: 201, description: 'Transação criada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
                new OA\Property(property: 'meta', description: 'Somente em parcelamentos.', properties: [
                    new OA\Property(property: 'installments', type: 'integer', example: 3),
                ], type: 'object'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        if (! $request->user()->canCreate('transaction')) {
            throw new PlanLimitException('Limite de transações do mês atingido. Faça upgrade para o plano Profissional.');
        }

        $dto = TransactionDTO::fromArray($request->validated());
        $transaction = $this->transactionService->createTransaction($dto)->load(self::RELATIONS);

        $resource = new TransactionResource($transaction);

        if ($dto->installments > 1) {
            $resource->additional(['meta' => ['installments' => $transaction->installment_total]]);
        }

        return $resource->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/transactions/{transaction}',
        operationId: 'transactionsShow',
        description: 'Detalhe de uma transação própria. Ability: `transactions:read`.',
        summary: 'Detalhar transação',
        security: [['bearerAuth' => []]],
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transação.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function show(string $transaction): TransactionResource
    {
        $model = $this->transactionService->getTransactionById($transaction) ?? abort(404);
        $this->authorize('view', $model);

        return new TransactionResource($model->load(self::RELATIONS));
    }

    #[OA\Put(
        path: '/api/v1/transactions/{transaction}',
        operationId: 'transactionsUpdatePut',
        description: 'Atualiza uma transação própria. Para trocar o comprovante envie multipart/form-data com `receipt` (use POST com `_method=PUT` em clientes que não enviam arquivos em PUT); `remove_receipt=true` remove o atual. Ability: `transactions:write`.',
        summary: 'Atualizar transação (PUT)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: [
            new OA\JsonContent(ref: '#/components/schemas/TransactionInput', example: [
                'wallet_id' => '9f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f',
                'type' => 'expense',
                'amount' => 95.4,
                'description' => 'Mercado Central',
                'date' => '2026-05-10',
                'reason' => 'Valor corrigido',
            ]),
            new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/TransactionMultipartInput')),
        ]),
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transação atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/transactions/{transaction}',
        operationId: 'transactionsUpdatePatch',
        description: 'Mesmo comportamento e validação do PUT. Ability: `transactions:write`.',
        summary: 'Atualizar transação (PATCH)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: [
            new OA\JsonContent(ref: '#/components/schemas/TransactionInput', example: [
                'wallet_id' => '9f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f',
                'type' => 'expense',
                'amount' => 95.4,
                'description' => 'Mercado Central',
                'date' => '2026-05-10',
            ]),
            new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/TransactionMultipartInput')),
        ]),
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transação atualizada.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function update(UpdateTransactionRequest $request, string $transaction): TransactionResource
    {
        $model = $this->transactionService->getTransactionById($transaction) ?? abort(404);
        $this->authorize('update', $model);

        $updated = $this->transactionService->updateTransaction($transaction, TransactionDTO::fromArray($request->validated()));

        return new TransactionResource($updated->load(self::RELATIONS));
    }

    /** `scope=installments` moves every installment of the purchase to the trash. */
    #[OA\Delete(
        path: '/api/v1/transactions/{transaction}',
        operationId: 'transactionsDestroy',
        description: 'Move a transação para a lixeira e reverte o efeito no saldo. `scope=installments` exclui todas as parcelas da compra. Ability: `transactions:write`.',
        summary: 'Excluir transação',
        security: [['bearerAuth' => []]],
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'scope', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'single', enum: ['single', 'installments'])),
            new OA\Parameter(name: 'reason', description: 'Justificativa (até 255 caracteres).', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 255)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Transação excluída.'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function destroy(Request $request, string $transaction): Response
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'in:single,installments'],
        ], [
            'reason.max' => 'A justificativa pode ter no máximo 255 caracteres.',
            'scope.in' => 'Escopo inválido. Use single ou installments.',
        ]);

        $model = $this->transactionService->getTransactionById($transaction) ?? abort(404);
        $this->authorize('delete', $model);

        if (($data['scope'] ?? 'single') === 'installments' && $model->isInstallment()) {
            $this->transactionService->deleteInstallmentGroup($model->installment_group_id, $data['reason'] ?? null);
        } else {
            $this->transactionService->deleteTransaction($transaction, $data['reason'] ?? null);
        }

        return response()->noContent();
    }

    #[OA\Patch(
        path: '/api/v1/transactions/{transaction}/pay',
        operationId: 'transactionsPay',
        description: 'Marca como paga/recebida e atualiza o saldo da carteira. Ability: `transactions:write`.',
        summary: 'Marcar como paga',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'paid_at', description: 'Data do pagamento (hoje ou antes; padrão: hoje).', type: 'string', format: 'date', nullable: true),
            ],
            example: ['paid_at' => '2026-05-10'],
        )),
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transação paga.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function pay(Request $request, string $transaction): TransactionResource
    {
        $data = $request->validate([
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'paid_at.date' => 'Data de pagamento inválida.',
            'paid_at.before_or_equal' => 'A data de pagamento não pode ser futura.',
        ]);

        $this->authorize('pay', $this->transactionService->getTransactionById($transaction) ?? abort(404));

        return new TransactionResource(
            $this->transactionService->markAsPaid($transaction, $data['paid_at'] ?? null)->load(self::RELATIONS)
        );
    }

    #[OA\Patch(
        path: '/api/v1/transactions/{transaction}/unpay',
        operationId: 'transactionsUnpay',
        description: 'Volta a transação para pendente e reverte o saldo da carteira. Ability: `transactions:write`.',
        summary: 'Marcar como pendente',
        security: [['bearerAuth' => []]],
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transação pendente.', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/Transaction'),
            ])),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function unpay(string $transaction): TransactionResource
    {
        $this->authorize('unpay', $this->transactionService->getTransactionById($transaction) ?? abort(404));

        return new TransactionResource($this->transactionService->markAsPending($transaction)->load(self::RELATIONS));
    }

    /** Authenticated receipt download; another user's receipt is a 404. */
    #[OA\Get(
        path: '/api/v1/transactions/{transaction}/receipt',
        operationId: 'transactionsReceipt',
        description: 'Baixa o comprovante (JPG, PNG ou PDF) com o mesmo token. Sem comprovante ou transação de outro usuário: 404. Não existem URLs públicas de comprovantes. Ability: `transactions:read`.',
        summary: 'Baixar comprovante',
        security: [['bearerAuth' => []]],
        tags: ['Transações'],
        parameters: [
            new OA\Parameter(name: 'transaction', description: 'UUID da transação.', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Arquivo do comprovante (cabeçalho Content-Disposition).',
                content: [
                    new OA\MediaType(mediaType: 'application/pdf', schema: new OA\Schema(type: 'string', format: 'binary')),
                    new OA\MediaType(mediaType: 'image/png', schema: new OA\Schema(type: 'string', format: 'binary')),
                    new OA\MediaType(mediaType: 'image/jpeg', schema: new OA\Schema(type: 'string', format: 'binary')),
                ],
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function receipt(string $transaction): StreamedResponse
    {
        $model = $this->transactionService->getTransactionById($transaction) ?? abort(404);
        $this->authorize('receipt', $model);

        return $this->receiptService->response($model) ?? abort(404);
    }
}
