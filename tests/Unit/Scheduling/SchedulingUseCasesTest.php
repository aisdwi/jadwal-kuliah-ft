<?php

namespace Tests\Unit\Scheduling;

use App\Modules\Penjadwalan\Application\Port\GeneticAlgorithmEnginePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingJobDispatcherPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingProgressStorePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingQueryRepositoryPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingRunRepositoryPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingSnapshotRepositoryPort;
use App\Modules\Penjadwalan\Application\Service\SchedulingAppService;
use App\Modules\Penjadwalan\Application\Service\SchedulingGenerationLifecycle;
use App\Modules\Penjadwalan\Application\Service\SchedulingGenerationNotifier;
use App\Modules\Penjadwalan\Application\Service\SchedulingGenerationRecorder;
use App\Modules\Penjadwalan\Application\Service\SchedulingPreviewBuilder;
use App\Modules\Penjadwalan\Application\Service\SchedulingQueueStarter;
use App\Modules\Penjadwalan\Application\Service\SchedulingSnapshotManager;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use App\Modules\Shared\Application\Service\NotificationWriter;
use PHPUnit\Framework\TestCase;

class SchedulingUseCasesTest extends TestCase
{
    public function test_preview_returns_counts_and_latest_snapshot_for_scope(): void
    {
        $queryRepository = new FakeSchedulingQueryRepository();
        $queryRepository->kelasKuliahList = collect([(object) ['id' => 1], (object) ['id' => 2], (object) ['id' => 3]]);
        $queryRepository->scheduledKelasKuliahList = collect([(object) ['id' => 2]]);
        $queryRepository->stats = [
            'slots' => ['total' => 10, 'unit_count' => 6, 'room_count' => 4],
            'classes' => ['generated_count' => 1],
        ];

        $snapshotRepository = new FakeSchedulingSnapshotRepository();
        $snapshotRepository->latestSnapshot = (object) [
            'id' => 9,
            'action_type' => 'before_generate',
            'created_at' => new \DateTimeImmutable('2026-04-16T07:00:00+07:00'),
            'snapshot_count' => 3,
        ];

        $service = $this->makeService(queryRepository: $queryRepository, snapshotRepository: $snapshotRepository);

        $result = $service->preview(12, 'ganjil', [
            'restrict_by_jurusan' => true,
            'jurusan_id' => 4,
            'label' => 'Jurusan Teknik Informatika',
        ]);

        $this->assertSame(3, $result['all_classes']);
        $this->assertSame(1, $result['scheduled_count']);
        $this->assertSame(2, $result['unscheduled_count']);
        $this->assertSame(1, $result['generated_count']);
        $this->assertSame(10, $result['max_capacity']);
        $this->assertSame('Jurusan Teknik Informatika', $result['scope_label']);
        $this->assertSame(9, $result['latest_snapshot']['id']);
        $this->assertSame([
            'jurusan_id' => 4,
            'semester_tipe' => 'ganjil',
            'is_scheduled' => true,
        ], $queryRepository->lastScheduledFilters);
    }

