<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Interfaces\Services\ReportServiceInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    public function __construct(
        protected ReportServiceInterface $reports
    ) {}

    /**
     * Period summary (totals, status and category breakdown) of the user's
     * own transactions. Route middleware: permission:reports.view.
     */
    #[OA\Get(
        path: '/api/v1/reports/summary',
        operationId: 'reportsSummary',
        description: 'Totais de receitas e despesas, fixo × variável, situação e despesas por categoria do período, somente com as transações do próprio usuário. Ability: `reports:read`; permissão do papel: `reports.view`.',
        summary: 'Resumo financeiro do período',
        security: [['bearerAuth' => []]],
        tags: ['Relatórios'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-05-01'),
            new OA\Parameter(name: 'end_date', description: 'Igual ou posterior a start_date.', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-05-31'),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['income', 'expense'])),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['paid', 'pending'])),
            new OA\Parameter(name: 'wallet_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resumo.', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'startDate', type: 'string', format: 'date'),
                        new OA\Property(property: 'endDate', type: 'string', format: 'date'),
                        new OA\Property(property: 'totalIncome', type: 'number', example: 5200),
                        new OA\Property(property: 'totalExpenses', type: 'number', example: 3100.4),
                        new OA\Property(property: 'balance', type: 'number', example: 2099.6),
                        new OA\Property(property: 'fixedVsVariable', type: 'object'),
                        new OA\Property(property: 'statusBreakdown', type: 'object'),
                        new OA\Property(property: 'expenseByCategory', type: 'object'),
                        new OA\Property(property: 'transactionCount', type: 'integer', example: 42),
                    ], type: 'object'),
                ],
            )),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function summary(ReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reports->summaryFor($request->user(), $request->filters()),
        ]);
    }
}
