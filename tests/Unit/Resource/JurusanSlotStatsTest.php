<?php

namespace Tests\Unit\Resource;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories\EloquentKelasKuliahStatsRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingQueryRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JurusanSlotStatsTest extends TestCase
{
    public function test_kelas_kuliah_stats_counts_only_slots_owned_by_selected_jurusan(): void
    {
        $this->seedAcademicSlotFixture();

        $stats = (new EloquentKelasKuliahStatsRepository())->stats(
            semesterTipe: null,
            programStudiId: null,
            jurusanId: 1,
        );

        $this->assertSame(1, $stats['slots']['total']);
        $this->assertSame(1, $stats['slots']['sks_2']);
        $this->assertSame(0, $stats['slots']['sks_3']);
    }

    public function test_scheduling_stats_counts_only_slots_owned_by_restricted_jurusan(): void
    {
        $this->seedAcademicSlotFixture();

        $stats = (new EloquentSchedulingQueryRepository())->getKelasKuliahStats(
            semesterTipe: null,
            scope: [
                'is_restricted' => true,
                'restrict_by_jurusan' => true,
                'jurusan_id' => 2,
            ],
        );

        $this->assertSame(1, $stats['slots']['total']);
        $this->assertSame(0, $stats['slots']['sks_2']);
        $this->assertSame(1, $stats['slots']['sks_3']);
        $this->assertSame(1, $stats['slots']['unit_count']);
    }

    private function seedAcademicSlotFixture(): void
    {
        if (! Schema::hasTable('jurusan_slot')) {
            Schema::create('jurusan_slot', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jurusan_id');
                $table->unsignedBigInteger('slot_id');
                $table->timestamps();
                $table->unique(['jurusan_id', 'slot_id']);
            });
        }

        DB::table('hari')->insert([
            ['id' => 1, 'nama_hari' => 'Senin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2, 'jam_index' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'pukul' => '10.00 - 12.30', 'sks' => 3, 'jam_index' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('slot')->insert([
            ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'hari_id' => 1, 'waktu_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('jurusan')->insert([
            ['id' => 1, 'nama_jurusan' => 'Informatika', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_jurusan' => 'Sipil', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('jurusan_slot')->insert([
            ['jurusan_id' => 1, 'slot_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['jurusan_id' => 2, 'slot_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('gedung')->insert([
            ['id' => 1, 'nama_gedung' => 'A', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'A101', 'kapasitas' => 40, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('program_studi')->insert([
            ['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'Teknik Informatika', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'jurusan_id' => 2, 'nama_prodi' => 'Teknik Sipil', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('dosen')->insert([
            ['id' => 1, 'jurusan_id' => 1, 'nip' => '111', 'nama_lengkap' => 'Dosen Informatika', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'jurusan_id' => 2, 'nip' => '222', 'nama_lengkap' => 'Dosen Sipil', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('matakuliah')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'IF001', 'nama_mk' => 'Algoritma', 'sks' => 2, 'semester' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'program_studi_id' => 2, 'jurusan_id' => 2, 'kode_mk' => 'SP001', 'nama_mk' => 'Struktur', 'sks' => 3, 'semester' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('kelas')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'IF-A', 'semester' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'program_studi_id' => 2, 'jurusan_id' => 2, 'nama_kelas' => 'SP-A', 'semester' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('kelas_kuliah')->insert([
            ['id' => 1, 'dosen_id' => 1, 'matakuliah_id' => 1, 'kelas_id' => 1, 'jumlah_mahasiswa' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'dosen_id' => 2, 'matakuliah_id' => 2, 'kelas_id' => 2, 'jumlah_mahasiswa' => 35, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