    public function test_generate_queues_background_job_and_records_progress(): void
    {
        $progressStore = new FakeSchedulingProgressStore();
        $jobDispatcher = new FakeSchedulingJobDispatcher();
        $notifications = new FakeNotificationService();

        $service = $this->makeService(
            progressStore: $progressStore,
            jobDispatcher: $jobDispatcher,
            notificationService: $notifications,
        );

        $result = $service->generate(7, 'genap', [
            'user_id' => 55,
            'label' => 'Prodi Sistem Informasi',
        ], [
            'num_kromosom' => 5,
            'max_generation' => 999,
            'crossover_rate' => 101,
            'mutation_rate' => 0,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('queued', $progressStore->payloads['55']['status']);
        $this->assertSame(500, $progressStore->payloads['55']['max_generation']);
        $this->assertSame([7, 'genap'], [$jobDispatcher->programStudiId, $jobDispatcher->semesterTipe]);
        $this->assertSame([
            'num_kromosom' => 10,
            'max_generation' => 500,
            'crossover_rate' => 100,
            'mutation_rate' => 1,
            'scheduling_run_id' => 77,
        ], $jobDispatcher->params);
        $this->assertSame('schedule_generation_queued', $notifications->created[0]['type']);
    }

    public function test_generate_does_not_dispatch_duplicate_job_for_same_active_user(): void
    {
        $progressStore = new FakeSchedulingProgressStore();
        $progressStore->payloads['55'] = ['status' => 'processing'];
        $jobDispatcher = new FakeSchedulingJobDispatcher();
        $notifications = new FakeNotificationService();

        $service = $this->makeService(
            progressStore: $progressStore,
            jobDispatcher: $jobDispatcher,
            notificationService: $notifications,
        );

        $result = $service->generate(7, 'genap', ['user_id' => 55], []);

        $this->assertFalse($result['success']);
        $this->assertSame('Generate jadwal masih berjalan untuk akun ini.', $result['message']);
        $this->assertSame(0, $jobDispatcher->dispatchCount);
        $this->assertSame([], $notifications->created);
    }

    public function test_process_generation_updates_progress_when_completed(): void
    {
        $queryRepository = new FakeSchedulingQueryRepository();
        $queryRepository->generationDataset = ['classes' => [['id' => 1]]];
        $progressStore = new FakeSchedulingProgressStore();
        $engine = new FakeGeneticAlgorithmEngine([
            'success' => true,
            'generation' => 12,
            'best_fitness' => 0.98,
            'logs' => [['type' => 'success', 'message' => 'done']],
            'result' => ['max_generation' => 50],
            'results' => [['kelas_kuliah_id' => 1]],
        ]);
        $notifications = new FakeNotificationService();
        $jadwalRepository = new FakeJadwalRepository();
        $snapshotRepository = new FakeSchedulingSnapshotRepository();

        $service = $this->makeService(
            queryRepository: $queryRepository,
            progressStore: $progressStore,
            geneticAlgorithm: $engine,
            notificationService: $notifications,
            snapshotRepository: $snapshotRepository,
            jadwalRepository: $jadwalRepository,
        );

        $result = $service->processGenerationInBackground(7, 'ganjil', [
            'user_id' => 55,
            'label' => 'Prodi Sistem Informasi',
        ], ['max_generation' => 50]);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $progressStore->payloads['55']['status']);
        $this->assertSame(12, $progressStore->payloads['55']['generation']);
        $this->assertSame(0.98, $progressStore->payloads['55']['best_fitness']);
        $this->assertSame('schedule_generation_completed', $notifications->created[0]['type']);
        $this->assertSame($queryRepository->generationDataset, $engine->dataset);
        $this->assertSame('before_generate', $snapshotRepository->createdSnapshots[0]['actionType']);
        $this->assertNull($jadwalRepository->deleteGeneratedScope);
    }

    public function test_progress_reads_user_progress_payload(): void
    {
        $progressStore = new FakeSchedulingProgressStore();
        $progressStore->payloads['55'] = ['status' => 'completed'];

        $service = $this->makeService(progressStore: $progressStore);

        $this->assertSame(['status' => 'completed'], $service->progress('55'));
    }

    public function test_cancel_records_payload_and_notification_for_system_user(): void
    {
        app()->instance(\Illuminate\Contracts\Auth\Factory::class, new class implements \Illuminate\Contracts\Auth\Factory {
            public function guard($name = null): \Illuminate\Contracts\Auth\Guard
            {
                throw new \LogicException('Guard resolution is not expected in this test.');
            }

            public function shouldUse($name): void {}

            public function id(): ?int
            {
                return null;
            }
        });

        $progressStore = new FakeSchedulingProgressStore();
        $progressStore->payloads['system'] = [
            'generation' => 8,
            'max_generation' => 50,
            'best_fitness' => 0.75,
            'logs' => [],
            'scope_label' => 'Semua Data',
        ];
        $notifications = new FakeNotificationService();

        $result = $this->makeService(
            progressStore: $progressStore,
            notificationService: $notifications,
        )->cancel();

        $this->assertSame('Permintaan pembatalan dikirim', $result['message']);
        $this->assertTrue($progressStore->cancelRequests['system']);
        $this->assertSame('canceled', $progressStore->payloads['system']['status']);
        $this->assertSame(8, $progressStore->payloads['system']['generation']);
        $this->assertSame('schedule_generation_canceled', $notifications->created[0]['type']);
    }

    public function test_restore_last_reports_when_no_snapshot_exists(): void
    {
        $result = $this->makeService()->restoreLast(['restrict_by_jurusan' => false]);

        $this->assertSame(['message' => 'Tidak ada snapshot untuk dipulihkan'], $result);
    }

    public function test_reset_auto_returns_confirmation(): void
    {
        $this->assertSame(
            ['message' => 'Jadwal otomatis telah direset'],
            $this->makeService()->resetAuto(),
        );
    }

    public function test_process_generation_records_canceled_result(): void
    {
        $progressStore = new FakeSchedulingProgressStore();
        $notifications = new FakeNotificationService();
        $jadwalRepository = new FakeJadwalRepository();
        $jadwalRepository->generatedSchedules = [
            ['kelas_kuliah_id' => 7, 'slot_id' => 8, 'ruangan_id' => 9, 'origin' => 'generated'],
        ];
        $snapshotRepository = new FakeSchedulingSnapshotRepository();
        $engine = new FakeGeneticAlgorithmEngine([
            'success' => false,
            'canceled' => true,
            'generation' => 3,
            'fitness' => 0.25,
            'message' => 'stopped',
        ]);

        $result = $this->makeService(
            progressStore: $progressStore,
            geneticAlgorithm: $engine,
            notificationService: $notifications,
            snapshotRepository: $snapshotRepository,
            jadwalRepository: $jadwalRepository,
        )->processGenerationInBackground(7, 'ganjil', ['user_id' => 55], []);

        $this->assertTrue($result['canceled']);
        $this->assertSame('canceled', $progressStore->payloads['55']['status']);
        $this->assertSame(0.25, $progressStore->payloads['55']['best_fitness']);
        $this->assertSame('schedule_generation_canceled', $notifications->created[0]['type']);
        $this->assertSame('ganjil', $jadwalRepository->deleteGeneratedSemesterTipe);
        $this->assertSame($jadwalRepository->generatedSchedules, $jadwalRepository->restoredSchedules);
        $this->assertSame([1], $snapshotRepository->restoredSnapshotIds);
    }

    public function test_process_generation_records_failure_before_rethrowing_exception(): void
    {
        $progressStore = new FakeSchedulingProgressStore();
        $notifications = new FakeNotificationService();
        $runRepository = new FakeSchedulingRunRepository();
        $engine = new FakeGeneticAlgorithmEngine(new \RuntimeException('database write failed'));

        try {
            $this->makeService(
                progressStore: $progressStore,
                runRepository: $runRepository,
                geneticAlgorithm: $engine,
                notificationService: $notifications,
            )->processGenerationInBackground(7, 'ganjil', ['user_id' => 55], [
                'scheduling_run_id' => '91',
            ]);
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('database write failed', $exception->getMessage());
        }

        $this->assertSame('failed', $progressStore->payloads['55']['status']);
        $this->assertSame(
            'Proses generate berhenti karena terjadi kesalahan saat menyimpan hasil jadwal.',
            $progressStore->payloads['55']['message'],
        );
        $this->assertSame(91, $runRepository->failed[0]['runId']);
        $this->assertSame('schedule_generation_failed', $notifications->created[0]['type']);
    }

    public function test_clear_all_only_deletes_generated_schedules_for_scope(): void
    {
        $jadwalRepository = new FakeJadwalRepository();
        $jadwalRepository->generatedSchedules = [
            ['kelas_kuliah_id' => 1, 'slot_id' => 2, 'ruangan_id' => 3, 'origin' => 'generated'],
        ];
        $snapshotRepository = new FakeSchedulingSnapshotRepository();

        $service = $this->makeService(
            snapshotRepository: $snapshotRepository,
            jadwalRepository: $jadwalRepository,
        );
        $scope = ['restrict_by_jurusan' => true, 'jurusan_id' => 4, 'user_id' => 55];

        $result = $service->clearAll($scope, 'ganjil');

        $this->assertSame('Hasil generate telah dihapus', $result['message']);
        $this->assertSame($scope, $jadwalRepository->deleteGeneratedScope);
        $this->assertSame('ganjil', $jadwalRepository->deleteGeneratedSemesterTipe);
        $this->assertSame(0, $jadwalRepository->deleteAllCalls);
        $this->assertSame('clear', $snapshotRepository->createdSnapshots[0]['actionType']);
        $this->assertSame($jadwalRepository->generatedSchedules, $snapshotRepository->createdSnapshots[0]['payload']['jadwals']);
    }

    public function test_restore_last_replaces_generated_schedules_from_snapshot_without_touching_manual(): void
    {
        $jadwalRepository = new FakeJadwalRepository();
        $snapshotRepository = new FakeSchedulingSnapshotRepository();
        $snapshotRepository->latestSnapshot = (object) [
            'id' => 9,
            'snapshot_payload' => [
                'semester_tipe' => 'genap',
                'jadwals' => [
                    ['kelas_kuliah_id' => 7, 'slot_id' => 8, 'ruangan_id' => 9, 'origin' => 'generated'],
                ],
            ],
        ];

        $service = $this->makeService(
            snapshotRepository: $snapshotRepository,
            jadwalRepository: $jadwalRepository,
        );
        $scope = ['restrict_by_jurusan' => true, 'jurusan_id' => 4];

        $result = $service->restoreLast($scope);

        $this->assertSame('Hasil generate berhasil dipulihkan dari snapshot', $result['message']);
        $this->assertSame($scope, $jadwalRepository->deleteGeneratedScope);
        $this->assertSame('genap', $jadwalRepository->deleteGeneratedSemesterTipe);
        $this->assertSame($snapshotRepository->latestSnapshot->snapshot_payload['jadwals'], $jadwalRepository->restoredSchedules);
        $this->assertSame([9], $snapshotRepository->restoredSnapshotIds);
    }

    public function test_restore_last_uses_requested_semester_when_available(): void
    {
        $jadwalRepository = new FakeJadwalRepository();
        $snapshotRepository = new FakeSchedulingSnapshotRepository();
        $snapshotRepository->latestSnapshot = (object) [
            'id' => 9,
            'snapshot_payload' => [
                'semester_tipe' => 'genap',
                'jadwals' => [
                    ['kelas_kuliah_id' => 7, 'slot_id' => 8, 'ruangan_id' => 9, 'origin' => 'generated'],
                ],
            ],
        ];

        $service = $this->makeService(
            snapshotRepository: $snapshotRepository,
            jadwalRepository: $jadwalRepository,
        );
        $scope = ['restrict_by_jurusan' => true, 'jurusan_id' => 4];

        $service->restoreLast($scope, 'ganjil');

        $this->assertSame('ganjil', $jadwalRepository->deleteGeneratedSemesterTipe);
    }

    private function makeService(
        ?FakeSchedulingQueryRepository $queryRepository = null,
        ?FakeSchedulingSnapshotRepository $snapshotRepository = null,
        ?FakeSchedulingProgressStore $progressStore = null,
        ?FakeSchedulingJobDispatcher $jobDispatcher = null,
        ?FakeSchedulingRunRepository $runRepository = null,
        ?FakeGeneticAlgorithmEngine $geneticAlgorithm = null,
        ?FakeNotificationService $notificationService = null,
        ?FakeJadwalRepository $jadwalRepository = null,
    ): SchedulingAppService {
        $queryRepository ??= new FakeSchedulingQueryRepository();
        $snapshotRepository ??= new FakeSchedulingSnapshotRepository();
        $progressStore ??= new FakeSchedulingProgressStore();
        $jobDispatcher ??= new FakeSchedulingJobDispatcher();
        $runRepository ??= new FakeSchedulingRunRepository();
        $geneticAlgorithm ??= new FakeGeneticAlgorithmEngine(['success' => true]);
        $notificationService ??= new FakeNotificationService();

        return new SchedulingAppService(
            new SchedulingPreviewBuilder($queryRepository, $snapshotRepository),
            new SchedulingGenerationLifecycle(
                $queryRepository,
                $geneticAlgorithm,
                new SchedulingQueueStarter(
                    $progressStore,
                    $jobDispatcher,
                    $runRepository,
                    new SchedulingGenerationNotifier($notificationService),
                ),
                new SchedulingGenerationRecorder(
                    $progressStore,
                    $runRepository,
                    new SchedulingGenerationNotifier($notificationService),
                ),
            ),
            new SchedulingSnapshotManager($snapshotRepository, $jadwalRepository ?? new FakeJadwalRepository()),
            $progressStore,
        );
    }
}

class FakeJadwalRepository implements JadwalRepository
{
    public array $generatedSchedules = [];
    public array $restoredSchedules = [];
    public ?array $deleteGeneratedScope = null;
    public ?string $deleteGeneratedSemesterTipe = null;
    public int $deleteAllCalls = 0;

