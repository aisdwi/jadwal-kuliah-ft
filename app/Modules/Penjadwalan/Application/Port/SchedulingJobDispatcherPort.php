<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface SchedulingJobDispatcherPort
{
    public function dispatchRunGeneration(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): void;
}
