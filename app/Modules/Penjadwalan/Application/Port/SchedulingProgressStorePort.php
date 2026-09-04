<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface SchedulingProgressStorePort
{
    public function get(string $userId): ?array;

    public function put(string $userId, array $payload, int $ttlSeconds): void;

    public function forgetCancel(string $userId): void;

    public function requestCancel(string $userId): void;
}
