<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\UserCacheInterface;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Version-key invalidation (works on every store, including `database`,
 * which has no tags):
 *
 *   finestro:user:{userId}:version                       -> random version
 *   finestro:user:{userId}:v{version}:{name}:{md5(params)} -> cached value
 *
 * flush() writes a new version, so old entries are never read again and
 * expire after the TTL.
 */
class UserCache implements UserCacheInterface
{
    public function remember(?string $userId, string $name, array $params, Closure $callback): mixed
    {
        if ($userId === null || $userId === '') {
            return $callback();
        }

        $ttl = max(1, (int) config('cache.user_ttl', 300));

        return Cache::remember($this->key($userId, $name, $params), $ttl, $callback);
    }

    public function flush(string $userId): void
    {
        Cache::forever($this->versionKey($userId), Str::random(12));
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function key(string $userId, string $name, array $params): string
    {
        ksort($params);

        $version = (string) Cache::get($this->versionKey($userId), '0');

        return 'finestro:user:'.$userId.':v'.$version.':'.$name.':'.md5((string) json_encode($params));
    }

    protected function versionKey(string $userId): string
    {
        return 'finestro:user:'.$userId.':version';
    }
}
