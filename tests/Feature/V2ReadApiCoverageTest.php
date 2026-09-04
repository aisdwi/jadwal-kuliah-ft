<?php

namespace Tests\Feature;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class V2ReadApiCoverageTest extends TestCase
{
    // This suite is the release smoke test for the active API surface.
    public function test_super_admin_can_read_catalog_dashboard_reference_and_schedule_payloads(): void
    {
        $fixture = $this->seedFixture();
        Sanctum::actingAs($fixture['user']);

        foreach ([
            '/api/v2/dashboard/stats?semester_tipe=ganjil',
            '/api/v2/dashboard/chart?semester_tipe=ganjil',
            '/api/v2/dashboard/activity',
            '/api/v2/referensi/jurusan',
            '/api/v2/referensi/program-studi?jurusan_id=' . $fixture['jurusan_id'],
            '/api/v2/referensi/gedung',
            '/api/v2/referensi/hari',
            '/api/v2/referensi/waktu',
            '/api/v2/referensi/slot?jurusan_id=' . $fixture['jurusan_id'],
            '/api/v2/referensi/roles',
            '/api/v2/dosen?per_page=all',
            '/api/v2/matakuliah?per_page=all&semester_tipe=ganjil',
            '/api/v2/jurusan',
            '/api/v2/program-studi?jurusan_id=' . $fixture['jurusan_id'],
            '/api/v2/ruangan?jurusan_id=' . $fixture['jurusan_id'],
            '/api/v2/hari',
            '/api/v2/waktu',
            '/api/v2/slot?jurusan_id=' . $fixture['jurusan_id'],
            '/api/v2/slot?hari_id=' . $fixture['hari_id'] . '&jurusan_id=' . $fixture['jurusan_id'],
            '/api/v2/users?per_page=all',
            '/api/v2/users/roles',
            '/api/v2/kelas?per_page=all&program_studi_id=' . $fixture['program_studi_id'],
            '/api/v2/kelas-kuliah?per_page=all&program_studi_id=' . $fixture['program_studi_id'],
            '/api/v2/kelas-kuliah/stats?semester_tipe=ganjil&program_studi_id=' . $fixture['program_studi_id'],
            '/api/v2/jadwal?program_studi_id=' . $fixture['program_studi_id'] . '&semester=1',
        ] as $url) {
            $this->getJson($url)->assertOk();
        }

        $this->getJson('/api/v2/dosen/' . $fixture['dosen_id'])
            ->assertOk()
            ->assertJsonPath('nama_lengkap', 'Dosen Coverage');
        $this->getJson('/api/v2/matakuliah/' . $fixture['matakuliah_id'])
            ->assertOk()
            ->assertJsonPath('kode_mk', 'CVG101');
        $this->getJson('/api/v2/jurusan/' . $fixture['jurusan_id'])
            ->assertOk()
            ->assertJsonPath('nama_jurusan', 'Teknik Coverage');
        $this->getJson('/api/v2/program-studi/' . $fixture['program_studi_id'])
            ->assertOk()
            ->assertJsonPath('nama_prodi', 'S1 Coverage');
        $this->getJson('/api/v2/ruangan/' . $fixture['ruangan_id'])
            ->assertOk()
            ->assertJsonPath('ruangan', 'CVG-101');
        $this->getJson('/api/v2/hari/' . $fixture['hari_id'])
            ->assertOk()
            ->assertJsonPath('nama_hari', 'Senin');
        $this->getJson('/api/v2/waktu/' . $fixture['waktu_id'])
            ->assertOk()
            ->assertJsonPath('pukul', '08.00 - 09.40');
        $this->getJson('/api/v2/slot/' . $fixture['slot_id'])
            ->assertOk()
            ->assertJsonPath('hari.nama_hari', 'Senin');
        $this->getJson('/api/v2/users/' . $fixture['user']->id)
            ->assertOk()
            ->assertJsonPath('role.role', 'Super Admin');
        $this->getJson('/api/v2/kelas/' . $fixture['kelas_id'])
            ->assertOk()
            ->assertJsonPath('nama_kelas', 'CVG-A');
        $this->getJson('/api/v2/kelas-kuliah/' . $fixture['kelas_kuliah_id'])
            ->assertOk()
            ->assertJsonPath('slot.hari.nama_hari', 'Senin')
            ->assertJsonPath('ruangan.ruangan', 'CVG-101');
        $this->getJson('/api/v2/jadwal/' . $fixture['jadwal_id'])
            ->assertOk()
            ->assertJsonPath('kelas_kuliah.matakuliah.kode_mk', 'CVG101')
            ->assertJsonPath('slot.hari.nama_hari', 'Senin');
    }

