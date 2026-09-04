<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\Penjadwalan\Application\Port\SchedulingRunRepositoryPort;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\SchedulingRunModel;
use Illuminate\Support\Facades\Schema;

final class EloquentSchedulingRunRepository implements SchedulingRunRepositoryPort
{
    public function createQueued(string $userId, ?string $semesterTipe, array $scope, array $params): ?int
    {
        if (!$this->hasTable()) {
            return null;
        }

        $run = SchedulingRunModel::create([
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'jurusan_id' => !empty($scope['restrict_by_jurusan']) ? ($scope['jurusan_id'] ?? null) : null,
            'program_studi_id' => !empty($scope['restrict_by_program_studi']) ? ($scope['program_studi_id'] ?? null) : null,
            'semester_tipe' => $this->validSemesterTipe($semesterTipe),
            'scope_label' => $scope['label'] ?? 'Semua Data',
            'status' => 'queued',
            'params' => $params,
            'queued_at' => now(),
        ]);

        return (int) $run->id;
    }

    public function markProcessing(?int $runId): void
    {
        $this->updateRun($runId, [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markFinished(?int $runId, string $status, array $result): void
    {
        $this->updateRun($runId, [
            'status' => $status,
            'result_summary' => $this->summary($result),
            'message' => $result['message'] ?? null,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(?int $runId, string $message): void
    {
        $this->updateRun($runId, [
            'status' => 'failed',
            'message' => $message,
            'finished_at' => now(),
        ]);
    }

    private function updateRun(?int $runId, array $payload): void
    {
        if (!$this->hasTable() || empty($runId)) {
            return;
        }

        SchedulingRunModel::whereKey($runId)->update($payload);
    }

    private function hasTable(): bool
    {
        return Schema::hasTable('scheduling_runs');
    }

    private function validSemesterTipe(?string $semesterTipe): ?string
    {
        $value = strtolower(trim((string) $semesterTipe));

        return in_array($value, ['ganjil', 'genap'], true) ? $value : null;
    }

    private function summary(array $result): array
    {
        return [
            'success' => (bool) ($result['success'] ?? false),
            'canceled' => (bool) ($result['canceled'] ?? false),
            'generation' => (int) ($result['generation'] ?? 0),
            'best_fitness' => (float) ($result['best_fitness'] ?? ($result['fitness'] ?? 0)),
            'saved_count' => is_array($result['results'] ?? null) ? count($result['results']) : 0,
        ];
    }
}
