<?php

namespace App\Modules\MasterAkademik\Application\Service;

use App\Modules\MasterAkademik\Domain\Repositories\JurusanRepository;

class JurusanAppService
{
    public function __construct(protected JurusanRepository $repo) {}

    public function list(?string $search = null, int|string $perPage = 'all')
    {
        return $this->repo->all($search, $perPage);
    }

    public function findById(int $id)
    {
        $jurusan = $this->repo->findById($id);
        if (!$jurusan) {
            abort(404, "Jurusan dengan ID {$id} tidak ditemukan.");
        }
        return $jurusan;
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = array_intersect_key($data, array_flip(['nama_jurusan']));
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
