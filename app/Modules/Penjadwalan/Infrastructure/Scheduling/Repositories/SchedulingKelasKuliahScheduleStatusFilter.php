<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use Illuminate\Database\Eloquent\Builder;

final class SchedulingKelasKuliahScheduleStatusFilter
{
    public function apply(Builder $query, mixed $value, bool $filterExists): void
    {
        if (!$filterExists || $value === null || $value === '') {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereHas('jadwals');
            return;
        }

        $query->whereDoesntHave('jadwals');
    }
}
