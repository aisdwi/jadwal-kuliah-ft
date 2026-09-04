<?php

namespace App\Modules\Penjadwalan\Application\Port;

interface GeneticAlgorithmEnginePort
{
    public function run(array $dataset, int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array;
}
