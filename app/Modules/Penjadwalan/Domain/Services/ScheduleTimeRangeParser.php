<?php

namespace App\Modules\Penjadwalan\Domain\Services;

final class ScheduleTimeRangeParser
{
    /**
     * @return array{start: int, end: int}|null
     */
    public function parse(?string $pukul): ?array
    {
        $value = trim((string) $pukul);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/(\d{1,2})[.:](\d{2})\s*-\s*(\d{1,2})[.:](\d{2})/', $value, $matches)) {
            return null;
        }

        $start = $this->toMinutes((int) $matches[1], (int) $matches[2]);
        $end = $this->toMinutes((int) $matches[3], (int) $matches[4]);

        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function toMinutes(int $hour, int $minute): ?int
    {
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }
}