    public function test_auth_token_endpoints_issue_return_and_revoke_token(): void
    {
        $fixture = $this->seedFixture();

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => $fixture['user']->email,
            'password' => 'coverage-password',
        ])
            ->assertOk()
            ->assertJsonPath('user.role', 'Super Admin')
            ->assertJsonPath('user.jurusan_name', 'Teknik Coverage');

        $token = $response->json('token');

        $this->withToken($token)->getJson('/api/v2/auth/user')
            ->assertOk()
            ->assertJsonPath('id', $fixture['user']->id);

        $this->withToken($token)->postJson('/api/v2/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_read_unread_count_and_mark_all_notifications_as_read(): void
    {
        $fixture = $this->seedFixture();
        Sanctum::actingAs($fixture['user']);

        $this->getJson('/api/v2/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->patchJson('/api/v2/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull(DB::table('notifications')->where('user_id', $fixture['user']->id)->value('read_at'));
    }

    private function seedFixture(): array
    {
        DB::table('role')->updateOrInsert(['id' => 1], ['role' => 'Super Admin']);
        $jurusanId = (int) DB::table('jurusan')->insertGetId(['nama_jurusan' => 'Teknik Coverage']);
        $programStudiId = (int) DB::table('program_studi')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nama_prodi' => 'S1 Coverage',
        ]);
        $dosenId = (int) DB::table('dosen')->insertGetId([
            'jurusan_id' => $jurusanId,
            'nip' => 'CVG001',
            'nama_lengkap' => 'Dosen Coverage',
            'inisial' => 'CVG',
        ]);
        $matakuliahId = (int) DB::table('matakuliah')->insertGetId([
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'kode_mk' => 'CVG101',
            'nama_mk' => 'Mata Kuliah Coverage',
            'sks' => 2,
            'semester' => 1,
        ]);
        $kelasId = (int) DB::table('kelas')->insertGetId([
            'program_studi_id' => $programStudiId,
            'jurusan_id' => $jurusanId,
            'nama_kelas' => 'CVG-A',
            'semester' => 1,
        ]);
        $kelasKuliahId = (int) DB::table('kelas_kuliah')->insertGetId([
            'dosen_id' => $dosenId,
            'matakuliah_id' => $matakuliahId,
            'kelas_id' => $kelasId,
            'jumlah_mahasiswa' => 30,
            'semester_tipe' => 'ganjil',
        ]);
        $hariId = (int) DB::table('hari')->insertGetId(['nama_hari' => 'Senin']);
        $waktuId = (int) DB::table('waktu')->insertGetId([
            'pukul' => '08.00 - 09.40',
            'sks' => 2,
            'jam_index' => 1,
        ]);
        $slotId = (int) DB::table('slot')->insertGetId([
            'hari_id' => $hariId,
            'waktu_id' => $waktuId,
        ]);
        $gedungId = (int) DB::table('gedung')->insertGetId(['nama_gedung' => 'Gedung Coverage']);
        $ruanganId = (int) DB::table('ruangan')->insertGetId([
            'gedung_id' => $gedungId,
            'ruangan' => 'CVG-101',
            'kapasitas' => 40,
        ]);
        DB::table('jurusan_slot')->insert([
            'jurusan_id' => $jurusanId,
            'slot_id' => $slotId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jurusan_ruangan')->insert([
            'jurusan_id' => $jurusanId,
            'ruangan_id' => $ruanganId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jadwalId = (int) DB::table('jadwal')->insertGetId([
            'kelas_kuliah_id' => $kelasKuliahId,
            'slot_id' => $slotId,
            'ruangan_id' => $ruanganId,
            'origin' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = UserModel::factory()->create([
            'nama_user' => 'Admin Coverage',
            'email' => 'coverage-admin@example.test',
            'password' => Hash::make('coverage-password'),
            'role_id' => 1,
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
            'dosen_id' => $dosenId,
        ]);
        DB::table('activity_logs')->insert([
            'user_id' => $user->id,
            'action' => 'create',
            'subject_type' => 'Jadwal',
            'subject_name' => 'CVG101',
            'description' => 'Membuat jadwal coverage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('notifications')->insert([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'type' => 'coverage',
            'title' => 'Coverage notification',
            'message' => 'Unread coverage notification',
            'data' => json_encode(['url' => '/coverage']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'user' => $user,
            'jurusan_id' => $jurusanId,
            'program_studi_id' => $programStudiId,
            'dosen_id' => $dosenId,
            'matakuliah_id' => $matakuliahId,
            'kelas_id' => $kelasId,
            'kelas_kuliah_id' => $kelasKuliahId,
            'hari_id' => $hariId,
            'waktu_id' => $waktuId,
            'slot_id' => $slotId,
            'ruangan_id' => $ruanganId,
            'jadwal_id' => $jadwalId,
        ];
    }
}
