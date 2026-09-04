<?php

namespace App\Modules\MasterAkademik\Domain\Repositories;

interface MataKuliahRepository
{
    public function all(
        ?string    $search          = null,
        ?int       $programStudiId  = null,
        ?int       $semester        = null,
        ?string    $semesterTipe    = null,
        bool       $filterByJurusan = true,
        int|string $perPage         = 'all'
    );

    public function findById(int $id);

    public function findAccessibleById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