    public function findAll(?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): array
    {
        return [];
    }

    public function findById(int $id)
    {
        return null;
    }

    public function findByKelasKuliah(int $kelasKuliahId)
    {
        return null;
    }

    public function findByProgramStudi(int $programStudiId, ?int $semester = null): array
    {
        return [];
    }

    public function findAssignmentsBySlot(int $slotId, ?int $ignoreKelasKuliahId = null): array
    {
        return [];
    }

    public function create(array $data)
    {
        return (object) $data;
    }

    public function update(int $id, array $data)
    {
        return (object) array_merge(['id' => $id], $data);
    }

    public function delete(int $id): void {}

    public function deleteByKelasKuliah(int $kelasKuliahId): void {}

    public function deleteAll(?int $programStudiId = null): int
    {
        $this->deleteAllCalls++;

        return 0;
    }

    public function deleteGeneratedByProgramStudi(int $programStudiId, ?int $semester = null): int
    {
        return 0;
    }

    public function findGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): array
    {
        return $this->generatedSchedules;
    }

    public function deleteGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): int
    {
        $this->deleteGeneratedScope = $scope;
        $this->deleteGeneratedSemesterTipe = $semesterTipe;

        return count($this->generatedSchedules);
    }

    public function restoreGeneratedSchedules(array $schedules): int
    {
        $this->restoredSchedules = $schedules;

        return count($schedules);
    }
}

