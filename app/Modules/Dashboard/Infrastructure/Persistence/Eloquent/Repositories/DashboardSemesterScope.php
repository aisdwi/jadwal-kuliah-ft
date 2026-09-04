<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\Schema;

final class DashboardSemesterScope
{
    public static function applyToMataKuliah($query, array $scope): void
    {
        $semesterTipe = strtolower((string) ($scope['semester_tipe'] ?? ''));

        if ($semesterTipe === 'ganjil') {
            $query->whereRaw('semester % 2 != 0');
        } elseif ($semesterTipe === 'genap') {
            $query->whereRaw('semester % 2 = 0');
        }
    }

    public static function applyToKelasKuliah($query, array $scope): void
    {
        $semesterTipe = strtolower((string) ($scope['semester_tipe'] ?? ''));

        if (!in_array($semesterTipe, ['ganjil', 'genap'], true)) {
            return;
        }

        if (Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
            $query->where('semester_tipe', $semesterTipe);
            return;
        }

        $query->whereHas('matakuliah', function ($mataKuliahQuery) use ($semesterTipe) {
            if ($semesterTipe === 'ganjil') {
                $mataKuliahQuery->whereRaw('semester % 2 != 0');
                return;
            }

            $mataKuliahQuery->whereRaw('semester % 2 = 0');
        });
    }
}
