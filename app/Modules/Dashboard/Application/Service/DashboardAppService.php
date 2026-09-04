<?php

namespace App\Modules\Dashboard\Application\Service;

use App\Modules\Dashboard\Domain\Repositories\DashboardRepository;

class DashboardAppService
{
    public function __construct(
        protected DashboardRepository $repo,
    ) {}

    public function stats(array $scope): array
    {
        return $this->repo->getStats($scope);
    }

    public function chart(array $scope): array
    {
        return $this->repo->getChartData($scope);
    }

    public function activity(array $scope, int $limit = 10): array
    {
        return $this->repo->getRecentActivity($scope, $limit);
    }
}
