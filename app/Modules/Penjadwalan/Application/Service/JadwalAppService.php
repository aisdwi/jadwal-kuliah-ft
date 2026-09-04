<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use App\Modules\Penjadwalan\Domain\Services\ScheduleConflictChecker;

class JadwalAppService
{
    public function __construct(
        protected JadwalRepository $repo,
        protected KelasKuliahRepository $kelasKuliahRepo,
        protected ?ScheduleConflictChecker $conflictChecker = null,
        protected ?JadwalAssignmentValidator $assignmentValidator = null,
    ) {}

    public function list(?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): array
    {
        return $this->repo->findAll($programStudiId, $semester, $perPage);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "Jadwal $id tidak ditemukan");
    }

    public function findByKelasKuliah(int $kelasKuliahId)
    {
        return $this->repo->findByKelasKuliah($kelasKuliahId);
    }

    public function listByProgramStudi(int $programStudiId, ?int $semester = null): array
    {
        return $this->repo->findByProgramStudi($programStudiId, $semester);
    }

    public function persist(array $data, ?int $id = null)
    {
        if ($id !== null) {
            $this->findById($id);
        }

        return $this->assignmentWriter()->persist($data, $id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repo->delete($id);
    }

    public function deleteGeneratedByProgramStudi(int $programStudiId, ?int $semester = null): int
    {
        return $this->repo->deleteGeneratedByProgramStudi($programStudiId, $semester);
    }

    private function assignmentValidator(): JadwalAssignmentValidator
    {
        return $this->assignmentValidator ??= new JadwalAssignmentValidator($this->repo, $this->conflictChecker);
    }

    private function assignmentWriter(): JadwalAssignmentWriter
    {
        return new JadwalAssignmentWriter($this->repo, $this->kelasKuliahRepo, $this->assignmentValidator());
    }
}
