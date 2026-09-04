<?php

namespace App\Modules\MasterAkademik\Domain\Repositories;

interface DosenRepository
{
    public function all(?string $search = null, ?int $jurusanId = null, bool $filterByJurusan = true, int|string $perPage = 'all', ?int $excludeJurusanId = null);

    public function findById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
