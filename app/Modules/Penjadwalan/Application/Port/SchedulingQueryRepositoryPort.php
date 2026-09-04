<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface SchedulingQueryRepositoryPort
{
    public function getKelasKuliahList(array $filters, mixed $perPage): mixed;

    public function findKelasKuliahDetail(int $id, array $scope): ?object;

    public function getKelasKuliahStats(?string $semesterTipe, array $scope): array;

    public function getGenerationDataset(array $scope, ?string $semesterTipe): array;
}
