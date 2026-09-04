<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use Illuminate\Database\Eloquent\Builder;

final class SchedulingKelasKuliahFilter
{
    public function apply(Builder $query, array $filters): Builder
    {
        (new SchedulingKelasKuliahSearchFilter())->apply($query, $filters['search'] ?? null);
        (new SchedulingKelasKuliahScheduleStatusFilter())->apply($query, $filters['is_scheduled'] ?? null, array_key_exists('is_scheduled', $filters));
        (new SchedulingMatakuliahFilter())->apply($query, 'program_studi_id', $filters['program_studi_id'] ?? null);
        (new SchedulingMatakuliahFilter())->apply($query, 'jurusan_id', $filters['jurusan_id'] ?? null);
        (new SchedulingSemesterTypeFilter())->apply($query, $filters['semester_tipe'] ?? null);

        return $query->distinct();
    }
}
