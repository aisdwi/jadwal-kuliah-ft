<?php

namespace App\Modules\Shared\Application\Port;

interface TransactionServiceInterface
{
    public function run(callable $callback): mixed;
}
