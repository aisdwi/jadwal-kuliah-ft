<?php

namespace App\Modules\Penjadwalan\Domain\Repositories;

interface JadwalRepository
{
    public function findAll(?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): array;

    public function findById(int $id);

    public function findByKelasKuliah(int $kelasKuliahId);

    public function findByProgramStudi(int $programStudiId, ?int $semester = null): array;

    public function findAssignmentsBySlot(int $slotId, ?int $ignoreKelasKuliahId = null): array;

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;

    public function deleteByKelasKuliah(int $kelasKuliahId): void;

    public function deleteAll(?int $programStudiId = null): int;

    public function deleteGeneratedByProgramStudi(int $programStudiId, ?int $semester = null): int;

    public function findGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): array;

    public function deleteGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): int;

    public function restoreGeneratedSchedules(array $schedules): int;
}
