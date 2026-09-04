<?php

namespace App\Modules\Dashboard\Domain\Repositories;

interface DashboardRepository
{
    public function getStats(array $userScope): array;

    public function getChartData(array $userScope): array;

    public function getRecentActivity(array $userScope, int $limit = 10): array;
}
