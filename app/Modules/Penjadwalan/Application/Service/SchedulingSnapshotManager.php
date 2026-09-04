<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Application\Port\SchedulingSnapshotRepositoryPort;
use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;

final class SchedulingSnapshotManager
{
    public function __construct(
        private readonly SchedulingSnapshotRepositoryPort $snapshotRepository,
        private readonly JadwalRepository $jadwalRepository,
    ) {}

    public function restoreLast(array $scope, ?string $semesterTipe = null): array
    {
        $latestSnapshot = $this->snapshotRepository->latestAvailableSnapshot($scope, $semesterTipe);
        if (!$latestSnapshot) {
            return ['message' => 'Tidak ada snapshot untuk dipulihkan'];
        }

        $payload = $this->snapshotPayload($latestSnapshot);
        $snapshotSemesterTipe = $payload['semester_tipe'] ?? null;
        $targetSemesterTipe = is_string($semesterTipe) && $semesterTipe !== ''
            ? $semesterTipe
            : $snapshotSemesterTipe;
        $schedules = $payload['jadwals'] ?? $payload;

        $this->jadwalRepository->deleteGeneratedForSchedulingScope($scope, is_string($targetSemesterTipe) ? $targetSemesterTipe : null);
        $restoredCount = $this->jadwalRepository->restoreGeneratedSchedules(is_array($schedules) ? $schedules : []);
        $this->snapshotRepository->markAsRestored($latestSnapshot->id);

        return [
            'message' => 'Hasil generate berhasil dipulihkan dari snapshot',
            'snapshot_id' => $latestSnapshot->id,
            'restored_count' => $restoredCount,
        ];
    }

    public function clearAll(array $scope, ?string $semesterTipe = null): array
    {
        $this->snapshotGeneratedSchedules($scope, $semesterTipe, 'clear');
        $deletedCount = $this->jadwalRepository->deleteGeneratedForSchedulingScope($scope, $semesterTipe);

        return [
            'message' => 'Hasil generate telah dihapus',
            'deleted_count' => $deletedCount,
        ];
    }

    public function clearBeforeGeneration(array $scope, ?string $semesterTipe, array $params): void
    {
        $this->snapshotGeneratedSchedules($scope, $semesterTipe, 'before_generate', $this->schedulingRunId($params));
    }

    private function snapshotGeneratedSchedules(array $scope, ?string $semesterTipe, string $actionType, ?int $schedulingRunId = null): void
    {
        $payload = [
            'semester_tipe' => $semesterTipe,
            'scheduling_run_id' => $schedulingRunId,
            'jadwals' => $this->jadwalRepository->findGeneratedForSchedulingScope($scope, $semesterTipe),
        ];

        $this->snapshotRepository->createSnapshot(
            $scope,
            $actionType,
            empty($scope['user_id']) ? null : (int) $scope['user_id'],
            $payload,
            $schedulingRunId,
        );
    }

    private function snapshotPayload(object $snapshot): array
    {
        $payload = $snapshot->snapshot_payload ?? [];

        return is_array($payload) ? $payload : [];
    }

    private function schedulingRunId(array $params): ?int
    {
        $value = $params['scheduling_run_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
