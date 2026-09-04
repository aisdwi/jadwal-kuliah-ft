<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface SchedulingSnapshotRepositoryPort
{
    public function hasSnapshotTable(): bool;

    public function createSnapshot(array $scope, ?string $actionType, ?int $userId, array $kelasKuliahPayload, ?int $schedulingRunId = null): ?object;

    public function latestAvailableSnapshot(array $scope, ?string $semesterTipe = null): ?object;

    public function markAsRestored(int $snapshotId): bool;
}
