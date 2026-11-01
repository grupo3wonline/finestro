<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\DataExportStatus;
use App\Interfaces\Repositories\DataExportRepositoryInterface;
use App\Models\DataExport;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DataExportRepository implements DataExportRepositoryInterface
{
    public function create(string $userId, string $disk): DataExport
    {
        return DataExport::query()->create([
            'user_id' => $userId,
            'status' => DataExportStatus::Pending,
            'disk' => $disk,
        ]);
    }

    public function find(string $id): ?DataExport
    {
        return $this->unscoped()->find($id);
    }

    public function findForUser(string $id, string $userId): ?DataExport
    {
        return $this->unscoped()->where('user_id', $userId)->find($id);
    }

    public function hasPending(string $userId): bool
    {
        return $this->unscoped()
            ->where('user_id', $userId)
            ->where('status', DataExportStatus::Pending)
            ->where('created_at', '>', $this->staleCutoff())
            ->exists();
    }

    public function stalePending(): Collection
    {
        return $this->unscoped()
            ->where('status', DataExportStatus::Pending)
            ->where('created_at', '<=', $this->staleCutoff())
            ->get();
    }

    public function listFor(string $userId, int $limit = 10): Collection
    {
        return $this->unscoped()
            ->where('user_id', $userId)
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markReady(DataExport $export, string $path, int $size, CarbonInterface $expiresAt): void
    {
        $export->forceFill([
            'status' => DataExportStatus::Ready,
            'path' => $path,
            'size' => $size,
            'completed_at' => now(),
            'expires_at' => $expiresAt,
        ])->save();
    }

    public function markFailed(DataExport $export): void
    {
        $export->forceFill(['status' => DataExportStatus::Failed])->save();
    }

    public function markExpired(DataExport $export): void
    {
        $export->forceFill(['status' => DataExportStatus::Expired, 'path' => null])->save();
    }

    public function expired(): Collection
    {
        return $this->unscoped()
            ->where('status', DataExportStatus::Ready)
            ->where('expires_at', '<=', now())
            ->get();
    }

    private function staleCutoff(): CarbonInterface
    {
        return now()->subMinutes(DataExport::STALE_AFTER_MINUTES);
    }

    /**
     * Without the BelongsToUser scope, but still hiding soft-deleted rows.
     *
     * @return Builder<DataExport>
     */
    private function unscoped(): Builder
    {
        return DataExport::withoutGlobalScope('user');
    }
}
