<?php

namespace App\Modules\Shared\Infrastructure\Services;

use App\Modules\Shared\Application\Port\ActivityLogRepositoryPort;
use Illuminate\Support\Facades\DB;

class LaravelActivityLogRepository implements ActivityLogRepositoryPort
{
    public function insert(array $data): void
    {
        DB::table('activity_logs')->insert($data);
    }
}
