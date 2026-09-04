<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use App\Modules\Shared\Application\Service\AcademicScopeQuery;

final class SchedulingKelasKuliahStatsQuery
{
    public function __construct(
        private readonly SchedulingKelasKuliahFilter $filter = new SchedulingKelasKuliahFilter(),
    ) {}

    public function getStats(?string $semesterTipe, array $scope): array
    {
        $jurusanId = $this->jurusanId($scope);
        $roomCount = RuanganModel::count();
        $slotCount = (clone SchedulingSlotQuery::forJurusan($jurusanId))->count();
        $baseQuery = $this->baseClassQuery($semesterTipe, $scope);

        return [
            'slots' => $this->slotStats($jurusanId, $roomCount, $slotCount),
            'classes' => $this->classStats($baseQuery),
        ];
    }

    private function baseClassQuery(?string $semesterTipe, array $scope)
    {
        $filters = $semesterTipe ? ['semester_tipe' => $semesterTipe] : [];

        return $this->filter->apply(
            AcademicScopeQuery::applyToKelasKuliahQuery(KelasKuliahModel::query(), $scope),
            $filters,
        );
    }

    private function slotStats(?int $jurusanId, int $roomCount, int $slotCount): array
    {
        return [
            'total' => $roomCount * $slotCount,
            'sks_1' => $this->slotCountBySks($jurusanId, 1) * $roomCount,
            'sks_2' => $this->slotCountBySks($jurusanId, 2) * $roomCount,
            'sks_3' => $this->slotCountBySks($jurusanId, 3) * $roomCount,
            'sks_4' => $this->slotCountBySks($jurusanId, 4) * $roomCount,
            'unit_count' => $slotCount,
            'room_count' => $roomCount,
        ];
    }

    private function classStats($baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'generated_count' => $this->generatedClassCount($baseQuery),
            'sks_1' => $this->classCountBySks($baseQuery, 1),
            'sks_2' => $this->classCountBySks($baseQuery, 2),
            'sks_3' => $this->classCountBySks($baseQuery, 3),
            'sks_4' => $this->classCountBySks($baseQuery, 4),
        ];
    }

    private function generatedClassCount($baseQuery): int
    {
        return (clone $baseQuery)
            ->whereHas('jadwals', fn ($query) => $query->where('origin', 'generated'))
            ->count();
    }

    private function slotCountBySks(?int $jurusanId, int $sks): int
    {
        return (clone SchedulingSlotQuery::forJurusan($jurusanId))
            ->whereHas('waktu', fn ($q) => $q->where('sks', $sks))
            ->count();
    }

    private function classCountBySks($baseQuery, int $sks): int
    {
        return (clone $baseQuery)
            ->whereHas('matakuliah', fn ($q) => $q->where('sks', $sks))
            ->count();
    }

    private function jurusanId(array $scope): ?int
    {
        return empty($scope['jurusan_id']) ? null : (int) $scope['jurusan_id'];
    }
}
