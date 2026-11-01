<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use App\Exceptions\DataExportException;
use App\Models\DataExport;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Personal data export (LGPD art. 18 II/V, GDPR arts. 15/20, CCPA right to know).
 */
interface DataExportServiceInterface
{
    /**
     * Queue a new export for the user.
     *
     * @throws DataExportException when an export is already being prepared
     */
    public function request(User $user): DataExport;

    /**
     * Build the zip (data.json + one CSV per section) and mark it ready, or
     * mark it failed. Runs in the queued job.
     */
    public function build(DataExport $export): void;

    /**
     * Temporary signed download link, valid until the export expires.
     */
    public function downloadUrl(DataExport $export): string;

    /**
     * Stream the zip to its owner (404 for others, 410 once expired).
     */
    public function download(DataExport $export, User $user): StreamedResponse;

    /**
     * Delete the files of expired exports; returns how many were purged.
     */
    public function purgeExpired(): int;

    /**
     * Mark Pending exports whose job was lost or killed as Failed; returns how many.
     */
    public function failStale(): int;

    /**
     * Mark one export Failed if it is still Pending (queued job failure hook).
     */
    public function markFailedIfPending(string $exportId): void;
}
