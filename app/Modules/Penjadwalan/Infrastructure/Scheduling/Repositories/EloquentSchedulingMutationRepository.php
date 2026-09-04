<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\Penjadwalan\Application\Port\SchedulingMutationRepositoryPort;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;

class EloquentSchedulingMutationRepository implements SchedulingMutationRepositoryPort
{
    public function findKelasKuliahById(int $id, array $scope): ?object
    {
        return $this->conflicts()->findKelasKuliahById($id, $scope);
    }

    public function findConflictByRuangan(int $slotId, int $ruanganId, int $ignoreKelasKuliahId): ?object
    {
        return $this->conflicts()->findByRuangan($slotId, $ruanganId, $ignoreKelasKuliahId);
    }

    public function findConflictByKelas(int $kelasId, int $slotId, int $ignoreKelasKuliahId): ?object
    {
        return $this->conflicts()->findByKelas($kelasId, $slotId, $ignoreKelasKuliahId);
    }

    public function findConflictByDosen(array $dosenIds, int $slotId, int $ignoreKelasKuliahId): ?object
    {
        return $this->conflicts()->findByDosen($dosenIds, $slotId, $ignoreKelasKuliahId);
    }

    public function getDosenIdsForKelasKuliah(object $kelasKuliah): array
    {
        if (!$kelasKuliah instanceof KelasKuliahModel) {
            return [];
        }

        $dosenIds = $kelasKuliah->dosens()->pluck('dosen.id')->toArray();
        if (empty($dosenIds) && !empty($kelasKuliah->dosen_id)) {
            $dosenIds = [$kelasKuliah->dosen_id];
        }

        return $dosenIds;
    }

    public function updateSchedule(int $id, ?int $ruanganId, ?int $slotId): bool
    {
        return $this->writer()->updateSchedule($id, $ruanganId, $slotId);
    }

    public function persistGeneratedAssignments(array $assignments): void
    {
        $this->writer()->persistGeneratedAssignments($assignments);
    }

    private function conflicts(): SchedulingConflictLookup
    {
        return new SchedulingConflictLookup();
    }

    private function writer(): SchedulingScheduleWriter
    {
        return new SchedulingScheduleWriter();
    }
}
