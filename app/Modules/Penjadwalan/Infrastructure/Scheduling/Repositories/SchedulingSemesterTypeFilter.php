<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class SchedulingSemesterTypeFilter
{
    public function apply(Builder $query, mixed $semesterType): void
    {
        $tipe = strtolower(trim((string) $semesterType));
        if (!in_array($tipe, ['ganjil', 'genap'], true)) {
            return;
        }

        if (Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
            $query->where('semester_tipe', $tipe);
            return;
        }

        $query->whereHas('matakuliah', function ($q) use ($tipe) {
            $operator = $tipe === 'ganjil' ? '!=' : '=';
            $q->whereRaw("semester % 2 {$operator} 0");
        });
    }
}
