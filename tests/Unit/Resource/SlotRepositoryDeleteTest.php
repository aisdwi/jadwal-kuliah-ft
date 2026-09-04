<?php

namespace Tests\Unit\Resource;

use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentSlotRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlotRepositoryDeleteTest extends TestCase
{
    public function test_delete_removes_slot_dependencies_before_deleting_slot(): void
    {
        $this->seedScheduledSlot();

        $repository = new EloquentSlotRepository(new SlotModel());

        $repository->delete(1);

        $this->assertDatabaseMissing('slot', ['id' => 1]);
        $this->assertDatabaseMissing('jadwal', ['slot_id' => 1]);
        $this->assertDatabaseHas('kelas_kuliah_dosen', [
            'kelas_kuliah_id' => 1,
            'dosen_id' => 1,
            'preferred_slot_id' => null,
        ]);
    }

    private function seedScheduledSlot(): void
    {
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert(['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2]);
        DB::table('slot')->insert(['id' => 1, 'hari_id' => 1, 'waktu_id' => 1]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung A']);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Sipil']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'D3 Teknik Sipil']);
        DB::table('ruangan')->insert(['id' => 1, 'gedung_id' => 1, 'ruangan' => 'A101', 'kapasitas' => 40]);
        DB::table('dosen')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_lengkap' => 'Dosen A']);
        DB::table('matakuliah')->insert([
            'id' => 1,
            'program_studi_id' => 1,
            'jurusan_id' => 1,
            'kode_mk' => 'TS101',
            'nama_mk' => 'Struktur',
            'sks' => 2,
            'semester' => 1,
        ]);
        DB::table('kelas')->insert([
            'id' => 1,
            'program_studi_id' => 1,
            'jurusan_id' => 1,
            'nama_kelas' => 'TS-A',
            'semester' => 1,
        ]);
        DB::table('kelas_kuliah')->insert([
            'id' => 1,
            'dosen_id' => 1,
            'matakuliah_id' => 1,
            'kelas_id' => 1,
            'jumlah_mahasiswa' => 30,
        ]);
        DB::table('jadwal')->insert([
            'id' => 1,
            'kelas_kuliah_id' => 1,
            'slot_id' => 1,
            'ruangan_id' => 1,
        ]);
        DB::table('kelas_kuliah_dosen')->insert([
            'id' => 1,
            'kelas_kuliah_id' => 1,
            'dosen_id' => 1,
            'preferred_slot_id' => 1,
        ]);
    }
}
