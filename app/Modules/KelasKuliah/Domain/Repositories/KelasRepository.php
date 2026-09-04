<?php

namespace App\Modules\KelasKuliah\Domain\Repositories;

use App\Modules\Shared\Domain\PagedResult;

interface KelasRepository
{
    public function all(?string $search = null, ?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): PagedResult;

    public function findById(int $id);

    public function findByProgramStudi(int $programStudiId, ?int $semester = null): array;

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
