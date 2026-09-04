<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Penjadwalan\Application\Port\SchedulingProgressStorePort;

class SchedulingAppService
{
    public function __construct(
        protected SchedulingPreviewBuilder $previewBuilder,
        protected SchedulingGenerationLifecycle $generationLifecycle,
        protected SchedulingSnapshotManager $snapshotManager,
        protected SchedulingProgressStorePort $progressStore,
    ) {}

    public function preview(int $programStudiId, ?string $semesterTipe, array $scope): array
    {
        return $this->previewBuilder->preview($programStudiId, $semesterTipe, $scope);
    }

    public function generate(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        return $this->generationLifecycle->queue($programStudiId, $semesterTipe, $scope, $params);
    }

    public function cancel(): array
    {
        return $this->generationLifecycle->cancel();
    }

    public function restoreLast(array $scope, ?string $semesterTipe = null): array
    {
        return $this->snapshotManager->restoreLast($scope, $semesterTipe);
    }

    public function clearAll(array $scope, ?string $semesterTipe = null): array
    {
        return $this->snapshotManager->clearAll($scope, $semesterTipe);
    }

    public function resetAuto(): array
    {
        return ['message' => 'Jadwal otomatis telah direset'];
    }

    public function progress(string $userId): ?array
    {
        return $this->progressStore->get($userId);
    }

    public function processGenerationInBackground(int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        $this->snapshotManager->clearBeforeGeneration($scope, $semesterTipe, $params);

        $result = $this->generationLifecycle->process($programStudiId, $semesterTipe, $scope, $params);

        if ($result['canceled'] ?? false) {
            $result['restore'] = $this->snapshotManager->restoreLast($scope, $semesterTipe);
        }

        return $result;
    }
}
