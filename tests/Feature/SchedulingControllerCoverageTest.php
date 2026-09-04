<?php

namespace Tests\Feature;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Penjadwalan\Application\Service\SchedulingAppService;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class SchedulingControllerCoverageTest extends TestCase
{
    public function test_super_admin_can_use_all_scheduling_endpoints(): void
    {
        DB::table('role')->updateOrInsert(['id' => 999], ['role' => 'Super Admin']);
        $user = UserModel::factory()->create(['role_id' => 999]);
        Sanctum::actingAs($user);

        $this->mock(SchedulingAppService::class, function (MockInterface $mock) use ($user): void {
            $mock->shouldReceive('preview')
                ->once()
                ->with(0, 'ganjil', \Mockery::on(fn (array $scope): bool => $scope['user_id'] === $user->id))
                ->andReturn(['all_classes' => 2]);
            $mock->shouldReceive('generate')
                ->once()
                ->with(0, 'ganjil', \Mockery::type('array'), [
                    'num_kromosom' => 20,
                    'max_generation' => 50,
                    'crossover_rate' => 80,
                    'mutation_rate' => 20,
                ])
                ->andReturn(['success' => true]);
            $mock->shouldReceive('cancel')->once()->andReturn(['message' => 'cancel']);
            $mock->shouldReceive('restoreLast')->once()->with(\Mockery::type('array'), 'ganjil')->andReturn(['message' => 'restore']);
            $mock->shouldReceive('clearAll')->once()->with(\Mockery::type('array'), 'genap')->andReturn(['message' => 'clear']);
            $mock->shouldReceive('resetAuto')->once()->andReturn(['message' => 'reset']);
            $mock->shouldReceive('progress')->once()->with((string) $user->id)->andReturn(null);
        });

        $this->getJson('/api/v2/scheduling/preview?semester_tipe=ganjil')
            ->assertOk()
            ->assertJsonPath('all_classes', 2);
        $this->postJson('/api/v2/scheduling/generate', [
            'semester_tipe' => 'ganjil',
            'num_kromosom' => 20,
            'max_generation' => 50,
            'crossover_rate' => 80,
            'mutation_rate' => 20,
        ])->assertOk()->assertJsonPath('success', true);
        $this->postJson('/api/v2/scheduling/cancel')->assertOk()->assertJsonPath('message', 'cancel');
        $this->postJson('/api/v2/scheduling/restore-last', ['semester_tipe' => 'ganjil'])->assertOk()->assertJsonPath('message', 'restore');
        $this->postJson('/api/v2/scheduling/clear-all?semester_tipe=genap')->assertOk()->assertJsonPath('message', 'clear');
        $this->postJson('/api/v2/scheduling/reset-auto')->assertOk()->assertJsonPath('message', 'reset');
        $this->getJson('/api/v2/scheduling/progress')->assertOk()->assertJsonPath('status', 'no_progress');

        $this->assertDatabaseCount('activity_logs', 5);
    }
}
