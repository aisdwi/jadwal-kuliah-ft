<?php

namespace App\Modules\KelasKuliah\Application\Service;

use App\Modules\KelasKuliah\Domain\Repositories\KelasRepository;
use App\Modules\MasterAkademik\Domain\Repositories\ProgramStudiRepository;
use App\Modules\Shared\Application\Service\AcademicScope;
use Illuminate\Support\Arr;

class KelasAppService
{
    public function __construct(
        protected KelasRepository $repo,
        protected ProgramStudiRepository $programStudiRepo,
    ) {}

    public function list(?string $search = null, ?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all')
    {
        return $this->repo->all($search, $programStudiId, $semester, $perPage);
    }

    public function listByProdi(int $programStudiId, ?int $semester = null): array
    {
        return $this->repo->findByProgramStudi($programStudiId, $semester);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "Kelas $id tidak ditemukan");
    }

    public function persist(array $data, ?int $id = null)
    {
        if ($id !== null) {
            $this->findById($id);
        }

        $payload = Arr::only($data, ['nama_kelas', 'semester', 'program_studi_id', 'jurusan_id']);

        $scope = AcademicScope::fromUser(auth()->user());
        $programStudi = $this->programStudiRepo->findAccessibleById((int) ($payload['program_studi_id'] ?? 0));

        if (!$programStudi) {
            abort(404, 'Program studi tidak ditemukan atau tidak dapat diakses');
        }

        if (!empty($scope['restrict_by_jurusan'])) {
            $payload['jurusan_id'] = (int) $scope['jurusan_id'];
        }

        return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repo->delete($id);
    }
}
