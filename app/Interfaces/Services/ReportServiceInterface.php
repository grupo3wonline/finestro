<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\DTOs\ReportFilterData;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Report data for the screen, print, PDF, e-mail and exports.
 *
 * $user must be the authenticated user: transactions are read through the
 * per-user scope and the cached summary is keyed by $user->id.
 */
interface ReportServiceInterface
{
    /**
     * Full report view model (transactions list, aggregates, comparison with
     * the previous period and the applied filters).
     *
     * @return array<string, mixed>
     */
    public function build(User $user, ReportFilterData $filters): array;

    /**
     * Scalar/array summary used by the report e-mail (no Eloquent models).
     *
     * @return array<string, mixed>
     */
    public function summaryFor(User $user, ReportFilterData $filters): array;

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    public function exportCsv(Collection $transactions): string;

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    public function exportXlsx(Collection $transactions): string;
}
