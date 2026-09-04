<?php

namespace Tests\Unit\Resource;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories\EloquentRuanganRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RuanganRepositoryScopeTest extends TestCase
{
    public function test_ketua_jurusan_only_sees_rooms_allocated_to_own_jurusan(): void
    {
        DB::table('role')->updateOrInsert(['id' => 6], ['role' => 'Ketua Jurusan']);
        DB::table('jurusan')->insert([
            ['id' => 1, 'nama_jurusan' => 'Teknik Elektro'],
            ['id' => 2, 'nama_jurusan' => 'Teknik Sipil'],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'C']);
        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'C-301', 'kapasitas' => 40],
            ['id' => 2, 'gedung_id' => 1, 'ruangan' => 'C-306', 'kapasitas' => 40],
        ]);
        DB::table('jurusan_ruangan')->insert([
            ['jurusan_id' => 1, 'ruangan_id' => 1],
            ['jurusan_id' => 2, 'ruangan_id' => 2],
        ]);

        $user = UserModel::factory()->create([
            'role_id' => 6,
            'jurusan_id' => 1,
        ]);
        $this->actingAs($user);

        $rooms = (new EloquentRuanganRepository(new RuanganModel()))->all();

        $this->assertSame(['C-301'], $rooms->pluck('ruangan')->all());
    }
}
