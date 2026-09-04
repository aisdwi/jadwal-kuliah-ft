<?php

namespace App\Modules\MasterAkademik\Domain\Repositories;

interface ProgramStudiRepository
{
    public function all(?string $search = null, ?int $jurusanId = null, int|string $perPage = 'all');

    public function findById(int $id);

    public function findAccessibleById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}
