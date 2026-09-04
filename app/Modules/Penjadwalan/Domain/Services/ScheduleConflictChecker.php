<?php

namespace App\Modules\Penjadwalan\Domain\Services;

use App\Modules\Penjadwalan\Domain\Entities\ScheduleAssignment;
use App\Modules\Penjadwalan\Domain\Entities\ScheduleConflict;

final class ScheduleConflictChecker
{
    /**
     * @param iterable<ScheduleAssignment> $existingAssignments
     */
    public function firstConflict(ScheduleAssignment $candidate, iterable $existingAssignments): ?ScheduleConflict
    {
        $conflict = null;

        foreach ($existingAssignments as $existing) {
            if ($candidate->isSameCourseOffering($existing) || !$candidate->overlapsTimeWith($existing)) {
                continue;
            }

            if ($candidate->usesSameRoom($existing)) {
                $conflict = new ScheduleConflict('ruangan', $existing);
            } elseif ($candidate->usesSameClassGroup($existing)) {
                $conflict = new ScheduleConflict('kelas', $existing);
            } elseif ($candidate->sharesLecturerWith($existing)) {
                $conflict = new ScheduleConflict('dosen', $existing);
            }

            if ($conflict !== null) {
                break;
            }
        }

        return $conflict;
    }
}
