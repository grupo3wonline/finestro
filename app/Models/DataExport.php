<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataExportStatus;
use App\Traits\BelongsToUser;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Database\Factories\DataExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A personal data export zip (data.json + one CSV per section).
 *
 * @property string $id
 * @property string $user_id
 * @property DataExportStatus $status
 * @property string $disk
 * @property string|null $path
 * @property int|null $size
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 */
class DataExport extends Model
{
    /** @use HasFactory<DataExportFactory> */
    use BelongsToUser, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'disk',
        'path',
        'size',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataExportStatus::class,
            'size' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * A Pending export older than this is treated as lost (killed worker,
     * timeout, worker never started): it no longer blocks new requests and
     * privacy:purge-exports marks it Failed.
     */
    public const int STALE_AFTER_MINUTES = 60;

    public function isStale(): bool
    {
        return $this->status === DataExportStatus::Pending
            && $this->created_at->lte(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /**
     * Pending and still within the time a real build can take.
     */
    public function isInProgress(): bool
    {
        return $this->status === DataExportStatus::Pending && ! $this->isStale();
    }

    public function isExpired(): bool
    {
        return $this->status === DataExportStatus::Expired
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function isDownloadable(): bool
    {
        return $this->status === DataExportStatus::Ready && ! $this->isExpired();
    }
}
