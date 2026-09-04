<?php

namespace App\Modules\Shared\Infrastructure\Services;

use App\Modules\Shared\Application\Port\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;

class LaravelCacheService implements CacheServiceInterface
{
    public function get(string $key): mixed
    {
        return Cache::store('database')->get($key);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::store('database')->put($key, $value, $ttl ?? 3600);
    }

    public function forget(string $key): bool
    {
        return Cache::store('database')->forget($key);
    }

    public function has(string $key): bool
    {
        return Cache::store('database')->has($key);
    }
}
