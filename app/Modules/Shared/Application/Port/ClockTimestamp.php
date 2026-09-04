<?php

namespace App\Modules\Shared\Application\Port;

final readonly class ClockTimestamp
{
    public function __construct(
        public string $iso8601,
        public string $time,
    ) {}
}
