<?php

namespace App\Modules\MasterAkademik\Application\Service;

use App\Modules\MasterAkademik\Domain\Repositories\MataKuliahRepository;
use App\Modules\Shared\Application\Service\ActivityLogger;

class MataKuliahAppService
{
    private const SUBJECT_TYPE = 'Mata Kuliah';

    public function __construct(
        protected MataKuliahRepository $repo,
        protected ActivityLogger $activityLogger,
    ) {}

    public function list(
        ?string    $search          = null,
        ?int       $programStudiId  = null,
        ?int       $semester        = null,
        ?string    $semesterTipe    = null,
        bool       $filterByJurusan = true,
        int|string $perPage         = 'all',
    ) {
        return $this->repo->all($search, $programStudiId, $semester, $semesterTipe, $filterByJurusan, $perPage);
    }

    public function findById(int $id)
    {
        $mk = $this->repo->findById($id);
        if (!$mk) {
            abort(404, "Mata kuliah dengan ID {$id} tidak ditemukan.");
        }
        return $mk;
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = array_intersect_key($data, array_flip(['kode_mk', 'nama_mk', 'sks', 'semester', 'jurusan_id', 'program_studi_id']));
        if ($id) {
            $mk = $this->repo->update($id, $payload);
            if ($mk) {
                $this->activityLogger->log(
                    action: 'update',
                    subjectType: self::SUBJECT_TYPE,
                    subjectName: $this->formatSubjectName($mk->kode_mk, $mk->nama_mk),
                    description: "Memperbarui mata kuliah {$this->formatSubjectName($mk->kode_mk, $mk->nama_mk)}",
                );
            }

            return $mk;
        }

        $mk = $this->repo->create($payload);
        $this->activityLogger->log(
            action: 'create',
            subjectType: self::SUBJECT_TYPE,
            subjectName: $this->formatSubjectName($mk->kode_mk, $mk->nama_mk),
            description: "Menambahkan mata kuliah {$this->formatSubjectName($mk->kode_mk, $mk->nama_mk)}",
        );

        return $mk;
    }

    public function delete(int $id): void
    {
        $mk = $this->repo->findById($id);
        $this->repo->delete($id);

        if ($mk) {
                $this->activityLogger->log(
                    action: 'delete',
                    subjectType: self::SUBJECT_TYPE,
                    subjectName: $this->formatSubjectName($mk->kode_mk, $mk->nama_mk),
                description: "Menghapus mata kuliah {$this->formatSubjectName($mk->kode_mk, $mk->nama_mk)}",
            );
        }
    }

    private function formatSubjectName(?string $kodeMk, ?string $namaMk): string
    {
        $kode = trim((string) $kodeMk);
        $nama = trim((string) $namaMk);

        if ($kode !== '' && $nama !== '') {
            return "{$kode} - {$nama}";
        }

        return $nama !== '' ? $nama : $kode;
    }
}
