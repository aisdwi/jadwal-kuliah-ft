<?php

namespace App\Modules\Resource\Domain\Repositories;

use Illuminate\Support\Collection;

interface SlotRepository
{
    public function all(?int $jurusanId = null): Collection;
    public function findById(int $id);
    public function findByHari(int $hariId, ?int $jurusanId = null): Collection;
    public function findByHariAndWaktu(int $hariId, int $waktuId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): void;
}
