<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\DataExport;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Every query scopes by user_id explicitly (or works across users on
 * purpose, for the queued job and the purge command), because the
 * BelongsToUser global scope does not apply without an authenticated user.
 */
interface DataExportRepositoryInterface
{
    public function create(string $userId, string $disk): DataExport;

    /**
     * Export by id regardless of owner (queued job only).
     */
    public function find(string $id): ?DataExport;

    public function findForUser(string $id, string $userId): ?DataExport;

    /**
     * Whether the user has a Pending export younger than
     * DataExport::STALE_AFTER_MINUTES (older ones are treated as lost).
     */
    public function hasPending(string $userId): bool;

    /**
     * Pending exports (of every user) older than DataExport::STALE_AFTER_MINUTES.
     *
     * @return Collection<int, DataExport>
     */
    public function stalePending(): Collection;

    /**
     * The user's exports, newest first.
     *
     * @return Collection<int, DataExport>
     */
    public function listFor(string $userId, int $limit = 10): Collection;

    public function markReady(DataExport $export, string $path, int $size, CarbonInterface $expiresAt): void;

    public function markFailed(DataExport $export): void;

    public function markExpired(DataExport $export): void;

    /**
     * Ready exports (of every user) whose download window has ended.
     *
     * @return Collection<int, DataExport>
     */
    public function expired(): Collection;
}
