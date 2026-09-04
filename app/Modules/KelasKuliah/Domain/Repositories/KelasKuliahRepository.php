<?php

namespace App\Modules\KelasKuliah\Domain\Repositories;

use App\Modules\Shared\Domain\PagedResult;

interface KelasKuliahRepository
{
    public function all(array $filters = []): PagedResult;

    public function findById(int $id);

    public function findByKelas(int $kelasId): array;

    public function findOfferingsByTeachingAssignment(int $kelasId, int $mataKuliahId, int $dosenId, ?int $ignoreId = null): array;

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
