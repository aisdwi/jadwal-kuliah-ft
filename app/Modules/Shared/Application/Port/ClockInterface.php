<?php

namespace App\Modules\Shared\Application\Port;

interface ClockInterface
{
    public function capture(): ClockTimestamp;
}
