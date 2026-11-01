<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class TransactionDTO extends Data
{
    /**
     * Chaves que vieram explicitamente na entrada (fromArray).
     * Permite distinguir "campo não enviado" (mantém o valor atual) de
     * "campo enviado vazio" (limpa o valor) — antes, um campo opcional como
     * `notes` ou `due_date` nunca podia ser apagado numa edição.
     *
     * @var array<int, string>
     */
    private array $provided = [];

    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $userId = null,
        public readonly ?string $walletId = null,
        public readonly ?string $categoryId = null,
        public readonly ?string $type = null,
        public readonly ?float $amount = null,
        public readonly ?string $description = null,
        public readonly ?string $date = null,
        public readonly ?bool $isRecurring = null,
        public readonly ?string $recurringFrequency = null,
        public readonly ?string $receiptPath = null,
        public readonly ?string $notes = null,
        // --- Contas a pagar/receber e parcelamento ---
        public readonly ?string $status = null,
        public readonly ?string $dueDate = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $recurringSourceId = null,
        public readonly ?string $installmentGroupId = null,
        public readonly ?int $installmentNumber = null,
        public readonly ?int $installmentTotal = null,
        public readonly ?string $origin = null,
        // --- Não persistidos: instruções para o Service ---
        public readonly int $installments = 1,
        public readonly ?string $reason = null,
        public readonly ?UploadedFile $receipt = null,
        public readonly bool $removeReceipt = false,
    ) {}

    /**
     * Mapa campo do DTO => coluna do banco.
     *
     * @var array<string, string>
     */
    private const array COLUMN_MAP = [
        'userId' => 'user_id',
        'walletId' => 'wallet_id',
        'categoryId' => 'category_id',
        'type' => 'type',
        'status' => 'status',
        'amount' => 'amount',
        'description' => 'description',
        'date' => 'date',
        'dueDate' => 'due_date',
        'paymentMethod' => 'payment_method',
        'isRecurring' => 'is_recurring',
        'recurringFrequency' => 'recurring_frequency',
        'recurringSourceId' => 'recurring_source_id',
        'installmentGroupId' => 'installment_group_id',
        'installmentNumber' => 'installment_number',
        'installmentTotal' => 'installment_total',
        'origin' => 'origin',
        'receiptPath' => 'receipt_path',
        'notes' => 'notes',
    ];

    /**
     * Create DTO from request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $dto = new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'] ?? null,
            walletId: $data['wallet_id'] ?? null,
            categoryId: $data['category_id'] ?? null,
            type: $data['type'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            description: $data['description'] ?? null,
            date: $data['date'] ?? null,
            isRecurring: isset($data['is_recurring']) ? (bool) $data['is_recurring'] : null,
            recurringFrequency: $data['recurring_frequency'] ?? null,
            receiptPath: $data['receipt_path'] ?? null,
            notes: $data['notes'] ?? null,
            status: $data['status'] ?? null,
            dueDate: $data['due_date'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            recurringSourceId: $data['recurring_source_id'] ?? null,
            installmentGroupId: $data['installment_group_id'] ?? null,
            installmentNumber: isset($data['installment_number']) ? (int) $data['installment_number'] : null,
            installmentTotal: isset($data['installment_total']) ? (int) $data['installment_total'] : null,
            origin: $data['origin'] ?? null,
            installments: max(1, (int) ($data['installments'] ?? 1)),
            reason: isset($data['reason']) && trim((string) $data['reason']) !== '' ? trim((string) $data['reason']) : null,
            receipt: ($data['receipt'] ?? null) instanceof UploadedFile ? $data['receipt'] : null,
            removeReceipt: (bool) ($data['remove_receipt'] ?? false),
        );

        $dto->provided = array_keys($data);

        return $dto;
    }

    /**
     * Cria uma cópia do DTO sobrescrevendo alguns campos (imutabilidade).
     *
     * @param  array<string, mixed>  $overrides  chaves no formato de coluna (snake_case)
     */
    public function withOverrides(array $overrides): self
    {
        return self::fromArray(array_merge($this->toArray(includeProvidedNulls: true), [
            'installments' => $this->installments,
            'reason' => $this->reason,
        ], $overrides));
    }

    /**
     * Convert DTO to array for model operations.
     *
     * Por padrão remove valores nulos (comportamento original). Com
     * $includeProvidedNulls = true, mantém como null os campos que foram
     * enviados explicitamente vazios — usado na edição.
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $includeProvidedNulls = false): array
    {
        $result = [];

        foreach (self::COLUMN_MAP as $property => $column) {
            $value = $this->{$property};

            if (! is_null($value)) {
                $result[$column] = $value;
            } elseif ($includeProvidedNulls && in_array($column, $this->provided, true)) {
                $result[$column] = null;
            }
        }

        return $result;
    }
}
