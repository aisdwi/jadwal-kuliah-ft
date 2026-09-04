<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Application\Port\SchedulingJobDispatcherPort;
use App\Modules\Penjadwalan\Application\Port\SchedulingProgressStorePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingRunRepositoryPort;

final class SchedulingQueueStarter
{
    public function __construct(
        private readonly SchedulingProgressStorePort $progressStore,
        private readonly SchedulingJobDispatcherPort $jobDispatcher,
        private readonly SchedulingRunRepositoryPort $runRepository,
        private readonly SchedulingGenerationNotifier $notifier,
    ) {}

    public function start(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        $userId = (string) ($scope['user_id'] ?? auth()?->id() ?? 'system');
        $normalizedParams = SchedulingGenerationParams::normalize($params);
        $previous = $this->progressStore->get($userId) ?? [];

        if ($this->hasActiveGeneration($previous)) {
            return [
                'success' => false,
                'message' => 'Generate jadwal masih berjalan untuk akun ini.',
            ];
        }

        $runId = $this->runRepository->createQueued($userId, $semesterTipe, $scope, $normalizedParams);
        if ($runId !== null) {
            $normalizedParams['scheduling_run_id'] = $runId;
        }

        $this->progressStore->forgetCancel($userId);
        $this->progressStore->put($userId, QueuedSchedulingProgressPayload::make($normalizedParams, $scope), 3600);
        $this->notifier->queued($userId, $semesterTipe, $scope);
        $this->jobDispatcher->dispatchRunGeneration($programStudiId, $semesterTipe, $scope, $normalizedParams);

        return [
            'success' => true,
            'message' => 'Penjadwalan sedang diproses di background',
        ];
    }

    public function cancel(): array
    {
        $userId = (string) (auth()?->id() ?? 'system');
        $previous = $this->progressStore->get($userId) ?? [];

        $this->progressStore->requestCancel($userId);
        $this->progressStore->put($userId, CanceledSchedulingProgressPayload::make($previous), 3600);
        $this->notifier->canceled($userId, $previous);

        return ['message' => 'Permintaan pembatalan dikirim'];
    }

    private function hasActiveGeneration(array $payload): bool
    {
        return in_array($payload['status'] ?? null, ['queued', 'processing'], true);
    }
}
