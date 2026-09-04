<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\Penjadwalan\Application\Port\SchedulingSnapshotRepositoryPort;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\SchedulingOperationSnapshotModel;
use Illuminate\Support\Facades\Schema;

class EloquentSchedulingSnapshotRepository implements SchedulingSnapshotRepositoryPort
{
    public function hasSnapshotTable(): bool
    {
        return Schema::hasTable('scheduling_operation_snapshots');
    }

    public function createSnapshot(array $scope, ?string $actionType, ?int $userId, array $kelasKuliahPayload, ?int $schedulingRunId = null): ?object
    {
        if (!$this->hasSnapshotTable()) {
            return null;
        }

        $snapshotCount = is_array($kelasKuliahPayload['jadwals'] ?? null)
            ? count($kelasKuliahPayload['jadwals'])
            : count($kelasKuliahPayload);

        return SchedulingOperationSnapshotModel::create([
            'user_id'          => $userId,
            'jurusan_id'       => !empty($scope['restrict_by_jurusan']) ? ($scope['jurusan_id'] ?? null) : null,
            'program_studi_id' => !empty($scope['restrict_by_program_studi']) ? ($scope['program_studi_id'] ?? null) : null,
            'scheduling_run_id' => $schedulingRunId,
            'scope_label'      => $scope['label'] ?? null,
            'action_type'      => $actionType,
            'snapshot_count'   => $snapshotCount,
            'snapshot_payload' => $kelasKuliahPayload,
        ]);
    }

    public function latestAvailableSnapshot(array $scope, ?string $semesterTipe = null): ?object
    {
        if (!$this->hasSnapshotTable()) {
            return null;
        }

        $programStudiId = $scope['program_studi_id'] ?? null;
        $jurusanId      = $scope['jurusan_id'] ?? null;
        $byProdi        = !empty($scope['restrict_by_program_studi']) && $programStudiId;
        $byJurusan      = !empty($scope['restrict_by_jurusan']) && $jurusanId;

        $tipe = strtolower((string) $semesterTipe);

        return SchedulingOperationSnapshotModel::query()
            ->when($byProdi, function ($query) use ($programStudiId) {
                $query->where('program_studi_id', $programStudiId);
            }, function ($query) use ($byJurusan, $jurusanId) {
                if ($byJurusan) {
                    $query->whereNull('program_studi_id')
                          ->where('jurusan_id', $jurusanId);
                } else {
                    $query->whereNull('program_studi_id')
                          ->whereNull('jurusan_id');
                }
            })
            ->when(in_array($tipe, ['ganjil', 'genap'], true), function ($query) use ($tipe) {
                $query->where('snapshot_payload->semester_tipe', $tipe);
            })
            ->whereNull('restored_at')
            ->latest('id')
            ->first();
    }

    public function markAsRestored(int $snapshotId): bool
    {
        if (!$this->hasSnapshotTable()) {
            return false;
        }

        $snapshot = SchedulingOperationSnapshotModel::find($snapshotId);
        if (!$snapshot) {
            return false;
        }

        return $snapshot->update(['restored_at' => now()]);
    }
}
