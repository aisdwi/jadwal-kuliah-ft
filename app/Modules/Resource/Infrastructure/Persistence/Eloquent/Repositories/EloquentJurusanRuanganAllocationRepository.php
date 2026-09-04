<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Resource\Domain\Repositories\JurusanRuanganAllocationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentJurusanRuanganAllocationRepository implements JurusanRuanganAllocationRepository
{
    public function loadJurusanDemands(): Collection
    {
        return DB::table('kelas_kuliah as kk')
            ->join('matakuliah as mk', 'mk.id', '=', 'kk.matakuliah_id')
            ->where('mk.kode_mk', 'not like', 'U%')
            ->selectRaw('
                mk.jurusan_id as jurusan_id,
                COUNT(*) as total_classes,
                COALESCE(SUM(mk.sks), 0) as total_sks,
                COALESCE(SUM(kk.jumlah_mahasiswa), 0) as total_students,
                COALESCE(AVG(kk.jumlah_mahasiswa), 0) as avg_students,
                SUM(CASE WHEN kk.jumlah_mahasiswa >= 45 THEN 1 ELSE 0 END) as large_classes,
                SUM(CASE WHEN kk.jumlah_mahasiswa >= 35 THEN 1 ELSE 0 END) as medium_classes
            ')
            ->groupBy('mk.jurusan_id')
            ->get()
            ->keyBy('jurusan_id');
    }

    public function replaceAllocations(array $rows): void
    {
        DB::transaction(function () use ($rows): void {
            DB::table('jurusan_ruangan')->delete();

            if ($rows !== []) {
                DB::table('jurusan_ruangan')->insert($rows);
            }
        });
    }
}
