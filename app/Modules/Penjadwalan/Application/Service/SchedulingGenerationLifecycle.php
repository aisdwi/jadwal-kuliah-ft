<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Application\Port\GeneticAlgorithmEnginePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingQueryRepositoryPort;

final class SchedulingGenerationLifecycle
{
    public function __construct(
        private readonly SchedulingQueryRepositoryPort $queryRepository,
        private readonly GeneticAlgorithmEnginePort $geneticAlgorithm,
        private readonly SchedulingQueueStarter $queueStarter,
        private readonly SchedulingGenerationRecorder $recorder,
    ) {}

    public function queue(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        return $this->queueStarter->start($programStudiId, $semesterTipe, $scope, $params);
    }

    public function cancel(): array
    {
        return $this->queueStarter->cancel();
    }

    public function process(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        $userId = (string) ($scope['user_id'] ?? 'system');
        $startedAt = now()->toISOString();
        $normalizedParams = SchedulingGenerationParams::normalize($params);
        $runId = $this->schedulingRunId($params);
        if ($runId !== null) {
            $normalizedParams['scheduling_run_id'] = $runId;
        }

        try {
            $this->recorder->markProcessing($runId);
            $dataset = $this->queryRepository->getGenerationDataset($scope, $semesterTipe);
            $result = $this->geneticAlgorithm->run($dataset, $programStudiId, $semesterTipe, $scope, $normalizedParams);
            $this->recorder->recordCompleted($userId, $semesterTipe, $scope, $runId, $startedAt, $result);

            return $result;
        } catch (\Throwable $e) {
            $this->recorder->recordFailed($userId, $semesterTipe, $scope, $runId, $startedAt, $e);

            throw $e;
        }
    }

    private function schedulingRunId(array $params): ?int
    {
        $value = $params['scheduling_run_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
