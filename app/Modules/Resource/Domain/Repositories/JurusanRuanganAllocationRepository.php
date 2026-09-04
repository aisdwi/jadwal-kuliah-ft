<?php

namespace App\Modules\Resource\Domain\Repositories;

use Illuminate\Support\Collection;

interface JurusanRuanganAllocationRepository
{
    public function loadJurusanDemands(): Collection;

    public function replaceAllocations(array $rows): void;
}
