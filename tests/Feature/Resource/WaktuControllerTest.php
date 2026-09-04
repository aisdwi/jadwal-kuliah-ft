<?php

namespace Tests\Feature\Resource;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaktuControllerTest extends TestCase
{
    public function test_admin_can_create_and_update_waktu_with_ui_payload(): void
    {
        DB::table('role')->updateOrInsert(['id' => 1], ['role' => 'Super Admin']);
        $user = UserModel::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/v2/waktu', [
            'pukul' => '08.00 - 09.40',
            'sks' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('pukul', '08.00 - 09.40');

        $id = $createResponse->json('id');

        $this->putJson("/api/v2/waktu/{$id}", [
            'pukul' => '09.50 - 11.30',
            'sks' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('pukul', '09.50 - 11.30');
    }

    public function test_waktu_rejects_duplicate_time_range(): void
    {
        DB::table('role')->updateOrInsert(['id' => 1], ['role' => 'Super Admin']);
        $user = UserModel::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v2/waktu', [
            'pukul' => '08.00 - 09.40',
            'sks' => 2,
        ])->assertCreated();

        $this->postJson('/api/v2/waktu', [
            'pukul' => '08.00-09.40',
            'sks' => 2,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Jam kuliah dengan rentang waktu yang sama sudah ada.');
    }

    public function test_waktu_index_is_sorted_by_earliest_time(): void
    {
        DB::table('role')->updateOrInsert(['id' => 1], ['role' => 'Super Admin']);
        $user = UserModel::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($user);

        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '13.00 - 14.40', 'sks' => 2, 'jam_index' => 3],
            ['id' => 2, 'pukul' => '08.00 - 09.40', 'sks' => 2, 'jam_index' => 1],
            ['id' => 3, 'pukul' => '10.00 - 11.40', 'sks' => 2, 'jam_index' => 2],
        ]);

        $items = $this->getJson('/api/v2/waktu')
            ->assertOk()
            ->json('data');

        $positions = collect($items)
            ->pluck('id')
            ->flip();

        $this->assertLessThan($positions[3], $positions[2]);
        $this->assertLessThan($positions[1], $positions[3]);
    }
}
