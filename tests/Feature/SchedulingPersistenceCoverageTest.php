<?php

namespace Tests\Feature;

use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories\EloquentJadwalRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingMutationRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingQueryRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingRunRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\EloquentSchedulingSnapshotRepository;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\SchedulingConflictLookup;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories\SchedulingScheduleWriter;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Services\DatabaseSchedulingProgressStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SchedulingPersistenceCoverageTest extends TestCase
{
    public function test_jadwal_repository_reads_filters_and_restores_generated_schedules(): void
    {
        $seed = $this->seedSchedulingFixture();
        $repository = new EloquentJadwalRepository(new JadwalModel());
        $manualId = $repository->create([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0],
            'slot_id' => $seed['slot_ids'][0],
            'ruangan_id' => $seed['ruangan_ids'][0],
            'origin' => 'manual',
        ])->id;
        $generatedId = $repository->create([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][1],
            'slot_id' => $seed['slot_ids'][1],
            'ruangan_id' => $seed['ruangan_ids'][1],
            'origin' => 'generated',
        ])->id;

        $this->assertCount(2, $repository->findAll($seed['program_studi_id'], 1));
        $this->assertSame(2, $repository->findAll(null, null, 1)['pagination']['total']);
        $this->assertSame($manualId, $repository->findById($manualId)->id);
        $this->assertSame($generatedId, $repository->findByKelasKuliah($seed['kelas_kuliah_ids'][1])->id);
        $this->assertCount(2, $repository->findByProgramStudi($seed['program_studi_id'], 1));
        $this->assertCount(2, $repository->findAssignmentsBySlot($seed['slot_ids'][0]));
        $this->assertSame([], $repository->findAssignmentsBySlot(9999));
        $this->assertCount(1, $repository->findAssignmentsBySlot($seed['slot_ids'][0], $seed['kelas_kuliah_ids'][0]));
        $this->assertSame($manualId, $repository->update($manualId, ['origin' => 'manual'])->id);
        $this->assertNull($repository->update(9999, ['origin' => 'manual']));

        $scope = [
            'restrict_by_program_studi' => true,
            'program_studi_id' => $seed['program_studi_id'],
        ];
        $generated = $repository->findGeneratedForSchedulingScope($scope, 'ganjil');
        $this->assertCount(1, $generated);
        $this->assertSame('generated', $generated[0]['origin']);
        $this->assertSame(1, $repository->deleteGeneratedForSchedulingScope($scope, 'ganjil'));
        $this->assertSame(0, $repository->restoreGeneratedSchedules([]));
        $this->assertSame(1, $repository->restoreGeneratedSchedules($generated));
        $this->assertSame(1, $repository->deleteGeneratedByProgramStudi($seed['program_studi_id'], 1));
        $this->assertSame(1, $repository->deleteAll($seed['program_studi_id']));
    }

    public function test_jadwal_repository_deletes_by_id_and_kelas_kuliah(): void
    {
        $seed = $this->seedSchedulingFixture();
        $repository = new EloquentJadwalRepository(new JadwalModel());
        $first = $repository->create([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0],
            'slot_id' => $seed['slot_ids'][0],
            'ruangan_id' => $seed['ruangan_ids'][0],
            'origin' => 'manual',
        ]);
        $repository->create([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][1],
            'slot_id' => $seed['slot_ids'][1],
            'ruangan_id' => $seed['ruangan_ids'][1],
            'origin' => 'manual',
        ]);

        $repository->delete($first->id);
        $repository->deleteByKelasKuliah($seed['kelas_kuliah_ids'][1]);

        $this->assertDatabaseCount('jadwal', 0);
    }

    public function test_schedule_writer_creates_updates_deletes_and_persists_generated_assignments(): void
    {
        $seed = $this->seedSchedulingFixture();
        $writer = new SchedulingScheduleWriter();

        $this->assertTrue($writer->updateSchedule($seed['kelas_kuliah_ids'][0], $seed['ruangan_ids'][0], $seed['slot_ids'][0]));
        $this->assertTrue($writer->updateSchedule($seed['kelas_kuliah_ids'][0], $seed['ruangan_ids'][1], $seed['slot_ids'][1]));
        $this->assertTrue($writer->updateSchedule($seed['kelas_kuliah_ids'][0], null, null));
        $this->assertDatabaseMissing('jadwal', ['kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0]]);

        $writer->persistGeneratedAssignments([[
            'kuliah' => $seed['kelas_kuliah_ids'][1],
            'slot' => $seed['slot_ids'][0],
            'ruang' => $seed['ruangan_ids'][0],
            'scheduling_run_id' => null,
        ]]);
        $writer->persistGeneratedAssignments([[
            'kuliah' => $seed['kelas_kuliah_ids'][1],
            'slot' => $seed['slot_ids'][1],
            'ruang' => $seed['ruangan_ids'][1],
            'scheduling_run_id' => null,
        ]]);

        $this->assertDatabaseHas('jadwal', [
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][1],
            'slot_id' => $seed['slot_ids'][1],
            'ruangan_id' => $seed['ruangan_ids'][1],
            'origin' => 'generated',
        ]);
    }

    public function test_run_and_snapshot_repositories_persist_lifecycle_state(): void
    {
        $seed = $this->seedSchedulingFixture();
        $runs = new EloquentSchedulingRunRepository();
        $snapshots = new EloquentSchedulingSnapshotRepository();
        $scope = [
            'restrict_by_program_studi' => true,
            'program_studi_id' => $seed['program_studi_id'],
            'label' => 'S1 Coverage',
        ];

        $runId = $runs->createQueued('system', ' GANJIL ', $scope, ['max_generation' => 50]);
        $this->assertNotNull($runId);
        $runs->markProcessing($runId);
        $runs->markFinished($runId, 'completed', [
            'success' => true,
            'fitness' => 0.9,
            'generation' => 10,
            'results' => [['id' => 1]],
        ]);
        $runs->markFailed(null, 'ignored');
        $this->assertDatabaseHas('scheduling_runs', [
            'id' => $runId,
            'semester_tipe' => 'ganjil',
            'status' => 'completed',
        ]);

        $snapshot = $snapshots->createSnapshot($scope, 'before_generate', null, [
            'semester_tipe' => 'ganjil',
            'jadwals' => [['kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0]]],
        ], $runId);
        $genapSnapshot = $snapshots->createSnapshot($scope, 'before_generate', null, [
            'semester_tipe' => 'genap',
            'jadwals' => [['kelas_kuliah_id' => $seed['kelas_kuliah_ids'][1]]],
        ], $runId);
        $this->assertNotNull($snapshot);
        $this->assertNotNull($genapSnapshot);
        $this->assertSame($genapSnapshot->id, $snapshots->latestAvailableSnapshot($scope)->id);
        $this->assertSame($snapshot->id, $snapshots->latestAvailableSnapshot($scope, 'ganjil')->id);
        $this->assertSame($genapSnapshot->id, $snapshots->latestAvailableSnapshot($scope, 'genap')->id);
        $this->assertFalse($snapshots->markAsRestored(9999));
        $this->assertTrue($snapshots->markAsRestored($snapshot->id));
        $this->assertNull($snapshots->latestAvailableSnapshot($scope, 'ganjil'));
    }

    public function test_progress_store_writes_status_completion_and_cancel_keys(): void
    {
        $cache = new FakeSchedulingCacheStore();
        Cache::shouldReceive('store')->with('database')->andReturn($cache);
        Log::spy();
        $store = new DatabaseSchedulingProgressStore();

        $store->put('55', [
            'status' => 'completed',
            'generation' => 10,
            'max_generation' => 10,
            'best_fitness' => 0.95,
            'started_at' => now()->subSeconds(2)->toISOString(),
            'scope_label' => 'S1 Coverage',
            'result' => ['scheduled' => [['id' => 1]], 'unscheduled' => []],
        ], 3600);
        $store->requestCancel('55');
        $store->forgetCancel('55');

        $this->assertSame('completed', $store->get('55')['status']);
        $this->assertArrayNotHasKey('ga_cancel_55', $cache->values);
        Log::shouldHaveReceived('info')->twice();
    }

    public function test_conflict_lookup_and_mutation_repository_find_existing_schedule(): void
    {
        $seed = $this->seedSchedulingFixture();
        DB::table('kelas_kuliah_dosen')->insert([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0],
            'dosen_id' => $seed['dosen_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jadwal')->insert([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0],
            'slot_id' => $seed['slot_ids'][0],
            'ruangan_id' => $seed['ruangan_ids'][0],
            'origin' => 'manual',
        ]);
        $scope = ['restrict_by_program_studi' => false, 'restrict_by_jurusan' => false];
        $lookup = new SchedulingConflictLookup();

        $this->assertSame($seed['kelas_kuliah_ids'][0], $lookup->findKelasKuliahById($seed['kelas_kuliah_ids'][0], $scope)->id);
        $this->assertSame($seed['kelas_kuliah_ids'][0], $lookup->findByRuangan($seed['slot_ids'][0], $seed['ruangan_ids'][0], $seed['kelas_kuliah_ids'][1])->id);
        $this->assertSame($seed['kelas_kuliah_ids'][0], $lookup->findByKelas($seed['kelas_id'], $seed['slot_ids'][0], $seed['kelas_kuliah_ids'][1])->id);
        $this->assertNull($lookup->findByDosen([], $seed['slot_ids'][0], $seed['kelas_kuliah_ids'][1]));
        $this->assertSame($seed['kelas_kuliah_ids'][0], $lookup->findByDosen([$seed['dosen_id']], $seed['slot_ids'][0], $seed['kelas_kuliah_ids'][1])->id);

        $mutations = new EloquentSchedulingMutationRepository();
        $model = $mutations->findKelasKuliahById($seed['kelas_kuliah_ids'][0], $scope);
        $this->assertSame([$seed['dosen_id']], $mutations->getDosenIdsForKelasKuliah($model));
        $this->assertSame([], $mutations->getDosenIdsForKelasKuliah((object) []));
        $this->assertSame($seed['kelas_kuliah_ids'][0], $mutations->findConflictByRuangan($seed['slot_ids'][0], $seed['ruangan_ids'][0], $seed['kelas_kuliah_ids'][1])->id);
        $this->assertSame($seed['kelas_kuliah_ids'][0], $mutations->findConflictByKelas($seed['kelas_id'], $seed['slot_ids'][0], $seed['kelas_kuliah_ids'][1])->id);
        $this->assertSame($seed['kelas_kuliah_ids'][0], $mutations->findConflictByDosen([$seed['dosen_id']], $seed['slot_ids'][0], $seed['kelas_kuliah_ids'][1])->id);
    }

    public function test_scheduling_query_repository_lists_searches_paginates_and_reads_dataset(): void
    {
        $seed = $this->seedSchedulingFixture();
        DB::table('jadwal')->insert([
            'kelas_kuliah_id' => $seed['kelas_kuliah_ids'][0],
            'slot_id' => $seed['slot_ids'][0],
            'ruangan_id' => $seed['ruangan_ids'][0],
            'origin' => 'manual',
        ]);
        $repository = new EloquentSchedulingQueryRepository();
        $scope = [
            'is_restricted' => false,
            'restrict_by_jurusan' => false,
            'restrict_by_program_studi' => false,
        ];

        $scheduled = $repository->getKelasKuliahList([
            'search' => 'Coverage',
            'is_scheduled' => true,
            'program_studi_id' => $seed['program_studi_id'],
            'jurusan_id' => $seed['jurusan_id'],
            'semester_tipe' => 'ganjil',
        ], 'all');
        $unscheduled = $repository->getKelasKuliahList(['is_scheduled' => false], 1);

        $this->assertCount(1, $scheduled);
        $this->assertSame($seed['slot_ids'][0], $scheduled->first()->slot_id);
        $this->assertSame(1, $unscheduled->total());
        $this->assertSame($seed['kelas_kuliah_ids'][0], $repository->findKelasKuliahDetail($seed['kelas_kuliah_ids'][0], $scope)->id);
        $this->assertNull($repository->findKelasKuliahDetail(9999, $scope));
        $this->assertArrayHasKey('slots', $repository->getKelasKuliahStats('ganjil', $scope));
        $this->assertArrayHasKey('kuliah', $repository->getGenerationDataset($scope, 'ganjil'));
    }

    private function seedSchedulingFixture(): array
    {
        DB::table('hari')->insert(['id' => 1, 'nama_hari' => 'Senin']);
        DB::table('waktu')->insert([
            ['id' => 1, 'pukul' => '08.00 - 09.40', 'sks' => 2, 'jam_index' => 1],
            ['id' => 2, 'pukul' => '09.50 - 11.30', 'sks' => 2, 'jam_index' => 2],
        ]);
        DB::table('slot')->insert([
            ['id' => 1, 'hari_id' => 1, 'waktu_id' => 1],
            ['id' => 2, 'hari_id' => 1, 'waktu_id' => 2],
        ]);
        DB::table('gedung')->insert(['id' => 1, 'nama_gedung' => 'Gedung Coverage']);
        DB::table('jurusan')->insert(['id' => 1, 'nama_jurusan' => 'Teknik Coverage']);
        DB::table('program_studi')->insert(['id' => 1, 'jurusan_id' => 1, 'nama_prodi' => 'S1 Coverage']);
        DB::table('ruangan')->insert([
            ['id' => 1, 'gedung_id' => 1, 'ruangan' => 'CVG-101', 'kapasitas' => 40],
            ['id' => 2, 'gedung_id' => 1, 'ruangan' => 'CVG-102', 'kapasitas' => 50],
        ]);
        DB::table('dosen')->insert([
            'id' => 1,
            'jurusan_id' => 1,
            'nip' => '199001012026061001',
            'nama_lengkap' => 'Dosen Coverage',
            'inisial' => 'DC',
        ]);
        DB::table('matakuliah')->insert([
            'id' => 1,
            'program_studi_id' => 1,
            'jurusan_id' => 1,
            'kode_mk' => 'CVG101',
            'nama_mk' => 'Coverage Scheduling',
            'sks' => 2,
            'semester' => 1,
        ]);
        DB::table('kelas')->insert(['id' => 1, 'program_studi_id' => 1, 'jurusan_id' => 1, 'nama_kelas' => 'CVG-A', 'semester' => 1]);
        DB::table('kelas_kuliah')->insert([
            ['id' => 1, 'dosen_id' => 1, 'matakuliah_id' => 1, 'kelas_id' => 1, 'jumlah_mahasiswa' => 30, 'semester_tipe' => 'ganjil'],
            ['id' => 2, 'dosen_id' => 1, 'matakuliah_id' => 1, 'kelas_id' => 1, 'jumlah_mahasiswa' => 35, 'semester_tipe' => 'ganjil'],
        ]);

        return [
            'jurusan_id' => 1,
            'program_studi_id' => 1,
            'dosen_id' => 1,
            'kelas_id' => 1,
            'kelas_kuliah_ids' => [1, 2],
            'slot_ids' => [1, 2],
            'ruangan_ids' => [1, 2],
        ];
    }
}

class FakeSchedulingCacheStore
{
    public array $values = [];

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $this->values[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }
}
