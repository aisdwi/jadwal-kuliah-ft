<?php

namespace App\Modules\MasterAkademik\Application\Service;

use App\Modules\MasterAkademik\Domain\Repositories\ProgramStudiRepository;

class ProgramStudiAppService
{
    public function __construct(protected ProgramStudiRepository $repo) {}

    public function list(?string $search = null, ?int $jurusanId = null, int|string $perPage = 'all')
    {
        return $this->repo->all($search, $jurusanId, $perPage);
    }

    public function findById(int $id)
    {
        $ps = $this->repo->findById($id);
        if (!$ps) {
            abort(404, "Program studi dengan ID {$id} tidak ditemukan.");
        }
        return $ps;
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = array_intersect_key($data, array_flip(['nama_prodi', 'jurusan_id']));
        if ($id) {
            return $this->repo->update($id, $payload);
        }
        return $this->repo->create($payload);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }
}
