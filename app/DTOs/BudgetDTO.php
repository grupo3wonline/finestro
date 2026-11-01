<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class BudgetDTO extends Data
{
    public function __construct(
        public readonly ?string $categoryId = null,
        public readonly ?int $year = null,
        public readonly ?int $month = null,
        public readonly ?float $amount = null,
        public readonly ?int $alertThreshold = null,
        public readonly ?string $notes = null,
        /** Não persistido: replica o orçamento até dezembro do mesmo ano. */
        public readonly bool $repeatUntilDecember = false,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: ! empty($data['category_id']) ? $data['category_id'] : null,
            year: isset($data['year']) ? (int) $data['year'] : null,
            month: isset($data['month']) ? (int) $data['month'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            alertThreshold: isset($data['alert_threshold']) ? (int) $data['alert_threshold'] : null,
            notes: $data['notes'] ?? null,
            repeatUntilDecember: (bool) ($data['repeat_until_december'] ?? false),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'year' => $this->year,
            'month' => $this->month,
            'amount' => $this->amount,
            'alert_threshold' => $this->alertThreshold ?? 80,
            'notes' => $this->notes,
        ];
    }
}
