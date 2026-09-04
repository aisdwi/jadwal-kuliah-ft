<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->restoreMissingMatakuliah104();
            $this->deleteOrphanJadwalRows();
            $this->collapseDuplicateSlots();
        });
    }

    public function down(): void
    {
        // Not safely reversible. This migration reconciles legacy data anomalies.
    }

    private function restoreMissingMatakuliah104(): void
    {
        if (! Schema::hasTable('matakuliah') || ! Schema::hasTable('kelas_kuliah')) {
            return;
        }

        $needsRestore = DB::table('kelas_kuliah')->where('matakuliah_id', 104)->exists()
            && ! DB::table('matakuliah')->where('id', 104)->exists();

        if (! $needsRestore) {
            return;
        }

        $programStudiExists = Schema::hasTable('program_studi')
            && DB::table('program_studi')->where('id', 4)->exists();

        $jurusanExists = Schema::hasTable('jurusan')
            && DB::table('jurusan')->where('id', 2)->exists();

        if (! $programStudiExists || ! $jurusanExists) {
            return;
        }

        // Backfill from the canonical legacy dumps bundled in the repository.
        DB::table('matakuliah')->insert([
            'id' => 104,
            'kode_mk' => 'FTS11023',
            'nama_mk' => 'Matematika Dasar',
            'sks' => 2,
            'semester' => 1,
            'program_studi_id' => 4,
            'jurusan_id' => 2,
            'created_at' => '2024-07-26 03:00:00',
            'updated_at' => '2024-07-26 03:00:00',
        ]);
    }

    private function deleteOrphanJadwalRows(): void
    {
        if (! Schema::hasTable('jadwal') || ! Schema::hasTable('kelas_kuliah')) {
            return;
        }

        $orphanIds = DB::table('jadwal as j')
            ->leftJoin('kelas_kuliah as kk', 'j.kelas_kuliah_id', '=', 'kk.id')
            ->whereNull('kk.id')
            ->pluck('j.id');

        if ($orphanIds->isEmpty()) {
            return;
        }

        DB::table('jadwal')->whereIn('id', $orphanIds)->delete();
    }

    private function collapseDuplicateSlots(): void
    {
        if (! Schema::hasTable('slot')) {
            return;
        }

        $duplicateGroups = DB::table('slot')
            ->select('hari_id', 'waktu_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('hari_id', 'waktu_id')
            ->having('duplicate_count', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $dropIds = DB::table('slot')
                ->where('hari_id', $group->hari_id)
                ->where('waktu_id', $group->waktu_id)
                ->where('id', '!=', $group->keep_id)
                ->pluck('id');

            if ($dropIds->isEmpty()) {
                continue;
            }

            if (Schema::hasTable('jadwal') && Schema::hasColumn('jadwal', 'slot_id')) {
                DB::table('jadwal')
                    ->whereIn('slot_id', $dropIds)
                    ->update(['slot_id' => $group->keep_id]);
            }

            if (Schema::hasTable('kelas_kuliah_dosen') && Schema::hasColumn('kelas_kuliah_dosen', 'preferred_slot_id')) {
                DB::table('kelas_kuliah_dosen')
                    ->whereIn('preferred_slot_id', $dropIds)
                    ->update(['preferred_slot_id' => $group->keep_id]);
            }

            DB::table('slot')->whereIn('id', $dropIds)->delete();
        }
    }
};
