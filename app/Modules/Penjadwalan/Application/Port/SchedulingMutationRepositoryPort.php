<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface SchedulingMutationRepositoryPort
{
    public function findKelasKuliahById(int $id, array $scope): ?object;

    public function findConflictByRuangan(int $slotId, int $ruanganId, int $ignoreKelasKuliahId): ?object;

    public function findConflictByKelas(int $kelasId, int $slotId, int $ignoreKelasKuliahId): ?object;

    public function findConflictByDosen(array $dosenIds, int $slotId, int $ignoreKelasKuliahId): ?object;

    public function getDosenIdsForKelasKuliah(object $kelasKuliah): array;

    public function updateSchedule(int $id, ?int $ruanganId, ?int $slotId): bool;

    public function persistGeneratedAssignments(array $assignments): void;
}
