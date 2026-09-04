<?php

namespace App\Modules\Shared\Infrastructure\Services;

use App\Modules\Shared\Application\Port\TransactionServiceInterface;
use Illuminate\Support\Facades\DB;

class LaravelTransactionService implements TransactionServiceInterface
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