class FakeSchedulingQueryRepository implements SchedulingQueryRepositoryPort
{
    public mixed $kelasKuliahList;
    public mixed $scheduledKelasKuliahList;
    public array $stats = ['slots' => ['total' => 0, 'unit_count' => 0, 'room_count' => 0]];
    public array $generationDataset = [];
    public array $lastScheduledFilters = [];

    public function __construct()
    {
        $this->kelasKuliahList = collect();
        $this->scheduledKelasKuliahList = collect();
    }

    public function getKelasKuliahList(array $filters, mixed $perPage): mixed
    {
        if (($filters['is_scheduled'] ?? false) === true) {
            $this->lastScheduledFilters = $filters;

            return $this->scheduledKelasKuliahList;
        }

        return $this->kelasKuliahList;
    }

    public function findKelasKuliahDetail(int $id, array $scope): ?object
    {
        return null;
    }

    public function getKelasKuliahStats(?string $semesterTipe, array $scope): array
    {
        return $this->stats;
    }

    public function getGenerationDataset(array $scope, ?string $semesterTipe): array
    {
        return $this->generationDataset;
    }
}

class FakeSchedulingSnapshotRepository implements SchedulingSnapshotRepositoryPort
{
    public ?object $latestSnapshot = null;
    public array $createdSnapshots = [];
    public array $restoredSnapshotIds = [];

