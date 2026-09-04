<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Dashboard\Domain\Repositories\DashboardRepository;

class EloquentDashboardRepository implements DashboardRepository
{
    public function getStats(array $userScope): array
    {
        return (new DashboardStatsQuery())->getStats($userScope);
    }

    public function getChartData(array $userScope): array
    {
        return (new DashboardChartQuery())->getChartData($userScope);
    }

    public function getRecentActivity(array $userScope, int $limit = 10): array
    {
        return (new DashboardActivityQuery())->getRecentActivity($userScope, $limit);
    }
}
