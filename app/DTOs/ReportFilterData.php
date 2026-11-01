<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

/**
 * Validated report filters (period, wallet, type and status).
 */
class ReportFilterData extends Data
{
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $walletId = null,
        public readonly ?string $type = null,
        public readonly ?string $status = null,
    ) {}

    /**
     * Build from ReportRequest::validated(); missing dates default to the
     * current month.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, ?string $wallet): self
    {
        return new self(
            startDate: $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString(),
            endDate: $validated['end_date'] ?? Carbon::now()->endOfMonth()->toDateString(),
            walletId: filled($wallet) ? $wallet : null,
            type: $validated['type'] ?? null,
            status: $validated['status'] ?? null,
        );
    }

    /**
     * Parameters that identify a cached report summary.
     *
     * @return array<string, string|null>
     */
    public function toCacheParams(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'wallet_id' => $this->walletId,
            'type' => $this->type,
            'status' => $this->status,
        ];
    }
}
