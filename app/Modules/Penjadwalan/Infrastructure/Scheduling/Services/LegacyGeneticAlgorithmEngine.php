<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Services;

use App\Modules\Penjadwalan\Application\Port\GeneticAlgorithmEnginePort;
use App\Modules\Penjadwalan\Application\Port\SchedulingMutationRepositoryPort;
use App\Modules\Penjadwalan\Infrastructure\Scheduling\Legacy\AlgoritmaGenetika;

class LegacyGeneticAlgorithmEngine implements GeneticAlgorithmEnginePort
{
    public function __construct(
        private readonly SchedulingMutationRepositoryPort $mutationRepository,
    ) {}

    public function run(array $dataset, int $programStudiId, ?string $semesterTipe, array $scope, array $params = []): array
    {
        $algorithm = $this->makeAlgorithm($dataset);
        $this->configureAlgorithm($algorithm, $dataset, $programStudiId, $scope, $params);

        $results = [];
        $schedulingRunId = $this->schedulingRunId($params);
        $algorithm->resultPersister = function (array $assignments) use (&$results, $schedulingRunId): void {
            $assignmentsWithRun = $this->withSchedulingRun($assignments, $schedulingRunId);
            logger()->info('legacy_ga_result_persister', [
                'scheduling_run_id' => $schedulingRunId,
                'assignment_count' => count($assignmentsWithRun),
                'sample_kuliah' => array_slice(array_column($assignmentsWithRun, 'kuliah'), 0, 10),
                'sample_slots' => array_slice(array_column($assignmentsWithRun, 'slot'), 0, 10),
                'sample_ruang' => array_slice(array_column($assignmentsWithRun, 'ruang'), 0, 10),
            ]);
            $this->mutationRepository->persistGeneratedAssignments($assignmentsWithRun);
            $results = $assignmentsWithRun;
        };

        $algorithm->generate();

        return $this->resultPayload($algorithm, $results);
    }

    private function makeAlgorithm(array $dataset): AlgoritmaGenetika
    {
        return new AlgoritmaGenetika(
            $dataset['waktu'],
            $dataset['ruang'],
            $dataset['kuliah'],
            $dataset['manual'],
            $dataset['slot'],
        );
    }

    private function configureAlgorithm(AlgoritmaGenetika $algorithm, array $dataset, int $programStudiId, array $scope, array $params): void
    {
        $algorithm->jurusan_id = $this->value($scope, 'jurusan_id');
        $algorithm->program_studi_id = $programStudiId;
        $algorithm->user_id = $this->value($scope, 'user_id');
        $algorithm->jurusan_ruang_map = $this->value($dataset, 'jurusan_room_map', []);
        $algorithm->mku_ruang_ids = $this->value($dataset, 'mku_room_ids', []);
        $this->applyNumericOptions($algorithm, $params);

        $algorithm->progress_scope_label = $this->value($scope, 'label', "prodi-{$programStudiId}");
        $algorithm->progress_started_at  = now()->toISOString();
    }

    private function applyNumericOptions(AlgoritmaGenetika $algorithm, array $params): void
    {
        foreach ($this->numericOptions() as $property => [$paramKey, $default]) {
            $algorithm->{$property} = (int) $this->value($params, $paramKey, $default);
        }
    }

    private function numericOptions(): array
    {
        return [
            'num_crommosom' => ['num_kromosom', 45],
            'max_generation' => ['max_generation', 60],
            'crossover_rate' => ['crossover_rate', 85],
            'mutation_rate' => ['mutation_rate', 40],
        ];
    }

    private function resultPayload(AlgoritmaGenetika $algorithm, array $results): array
    {
        $resultPayload = is_array($algorithm->result ?? null) ? $algorithm->result : null;

        return [
            'success' => (bool) $this->algorithmValue($algorithm, 'success', false),
            'canceled' => (bool) $this->algorithmValue($algorithm, 'isCanceled', false),
            'generation' => (int) $this->resultValue($resultPayload, $algorithm, 'generation', 0),
            'fitness' => (float) $this->resultValue($resultPayload, $algorithm, 'best_fitness', 0),
            'best_fitness' => (float) $this->resultValue($resultPayload, $algorithm, 'best_fitness', 0),
            'results'    => $results,
            'result'     => $resultPayload,
            'logs' => $this->generationLogs($resultPayload, $algorithm),
            'message'    => $this->generationMessage($algorithm),
        ];
    }

    private function resultValue(?array $payload, AlgoritmaGenetika $algorithm, string $key, mixed $default): mixed
    {
        return is_array($payload) && array_key_exists($key, $payload)
            ? $payload[$key]
            : $this->algorithmValue($algorithm, $key, $default);
    }

    private function generationLogs(?array $payload, AlgoritmaGenetika $algorithm): array
    {
        if (is_array($payload) && array_key_exists('generation_log', $payload)) {
            return $payload['generation_log'];
        }

        return array_slice((array) $this->algorithmValue($algorithm, 'generation_log', []), -50);
    }

    private function generationMessage(AlgoritmaGenetika $algorithm): string
    {
        if ($algorithm->isCanceled ?? false) {
            return 'Generate jadwal dibatalkan oleh pengguna.';
        }

        return (bool) ($algorithm->success ?? false)
            ? 'Generate jadwal selesai diproses.'
            : 'Generate jadwal tidak menghasilkan solusi yang dapat dipakai.';
    }

    private function value(array $source, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $source) ? $source[$key] : $default;
    }

    private function algorithmValue(AlgoritmaGenetika $algorithm, string $property, mixed $default = null): mixed
    {
        return property_exists($algorithm, $property) ? $algorithm->{$property} : $default;
    }

    private function schedulingRunId(array $params): ?int
    {
        $value = $params['scheduling_run_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    private function withSchedulingRun(array $assignments, ?int $schedulingRunId): array
    {
        if ($schedulingRunId === null) {
            return $assignments;
        }

        return array_map(
            fn (array $assignment): array => $assignment + ['scheduling_run_id' => $schedulingRunId],
            $assignments,
        );
    }
}