    public function hasSnapshotTable(): bool
    {
        return true;
    }

    public function createSnapshot(array $scope, ?string $actionType, ?int $userId, array $kelasKuliahPayload, ?int $schedulingRunId = null): ?object
    {
        $snapshot = (object) [
            'id' => count($this->createdSnapshots) + 1,
            'action_type' => $actionType,
            'snapshot_count' => count($kelasKuliahPayload['jadwals'] ?? $kelasKuliahPayload),
            'snapshot_payload' => $kelasKuliahPayload,
        ];

        $this->createdSnapshots[] = [
            'scope' => $scope,
            'actionType' => $actionType,
            'userId' => $userId,
            'payload' => $kelasKuliahPayload,
            'schedulingRunId' => $schedulingRunId,
        ];
        $this->latestSnapshot = $snapshot;

        return $snapshot;
    }

    public function latestAvailableSnapshot(array $scope, ?string $semesterTipe = null): ?object
    {
        return $this->latestSnapshot;
    }

    public function markAsRestored(int $snapshotId): bool
    {
        $this->restoredSnapshotIds[] = $snapshotId;

        return true;
    }
}

class FakeSchedulingRunRepository implements SchedulingRunRepositoryPort
{
    public array $created = [];
    public array $processingIds = [];
    public array $finished = [];
    public array $failed = [];
    public ?int $nextId = 77;

