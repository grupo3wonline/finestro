<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Explicit allowlist. Receipts are exposed only as `has_receipt` plus the
 * authenticated download URL: never receipt_path, media rows or disk paths.
 *
 * @mixin Transaction
 */
#[OA\Schema(
    schema: 'Transaction',
    description: 'Receita ou despesa. O comprovante aparece apenas como has_receipt e receipt_url (download autenticado).',
    properties: [
        new OA\Property(property: 'id', description: 'Identificador (UUID).', type: 'string', format: 'uuid', example: '0b6f1e2a-3c4d-4e5f-8a9b-1c2d3e4f5a6b'),
        new OA\Property(property: 'type', type: 'string', example: 'expense', enum: ['income', 'expense']),
        new OA\Property(property: 'status', type: 'string', example: 'paid', enum: ['paid', 'pending']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Pago'),
        new OA\Property(property: 'amount', description: 'Decimal com 2 casas.', type: 'string', example: '89.90'),
        new OA\Property(property: 'description', type: 'string', example: 'Mercado Central'),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-05-10'),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', example: null, nullable: true),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-05-10T00:00:00-03:00', nullable: true),
        new OA\Property(property: 'payment_method', type: 'string', example: 'pix', nullable: true),
        new OA\Property(property: 'payment_method_label', type: 'string', example: 'Pix', nullable: true),
        new OA\Property(property: 'is_recurring', type: 'boolean', example: false),
        new OA\Property(property: 'recurring_frequency', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'installment_group_id', type: 'string', format: 'uuid', example: null, nullable: true),
        new OA\Property(property: 'installment_number', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'installment_total', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
        new OA\Property(property: 'notes', type: 'string', example: null, nullable: true),
        new OA\Property(property: 'has_receipt', type: 'boolean', example: true),
        new OA\Property(property: 'receipt_url', description: 'Download autenticado (mesmo token); null sem comprovante.', type: 'string', format: 'uri', example: 'http://finestro.test/api/v1/transactions/0b6f1e2a-3c4d-4e5f-8a9b-1c2d3e4f5a6b/receipt', nullable: true),
        new OA\Property(property: 'wallet_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'wallet', properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string', example: 'Conta Corrente'),
        ], type: 'object'),
        new OA\Property(property: 'category_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'category', properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
            new OA\Property(property: 'type', type: 'string', example: 'expense'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $hasReceipt = $this->hasReceipt();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method_label,
            'is_recurring' => (bool) $this->is_recurring,
            'recurring_frequency' => $this->recurring_frequency,
            'installment_group_id' => $this->installment_group_id,
            'installment_number' => $this->installment_number,
            'installment_total' => $this->installment_total,
            'is_overdue' => $this->is_overdue,
            'notes' => $this->notes,
            'has_receipt' => $hasReceipt,
            'receipt_url' => $hasReceipt ? route('api.v1.transactions.receipt', $this->id) : null,
            'wallet_id' => $this->wallet_id,
            'wallet' => $this->whenLoaded('wallet', fn (Wallet $wallet): array => [
                'id' => $wallet->id,
                'name' => $wallet->name,
            ]),
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
