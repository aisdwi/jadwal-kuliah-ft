<?php

namespace Tests\Unit\Resource;

use App\Console\Commands\Scheduling\JurusanSlotRegenerator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JurusanSlotRegeneratorTest extends TestCase
{
    public function test_regenerates_slots_from_jurusan_course_sks_demand_and_time_windows(): void
    {
        DB::table('waktu')->insert(['id' => 99, 'pukul' => '07.00 - 07.50', 'sks' => 1]);
        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 10.30', 'sks' => 3],
            ['id' => 2, 'pukul' => '10.40 - 11.30', 'sks' => 1],
            ['id' => 3, 'pukul' => '13.00 - 14.40', 'sks' => 2],
            ['id' => 4, 'pukul' => '14.50 - 16.30', 'sks' => 2],
            ['id' => 5, 'pukul' => '08.00 - 09.40', 'sks' => 2],
        ]);
        DB::table('hari')->insert([
            ['id' => 1, 'nama_hari' => 'Senin'],
            ['id' => 2, 'nama_hari' => 'Selasa'],
            ['id' => 3, 'nama_hari' => 'Rabu'],
        ]);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Sipil']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'D3 Teknik Sipil']);
        DB::table('matakuliah')->insert([
            ['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS101', 'nama_mk' => 'Fisika Dasar', 'sks' => 2, 'semester' => 1],
            ['id' => 2, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS102', 'nama_mk' => 'Matematika Dasar', 'sks' => 3, 'semester' => 1],
            ['id' => 3, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS103', 'nama_mk' => 'Praktikum', 'sks' => 1, 'semester' => 1],
        ]);
        $waktuCountBefore = DB::table('waktu')->count();

        (new JurusanSlotRegenerator())->regenerate();

        $this->assertDatabaseHas('waktu', ['pukul' => '08.00 - 10.30', 'sks' => 3]);
        $this->assertDatabaseHas('waktu', ['pukul' => '10.40 - 11.30', 'sks' => 1]);
        $this->assertDatabaseHas('waktu', ['pukul' => '13.00 - 14.40', 'sks' => 2]);
        $this->assertDatabaseHas('waktu', ['pukul' => '07.00 - 07.50']);
        $this->assertSame($waktuCountBefore, DB::table('waktu')->count());
        $this->assertDatabaseMissing('slot', ['waktu_id' => 99]);
        $this->assertGreaterThan(0, DB::table('jurusan_slot')->where('jurusan_id', 1)->count());
        $this->assertNotSame($this->slotSksForDay(1), $this->slotSksForDay(2));
    }

    public function test_keeps_minor_sks_values_visible_when_two_sks_courses_dominate(): void
    {
        DB::table('hari')->insert([
            ['id' => 1, 'nama_hari' => 'Senin'],
            ['id' => 2, 'nama_hari' => 'Selasa'],
        ]);
        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 10.30', 'sks' => 3],
            ['id' => 2, 'pukul' => '10.40 - 11.30', 'sks' => 1],
            ['id' => 3, 'pukul' => '13.00 - 14.40', 'sks' => 2],
            ['id' => 4, 'pukul' => '14.50 - 16.30', 'sks' => 2],
            ['id' => 5, 'pukul' => '08.00 - 09.40', 'sks' => 2],
        ]);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Sipil']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'D3 Teknik Sipil']);

        $courses = [];
        for ($i = 1; $i <= 8; $i++) {
            $courses[] = [
                'id' => $i,
                'program_studi_id' => 1,
                'jurusan_id' => 1,
                'kode_mk' => 'TS' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nama_mk' => 'Mata Kuliah ' . $i,
                'sks' => 2,
                'semester' => 1,
            ];
        }

        $courses[] = ['id' => 20, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS020', 'nama_mk' => 'Struktur 3 SKS', 'sks' => 3, 'semester' => 1];
        $courses[] = ['id' => 21, 'program_studi_id' => 1, 'jurusan_id' => 1, 'kode_mk' => 'TS021', 'nama_mk' => 'Seminar 1 SKS', 'sks' => 1, 'semester' => 1];
        DB::table('matakuliah')->insert($courses);

        (new JurusanSlotRegenerator())->regenerate();

        $this->assertContains(3, $this->slotSksForDay(1));
        $this->assertContains(1, $this->slotSksForDay(1));
        $this->assertNotSame($this->slotSksForDay(1), $this->slotSksForDay(2));
    }

    /**
     * @return list<int>
     */
    private function slotSksForDay(int $dayId): array
    {
        return DB::table('slot')
            ->join('waktu', 'waktu.id', '=', 'slot.waktu_id')
            ->where('slot.hari_id', $dayId)
            ->orderBy('waktu.pukul')
            ->pluck('waktu.sks')
            ->map(fn ($sks) => (int) $sks)
            ->all();
    }
}