    public function createQueued(string $userId, ?string $semesterTipe, array $scope, array $params): ?int
    {
        $this->created[] = compact('userId', 'semesterTipe', 'scope', 'params');

        return $this->nextId;
    }

    public function markProcessing(?int $runId): void
    {
        $this->processingIds[] = $runId;
    }

    public function markFinished(?int $runId, string $status, array $result): void
    {
        $this->finished[] = compact('runId', 'status', 'result');
    }

    public function markFailed(?int $runId, string $message): void
    {
        $this->failed[] = compact('runId', 'message');
    }
}

class FakeSchedulingProgressStore implements SchedulingProgressStorePort
{
    public array $payloads = [];
    public array $cancelRequests = [];

    public function get(string $userId): ?array
    {
        return $this->payloads[$userId] ?? null;
    }

    public function put(string $userId, array $payload, int $ttlSeconds): void
    {
        $this->payloads[$userId] = $payload;
    }

    public function forgetCancel(string $userId): void
    {
        unset($this->cancelRequests[$userId]);
    }

    public function requestCancel(string $userId): void
    {
        $this->cancelRequests[$userId] = true;
    }
}

class FakeSchedulingJobDispatcher implements SchedulingJobDispatcherPort
{
    public ?int $programStudiId = null;
    public ?string $semesterTipe = null;
    public array $scope = [];
    public array $params = [];
    public int $dispatchCount = 0;

    public function dispatchRunGeneration(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): void
    {
        $this->dispatchCount++;
        $this->programStudiId = $programStudiId;
        $this->semesterTipe = $semesterTipe;
        $this->scope = $scope;
        $this->params = $params;
    }
}

class FakeGeneticAlgorithmEngine implements GeneticAlgorithmEnginePort
{
    public array $dataset = [];

    public function __construct(private readonly array|\Throwable $result) {}

    public function run(array $dataset, int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        $this->dataset = $dataset;

        if ($this->result instanceof \Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}

class FakeNotificationService extends NotificationWriter
{
    public array $created = [];

    public function createForUser(
        int|string|null $userId,
        string $type,
        string $title,
        ?string $message = null,
        array $data = [],
        int|string|null $actorId = null,
    ): void {
        $this->created[] = compact('userId', 'type', 'title', 'message', 'data', 'actorId');
    }
}
