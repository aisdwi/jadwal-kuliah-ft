<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use Illuminate\Database\Eloquent\Builder;

final class SchedulingMatakuliahFilter
{
    public function apply(Builder $query, string $column, mixed $value): void
    {
        if (!empty($value)) {
            $query->whereHas('matakuliah', fn ($q) => $q->where($column, $value));
        }
    }
}
