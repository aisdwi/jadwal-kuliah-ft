<?php

namespace App\Console\Commands\Scheduling;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class JurusanSlotTimeCatalog
{
    public function bySks(): Collection
    {
        return DB::table('waktu')
            ->whereNotNull('sks')
            ->where('sks', '>', 0)
            ->get()
            ->map(fn ($time) => $this->withParsedRange($time))
            ->filter(fn (?object $time) => $time !== null)
            ->sortBy(fn ($time) => sprintf('%04d-%04d-%04d', $time->start, $time->end, $time->id))
            ->values()
            ->groupBy(fn ($time) => (int) $time->sks);
    }

    private function withParsedRange(object $time): ?object
    {
        $range = $this->parseTimeRange((string) $time->pukul);
        if ($range === null || !JurusanSlotSessions::contains($range['start'], $range['end'])) {
            return null;
        }

        $time->start = $range['start'];
        $time->end = $range['end'];

        return $time;
    }

    /**
     * @return array{start: int, end: int}|null
     */
    private function parseTimeRange(string $pukul): ?array
    {
        if (!preg_match('/(\d{1,2})[.:](\d{2})\s*-\s*(\d{1,2})[.:](\d{2})/', $pukul, $matches)) {
            return null;
        }

        $start = ((int) $matches[1] * 60) + (int) $matches[2];
        $end = ((int) $matches[3] * 60) + (int) $matches[4];

        return $end > $start ? ['start' => $start, 'end' => $end] : null;
    }
}
