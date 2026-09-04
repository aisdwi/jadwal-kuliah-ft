<?php

namespace Tests\Feature\Penjadwalan;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClearInvalidRoomAssignmentsCommandTest extends TestCase
{
    public function test_command_deletes_only_schedules_using_rooms_outside_course_jurusan_allocation(): void
    {
        $this->seedScheduleRoomAllocationFixture();

        $this->artisan('schedule:clear-invalid-room-assignments', ['--force' => true])
            ->expectsOutput('Jadwal invalid yang dihapus: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('jadwal', ['id' => 1]);
        $this->assertDatabaseMissing('jadwal', ['id' => 2]);
    }

    private function seedScheduleRoomAllocationFixture(): void
    {
        DB::table('jurusan')->insert([
            ['id' => 1, 'nama_jurusan' => 'Teknik Elektro'],
            ['id' => 2, 'nama_jurusan' => 'Teknik Sipil'],
        ]);
        DB::table('program_studi')->insert([
            ['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'S1 Teknik Elektro'],
            ['id' => 2, 'jurusan_id' => 2, 'nama_prodi' => 'S1 Teknik Sipil'],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung C']);
        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'C-301', 'kapasitas' => 40],
            ['id' => 2, 'gedung_id' => 1, 'ruangan' => 'C-306', 'kapasitas' => 40],
        ]);
        DB::table('jurusan_ruangan')->insert([
            ['jurusan_id' => 1, 'ruangan_id' => 1],
            ['jurusan_id' => 2, 'ruangan_id' => 2],
        ]);
        DB::table('dosen')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_lengkap' => 'Dosen Elektro']);
        DB::table('matakuliah')->insert(['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TED101', 'nama_mk' => 'Rangkaian Listrik', 'sks' => 2, 'semester' => 1]);
        DB::table('kelas')->insert(['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'E-A', 'semester' => 1]);
        DB::table('kelas_kuliah')->insert(['id' => 1, 'dosen_id' => 1, 'matakuliah_id' => 1, 'kelas_id' => 1, 'jumlah_mahasiswa' => 30]);
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->updateOrInsert(['id' => 1], ['pukul' => '08.00 - 09.40', 'sks' => 2]);
        DB::table('slot')->insert(['id' => 1, 'hari_id' => 1, 'waktu_id' => 1]);
        DB::table('jadwal')->insert([
            ['id' => 1, 'kelas_kuliah_id' => 1, 'slot_id' => 1, 'ruangan_id' => 1, 'origin' => 'generated'],
            ['id' => 2, 'kelas_kuliah_id' => 1, 'slot_id' => 1, 'ruangan_id' => 2, 'origin' => 'generated'],
        ]);
    }
}
