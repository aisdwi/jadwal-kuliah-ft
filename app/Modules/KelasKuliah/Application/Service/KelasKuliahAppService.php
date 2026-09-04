<?php

namespace App\Modules\KelasKuliah\Application\Service;

use App\Modules\KelasKuliah\Domain\Entities\KelasKuliahOffering;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahStatsRepository;
use App\Modules\KelasKuliah\Domain\Repositories\KelasRepository;
use App\Modules\KelasKuliah\Domain\Services\KelasKuliahDuplicateChecker;
use App\Modules\MasterAkademik\Domain\Repositories\MataKuliahRepository;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KelasKuliahAppService
{
    public function __construct(
        protected KelasKuliahRepository $repo,
        protected KelasKuliahStatsRepository $statsRepo,
        protected KelasRepository $kelasRepo,
        protected MataKuliahRepository $mataKuliahRepo,
        protected ?KelasKuliahDuplicateChecker $duplicateChecker = null,
    ) {}

    public function list(array $filters = [])
    {
        return $this->repo->all($filters);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "KelasKuliah $id tidak ditemukan");
    }

    public function findByKelas(int $kelasId): array
    {
        return $this->repo->findByKelas($kelasId);
    }

    public function stats(?string $semesterTipe = null, ?int $programStudiId = null, ?int $jurusanId = null): array
    {
        return $this->statsRepo->stats($semesterTipe, $programStudiId, $jurusanId);
    }

    public function persist(array $data, ?int $id = null)
    {
        $existing = $id === null ? null : $this->findById($id);

        // Validasi lintas agregat: kelas harus ada
        $this->ensureKelasExists($this->fieldValue($data, 'kelas_id', $existing));
        $this->ensureMataKuliahExists($this->fieldValue($data, 'matakuliah_id', $existing));

        $payload = $this->payload($data);
        $this->ensureTeachingAssignmentIsUnique($payload, $id);
        $this->ensureTeachingTeamIsUnique($payload);

        return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repo->delete($id);
    }

    private function ensureTeachingAssignmentIsUnique(array $payload, ?int $ignoreId): void
    {
        $candidate = new KelasKuliahOffering(
            kelasId: (int) $payload['kelas_id'],
            mataKuliahId: (int) $payload['matakuliah_id'],
            dosenId: (int) $payload['dosen_id'],
            jumlahMahasiswa: (int) ($payload['jumlah_mahasiswa'] ?? 0),
        );

        $duplicate = $this->duplicateChecker()->hasDuplicate(
            $candidate,
            $this->repo->findOfferingsByTeachingAssignment(
                $candidate->kelasId,
                $candidate->mataKuliahId,
                $candidate->dosenId,
                $ignoreId,
            ),
        );

        if ($duplicate) {
            throw new HttpException(422, 'Kelas kuliah dengan kelas, mata kuliah, dan dosen yang sama sudah ada.');
        }
    }

    private function duplicateChecker(): KelasKuliahDuplicateChecker
    {
        return $this->duplicateChecker ??= new KelasKuliahDuplicateChecker();
    }

    private function ensureKelasExists(mixed $kelasId): void
    {
        if (!$kelasId || !$this->kelasRepo->findById((int) $kelasId)) {
            abort(404, "Kelas tidak ditemukan atau tidak valid");
        }
    }

    private function ensureMataKuliahExists(mixed $matakuliahId): void
    {
        if (!$matakuliahId || !$this->mataKuliahRepo->findAccessibleById((int) $matakuliahId)) {
            abort(404, "Mata kuliah tidak ditemukan atau tidak dapat diakses");
        }
    }

    private function fieldValue(array $data, string $key, ?object $existing): mixed
    {
        return array_key_exists($key, $data) ? $data[$key] : $existing?->{$key};
    }

    private function payload(array $data): array
    {
        return Arr::only($data, [
            'dosen_id',
            'matakuliah_id',
            'kelas_id',
            'jumlah_mahasiswa',
            'semester_tipe',
            'dosen_team',
            'dosen_ids',
            'preferred_slot_ids',
            'is_externals',
        ]);
    }

    private function ensureTeachingTeamIsUnique(array $payload): void
    {
        $team = $payload['dosen_team'] ?? null;
        if (!is_array($team)) {
            return;
        }

        $dosenIds = array_values(array_filter(array_map(
            fn ($entry) => is_array($entry) ? ($entry['dosen_id'] ?? null) : null,
            $team,
        )));

        if (count($dosenIds) !== count(array_unique($dosenIds))) {
            throw new HttpException(422, 'Dosen pengampu tidak boleh duplikat. Setiap dosen hanya boleh dipilih satu kali.');
        }
    }
}
