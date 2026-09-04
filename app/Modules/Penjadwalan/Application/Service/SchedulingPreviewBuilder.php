<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Application\Port\SchedulingQueryRepositoryPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingSnapshotRepositoryPort;

final class SchedulingPreviewBuilder
{
    public function __construct(
        private readonly SchedulingQueryRepositoryPort $queryRepository,
        private readonly SchedulingSnapshotRepositoryPort $snapshotRepository,
    ) {}

    public function preview(int $programStudiId, ?string $semesterTipe, array $scope): array
    {
        $filters = $this->filters($programStudiId, $semesterTipe, $scope);
        $allClasses = $this->queryRepository->getKelasKuliahList($filters, 'all');
        $scheduledCount = $this->queryRepository->getKelasKuliahList(
            array_merge($filters, ['is_scheduled' => true]),
            'all',
        )->count();

        $stats = $this->queryRepository->getKelasKuliahStats($semesterTipe, $scope);
        $hasSnapshotTable = $this->snapshotRepository->hasSnapshotTable();
        $latestSnapshot = $hasSnapshotTable ? $this->snapshotRepository->latestAvailableSnapshot($scope, $semesterTipe) : null;

        return [
            'all_classes' => $allClasses->count(),
            'total_kelas_kuliah' => $allClasses->count(),
            'scheduled_count' => $scheduledCount,
            'unscheduled_count' => $allClasses->count() - $scheduledCount,
            'generated_count' => (int) ($stats['classes']['generated_count'] ?? 0),
            'slots_unit_count' => $stats['slots']['unit_count'] ?? 0,
            'slots_room_count' => $stats['slots']['room_count'] ?? 0,
            'slots_total' => $stats['slots']['total'] ?? 0,
            'max_capacity' => $stats['slots']['total'] ?? 0,
            'scope_label' => $scope['label'] ?? 'Semua Data',
            'has_snapshot' => $hasSnapshotTable,
            'latest_snapshot' => $latestSnapshot ? $this->snapshotPayload($latestSnapshot) : null,
        ];
    }

    private function filters(int $programStudiId, ?string $semesterTipe, array $scope): array
    {
        $filters = [];
        if (!empty($scope['restrict_by_jurusan']) && !empty($scope['jurusan_id'])) {
            $filters['jurusan_id'] = $scope['jurusan_id'];
        } elseif ($programStudiId > 0) {
            $filters['program_studi_id'] = $programStudiId;
        }
        if ($semesterTipe) {
            $filters['semester_tipe'] = $semesterTipe;
        }

        return $filters;
    }

    private function snapshotPayload(object $snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'action_type' => $snapshot->action_type,
            'created_at' => $this->formatTimestamp($snapshot->created_at ?? null),
            'snapshot_count' => $snapshot->snapshot_count,
        ];
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'toISOString')) {
            return $value->toISOString();
        }

        return $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : (string) $value;
    }
}
