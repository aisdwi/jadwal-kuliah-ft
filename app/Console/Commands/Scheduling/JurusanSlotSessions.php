<?php

namespace App\Console\Commands\Scheduling;

final class JurusanSlotSessions
{
    private const SESSIONS = [
        ['start' => 480, 'end' => 730],
        ['start' => 780, 'end' => 1070],
    ];

    public static function all(): array
    {
        return self::SESSIONS;
    }

    public static function contains(int $start, int $end): bool
    {
        foreach (self::SESSIONS as $session) {
            if ($start >= $session['start'] && $end <= $session['end']) {
                return true;
            }
        }

        return false;
    }
}
