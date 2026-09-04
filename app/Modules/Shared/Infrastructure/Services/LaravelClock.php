<?php

namespace App\Modules\Shared\Infrastructure\Services;

use App\Modules\Shared\Application\Port\ClockInterface;
use App\Modules\Shared\Application\Port\ClockTimestamp;

class LaravelClock implements ClockInterface
{
    public function capture(): ClockTimestamp
    {
        $now = now();

        return new ClockTimestamp(
            $now->toISOString(),
            $now->format('H:i:s'),
        );
    }
}
