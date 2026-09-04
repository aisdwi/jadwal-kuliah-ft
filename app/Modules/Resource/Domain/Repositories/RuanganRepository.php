<?php

namespace App\Modules\Resource\Domain\Repositories;

use Illuminate\Support\Collection;

interface RuanganRepository
{
    public function all(?string $search = null, ?int $jurusanId = null): Collection;
    public function allForAllocation(): Collection;
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): void;
}
