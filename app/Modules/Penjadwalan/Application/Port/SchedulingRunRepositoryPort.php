<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface SchedulingRunRepositoryPort
{
    public function createQueued(string $userId, ?string $semesterTipe, array $scope, array $params): ?int;

    public function markProcessing(?int $runId): void;

    public function markFinished(?int $runId, string $status, array $result): void;

    public function markFailed(?int $runId, string $message): void;
}
