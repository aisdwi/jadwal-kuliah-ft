<?php

namespace App\Modules\Shared\Application\Port;

interface CacheServiceInterface
{
    public function get(string $key): mixed;

    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    public function forget(string $key): bool;

    public function has(string $key): bool;
}
