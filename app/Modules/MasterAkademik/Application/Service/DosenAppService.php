<?php

namespace App\Modules\MasterAkademik\Application\Service;

use App\Modules\MasterAkademik\Domain\Repositories\DosenRepository;

class DosenAppService
{
    public function __construct(protected DosenRepository $repo) {}

    public function list(?string $search = null, ?int $jurusanId = null, bool $filterByJurusan = true, int|string $perPage = 'all', ?int $excludeJurusanId = null)
    {
        return $this->repo->all($search, $jurusanId, $filterByJurusan, $perPage, $excludeJurusanId);
    }

    public function findById(int $id)
    {
        $dosen = $this->repo->findById($id);
        if (!$dosen) {
            abort(404, "Dosen dengan ID {$id} tidak ditemukan.");
        }
        return $dosen;
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = array_intersect_key($data, array_flip(['nip', 'nama_lengkap', 'inisial', 'jurusan_id']));
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
