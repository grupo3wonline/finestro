<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Interfaces\Repositories\DataExportRepositoryInterface;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Models\DataExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Builds a personal data export zip off the request cycle. Only the export id
 * is serialized, so no personal data sits in the jobs table.
 */
class GeneratePersonalDataExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $exportId) {}

    public function handle(DataExportRepositoryInterface $exports, DataExportServiceInterface $service): void
    {
        $export = $exports->find($this->exportId);

        if ($export instanceof DataExport) {
            $service->build($export);
        }
    }

    /**
     * Called by the queue when the job fails outside build()'s own error
     * handling (timeout, exception before build, max attempts): never leave
     * the export Pending.
     */
    public function failed(?Throwable $exception): void
    {
        app(DataExportServiceInterface::class)->markFailedIfPending($this->exportId);
    }
}
