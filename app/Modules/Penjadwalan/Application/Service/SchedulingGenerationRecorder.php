<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Application\Port\SchedulingProgressStorePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingRunRepositoryPort;
use Illuminate\Database\UniqueConstraintViolationException;

final class SchedulingGenerationRecorder
{
    public function __construct(
        private readonly SchedulingProgressStorePort $progressStore,
        private readonly SchedulingRunRepositoryPort $runRepository,
        private readonly SchedulingGenerationNotifier $notifier,
    ) {}

    public function markProcessing(?int $runId): void
    {
        $this->runRepository->markProcessing($runId);
    }

    public function recordCompleted(
        string $userId,
        ?string $semesterTipe,
        array $scope,
        ?int $runId,
        string $startedAt,
        array $result,
    ): void {
        $status = SchedulingGenerationStatus::fromResult($result);
        $previous = $this->progressStore->get($userId) ?? [];

        $this->progressStore->put($userId, ResultSchedulingProgressPayload::make($result, $previous, $scope, $status, $startedAt), 3600);
        $this->runRepository->markFinished($runId, $status, $result);
        $this->notifier->completed($userId, $semesterTipe, $scope, $status, $result);
    }

    public function recordFailed(
        string $userId,
        ?string $semesterTipe,
        array $scope,
        ?int $runId,
        string $startedAt,
        \Throwable $exception,
    ): void {
        $message = $this->failureMessage($exception);
        $previous = $this->progressStore->get($userId) ?? [];

        $this->progressStore->put($userId, FailedSchedulingProgressPayload::make($previous, $scope, $message, $startedAt), 3600);
        $this->runRepository->markFailed($runId, $message);
        $this->notifier->failed($userId, $semesterTipe, $scope, $message);
    }

    private function failureMessage(\Throwable $exception): string
    {
        if (
            $exception instanceof UniqueConstraintViolationException
            && str_contains($exception->getMessage(), 'jadwal_slot_ruangan_unique')
        ) {
            return 'Gagal menyimpan hasil generate karena ada slot dan ruangan yang sudah dipakai jadwal lain.';
        }

        return 'Proses generate berhenti karena terjadi kesalahan saat menyimpan hasil jadwal.';
    }
}
