<?php

declare(strict_types=1);

namespace App\Traits;

use App\Interfaces\Services\UserCacheInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invalidates the owner's cached aggregates (UserCacheInterface) whenever
 * the model is written through Eloquent. `increment()`/`decrement()` fire
 * `updated` too; raw query-builder writes do not (they expire after the TTL).
 */
trait FlushesUserCache
{
    protected static function bootFlushesUserCache(): void
    {
        $flush = static function (Model $model): void {
            $userId = $model->getAttribute('user_id');

            if (filled($userId)) {
                app(UserCacheInterface::class)->flush((string) $userId);
            }
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored($flush);
            static::forceDeleted($flush);
        }
    }
}
