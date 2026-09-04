<?php

namespace App\Modules\KelasKuliah\Domain\Repositories;

interface KelasKuliahStatsRepository
{
    public function stats(?string $semesterTipe = null, ?int $programStudiId = null, ?int $jurusanId = null): array;
}
