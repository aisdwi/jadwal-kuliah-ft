<?php

namespace App\Modules\Resource\Application\Service;

use App\Modules\Resource\Domain\Repositories\HariRepository;
use Illuminate\Support\Arr;

class HariAppService
{
    public function __construct(protected HariRepository $repo) {}

    public function list(?string $search = null): \Illuminate\Support\Collection
    {
        return $this->repo->all($search);
    }

    public function findById(int $id)
    {
        return $this->repo->findById($id) ?? abort(404, "Hari $id tidak ditemukan");
    }

    public function persist(array $data, ?int $id = null)
    {
        $payload = Arr::only($data, ['nama_hari']);
        return $id ? $this->repo->update($id, $payload) : $this->repo->create($payload);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }
}
