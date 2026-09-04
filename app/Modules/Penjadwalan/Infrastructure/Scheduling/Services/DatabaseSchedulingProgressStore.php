<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Services;

use App\Modules\Penjadwalan\Application\Port\SchedulingProgressStorePort;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DatabaseSchedulingProgressStore implements SchedulingProgressStorePort
{
    public function get(string $userId): ?array
    {
        return Cache::store('database')->get('ga_progress_' . $userId);
    }

    public function put(string $userId, array $payload, int $ttlSeconds): void
    {
        $previous = $this->get($userId);
        Cache::store('database')->put('ga_progress_' . $userId, $payload, $ttlSeconds);

        if ($this->statusChanged($payload, $previous)) {
            $this->logStatusChange($userId, $payload, $previous);
        }

        if ($this->hasCompletionResult($payload)) {
            $this->logCompletion($userId, $payload);
        }
    }

    private function statusChanged(array $payload, ?array $previous): bool
    {
        return $this->payloadValue($payload, 'status') !== $this->payloadValue($previous, 'status');
    }

    private function hasCompletionResult(array $payload): bool
    {
        return $this->payloadValue($payload, 'status') === 'completed' && isset($payload['result']);
    }

    private function logStatusChange(string $userId, array $payload, ?array $previous): void
    {
        Log::info('GA Progress Status Change', [
            'user_id' => $userId,
            'from_status' => $this->payloadValue($previous, 'status', 'none'),
            'to_status' => $this->payloadValue($payload, 'status'),
            'generation' => $this->payloadValue($payload, 'generation', 0),
            'max_generation' => $this->payloadValue($payload, 'max_generation', 0),
            'best_fitness' => $this->payloadValue($payload, 'best_fitness', 0),
            'scope' => $this->payloadValue($payload, 'scope_label', 'unknown'),
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function logCompletion(string $userId, array $payload): void
    {
        Log::info('GA Execution Completed', [
            'user_id' => $userId,
            'duration_seconds' => now()->diffInSeconds($this->startedAt($payload)),
            'final_generation' => $this->payloadValue($payload, 'generation', 0),
            'max_generation' => $this->payloadValue($payload, 'max_generation', 0),
            'best_fitness' => $this->payloadValue($payload, 'best_fitness', 0),
            'scheduled_count' => count(data_get($payload, 'result.scheduled', [])),
            'unscheduled_count' => count(data_get($payload, 'result.unscheduled', [])),
            'scope' => $this->payloadValue($payload, 'scope_label', 'unknown'),
        ]);
    }

    private function startedAt(array $payload): Carbon
    {
        $startedAt = $this->payloadValue($payload, 'started_at');

        return $startedAt === null ? now() : Carbon::parse($startedAt);
    }

    private function payloadValue(?array $payload, string $key, mixed $default = null): mixed
    {
        return is_array($payload) && array_key_exists($key, $payload) ? $payload[$key] : $default;
    }

    public function forgetCancel(string $userId): void
    {
        Cache::store('database')->forget('ga_cancel_' . $userId);
    }

    public function requestCancel(string $userId): void
    {
        Cache::store('database')->put('ga_cancel_' . $userId, true, 3600);
    }
}
