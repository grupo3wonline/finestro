<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use Closure;

/**
 * Per-user cache for aggregates. Every key contains the user id and a
 * per-user version, so flushing one user never touches another user's data.
 */
interface UserCacheInterface
{
    /**
     * Return the cached value for ($userId, $name, $params) or compute and
     * store it. With a null user the callback runs uncached.
     *
     * The callback must return plain arrays/scalars (cache stores do not
     * unserialize objects).
     *
     * @template TValue
     *
     * @param  array<string, mixed>  $params
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(?string $userId, string $name, array $params, Closure $callback): mixed;

    /**
     * Invalidate every cached entry of the user (bumps the version key).
     */
    public function flush(string $userId): void;
}
