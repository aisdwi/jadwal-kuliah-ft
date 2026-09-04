<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\Penjadwalan\Application\Port\SchedulingQueryRepositoryPort;

class EloquentSchedulingQueryRepository implements SchedulingQueryRepositoryPort
{
    public function getKelasKuliahList(array $filters, mixed $perPage): mixed
    {
        return (new SchedulingKelasKuliahListQuery())->getList($filters, $perPage);
    }

    public function findKelasKuliahDetail(int $id, array $scope): ?object
    {
        return (new SchedulingKelasKuliahListQuery())->findDetail($id, $scope);
    }

    public function getKelasKuliahStats(?string $semesterTipe, array $scope): array
    {
        return (new SchedulingKelasKuliahStatsQuery())->getStats($semesterTipe, $scope);
    }

    public function getGenerationDataset(array $scope, ?string $semesterTipe): array
    {
        return (new SchedulingGenerationDatasetQuery())->getDataset($scope, $semesterTipe);
    }
}
