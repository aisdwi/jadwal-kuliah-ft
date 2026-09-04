<?php

namespace Tests\Unit\Dashboard;

use App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories\DashboardChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardChartQueryTest extends TestCase
{
    public function test_ketua_jurusan_dashboard_progress_is_grouped_by_program_studi_in_own_jurusan(): void
    {
        $this->seedDashboardData();

        $rows = (new DashboardChartQuery())->getChartData([
            'role_name' => 'Ketua Jurusan',
            'jurusan_id' => 1,
            'default_jurusan_id' => 1,
            'program_studi_id' => null,
            'default_program_studi_id' => null,
            'restrict_by_jurusan' => false,
            'restrict_by_program_studi' => false,
            'semester_tipe' => null,
        ]);

        $this->assertSame([
            ['name' => 'S1 Teknik Elektro', 'filled' => 100.0, 'terjadwal' => 1, 'total' => 1],
            ['name' => 'S1 Teknik Informatika', 'filled' => 0.0, 'terjadwal' => 0, 'total' => 1],
            ['name' => 'D3 Teknik Elektro', 'filled' => 0.0, 'terjadwal' => 0, 'total' => 1],
        ], $rows);
    }

    private function seedDashboardData(): void
    {
        DB::table('jurusan')->insert([
            ['id' => 1, 'nama_jurusan' => 'Teknik Elektro'],
            ['id' => 2, 'nama_jurusan' => 'Teknik Sipil'],
        ]);
        DB::table('program_studi')->insert([
            ['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'S1 Teknik Elektro'],
            ['id' => 2, 'jurusan_id' => 1, 'nama_prodi' => 'S1 Teknik Informatika'],
            ['id' => 3, 'jurusan_id' => 1, 'nama_prodi' => 'D3 Teknik Elektro'],
            ['id' => 4, 'jurusan_id' => 2, 'nama_prodi' => 'S1 Teknik Sipil'],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'C']);
        DB::table('ruangan')->insert(['id' => 1, 'gedung_id' => 1, 'ruangan' => 'C-301', 'kapasitas' => 40]);
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert(['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]);
        DB::table('slot')->insert(['id' => 1, 'hari_id' => 1, 'waktu_id' => 1]);
        DB::table('dosen')->insert(['id' => 1, 'jurusan_id' => 1, 'nip' => '1', 'nama_lengkap' => 'Dosen Elektro']);

        DB::table('matakuliah')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TE001', 'nama_mk' => 'Dasar Elektro', 'sks' => 2, 'semester' => 1],
            ['id' => 2, 'program_studi_id' => 2, 'jurusan_id' => 1, 'kode_mk' => 'TI001', 'nama_mk' => 'Dasar Informatika', 'sks' => 2, 'semester' => 1],
            ['id' => 3, 'program_studi_id' => 3, 'jurusan_id' => 1, 'kode_mk' => 'DE001', 'nama_mk' => 'Dasar D3 Elektro', 'sks' => 2, 'semester' => 1],
            ['id' => 4, 'program_studi_id' => 4, 'jurusan_id' => 2, 'kode_mk' => 'TS001', 'nama_mk' => 'Dasar Sipil', 'sks' => 2, 'semester' => 1],
        ]);
        DB::table('kelas')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'TE-A', 'semester' => 1],
            ['id' => 2, 'program_studi_id' => 2, 'jurusan_id' => 1, 'nama_kelas' => 'TI-A', 'semester' => 1],
            ['id' => 3, 'program_studi_id' => 3, 'jurusan_id' => 1, 'nama_kelas' => 'DE-A', 'semester' => 1],
            ['id' => 4, 'program_studi_id' => 4, 'jurusan_id' => 2, 'nama_kelas' => 'TS-A', 'semester' => 1],
        ]);
        DB::table('kelas_kuliah')->insert([
            ['id' => 1, 'dosen_id' => 1, 'matakuliah_id' => 1, 'kelas_id' => 1, 'jumlah_mahasiswa' => 30],
            ['id' => 2, 'dosen_id' => 1, 'matakuliah_id' => 2, 'kelas_id' => 2, 'jumlah_mahasiswa' => 30],
            ['id' => 3, 'dosen_id' => 1, 'matakuliah_id' => 3, 'kelas_id' => 3, 'jumlah_mahasiswa' => 30],
            ['id' => 4, 'dosen_id' => 1, 'matakuliah_id' => 4, 'kelas_id' => 4, 'jumlah_mahasiswa' => 30],
        ]);
        DB::table('jadwal')->insert(['id' => 1, 'kelas_kuliah_id' => 1, 'slot_id' => 1, 'ruangan_id' => 1, 'origin' => 'generated']);
    }
}
