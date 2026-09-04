<?php

namespace App\Jobs;

use App\Modules\Penjadwalan\Application\Service\SchedulingAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessGeneticAlgorithm implements ShouldQueue
{
    use Queueable;

    public $timeout = 0;
    public $tries = 1;
    public $maxExceptions = 1;
    public $backoff = 0;

    public function __construct(
        private readonly int     $programStudiId,
        private readonly ?string $semesterTipe,
        private readonly array   $scope,
        private readonly array   $params = [],
    ) {}

    public function handle(SchedulingAppService $service): void
    {
        $service->processGenerationInBackground($this->programStudiId, $this->semesterTipe, $this->scope, $this->params);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->lockKey()))
                ->releaseAfter(60)
                ->expireAfter(7200),
        ];
    }

    private function lockKey(): string
    {
        $scopeKey = !empty($this->scope['restrict_by_jurusan']) && !empty($this->scope['jurusan_id'])
            ? 'jurusan:' . (int) $this->scope['jurusan_id']
            : 'prodi:' . $this->programStudiId;

        return 'schedule-generation:' . $scopeKey . ':semester:' . ($this->semesterTipe ?: 'all');
    }
}
