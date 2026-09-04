<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Services;

use App\Modules\Penjadwalan\Application\Port\SchedulingJobDispatcherPort;
use App\Jobs\ProcessGeneticAlgorithm;

class LaravelSchedulingJobDispatcher implements SchedulingJobDispatcherPort
{
    public function dispatchRunGeneration(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): void
    {
        ProcessGeneticAlgorithm::dispatch($programStudiId, $semesterTipe, $scope, $params)
            ->onConnection(config('queue.default', 'database'));
    }
}
