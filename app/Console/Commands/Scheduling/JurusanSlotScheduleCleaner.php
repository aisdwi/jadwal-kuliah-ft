<?php

namespace App\Console\Commands\Scheduling;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class JurusanSlotScheduleCleaner
{
    public function clear(): void
    {
        if (Schema::hasTable('jadwal')) {
            DB::table('jadwal')->delete();
        }

        $this->clearNullableScheduleColumn('slot_id');
        $this->clearNullableScheduleColumn('ruangan_id');

        if (Schema::hasTable('kelas_kuliah_dosen') && Schema::hasColumn('kelas_kuliah_dosen', 'preferred_slot_id')) {
            DB::table('kelas_kuliah_dosen')->update(['preferred_slot_id' => null]);
        }

        DB::table('jurusan_slot')->delete();
        DB::table('slot')->delete();
    }

    private function clearNullableScheduleColumn(string $column): void
    {
        if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', $column)) {
            DB::table('kelas_kuliah')->update([$column => null]);
        }
    }
}
