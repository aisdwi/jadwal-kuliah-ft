<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class KelasKuliahAcademicPeriodDefaults
{
    public static function fill(KelasKuliahModel $model): void
    {
        if (!Schema::hasColumn('kelas_kuliah', 'semester_tipe') || !empty($model->semester_tipe)) {
            return;
        }

        $model->semester_tipe = self::semesterTipeForMatakuliah((int) $model->matakuliah_id);
    }

    public static function fillData(array $data): array
    {
        if (Schema::hasColumn('kelas_kuliah', 'semester_tipe') && empty($data['semester_tipe'])) {
            $data['semester_tipe'] = self::semesterTipeForMatakuliah((int) ($data['matakuliah_id'] ?? 0));
        }

        return $data;
    }

    public static function semesterTipeForMatakuliah(int $matakuliahId): ?string
    {
        if ($matakuliahId <= 0 || !Schema::hasTable('matakuliah')) {
            return null;
        }

        $semester = DB::table('matakuliah')->where('id', $matakuliahId)->value('semester');
        if ($semester === null) {
            return null;
        }

        return ((int) $semester % 2) === 0 ? 'genap' : 'ganjil';
    }
}
