<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\Penjadwalan\Domain\Entities\ScheduleAssignment;
use App\Modules\Penjadwalan\Domain\Services\ScheduleConflictChecker;
use PHPUnit\Framework\TestCase;

class ScheduleConflictCheckerTest extends TestCase
{
    public function test_detects_room_conflict_on_same_slot(): void
    {
        $checker = new ScheduleConflictChecker();

        $conflict = $checker->firstConflict(
            new ScheduleAssignment(1, 10, 7, 3, [100]),
            [new ScheduleAssignment(2, 11, 7, 3, [101])],
        );

        $this->assertNotNull($conflict);
        $this->assertSame('ruangan', $conflict->type);
    }

    public function test_detects_class_conflict_on_same_slot(): void
    {
        $checker = new ScheduleConflictChecker();

        $conflict = $checker->firstConflict(
            new ScheduleAssignment(1, 10, 7, 3, [100]),
            [new ScheduleAssignment(2, 10, 7, 4, [101])],
        );

        $this->assertNotNull($conflict);
        $this->assertSame('kelas', $conflict->type);
    }

    public function test_detects_lecturer_conflict_on_same_slot(): void
    {
        $checker = new ScheduleConflictChecker();

        $conflict = $checker->firstConflict(
            new ScheduleAssignment(1, 10, 7, 3, [100, 101]),
            [new ScheduleAssignment(2, 11, 7, 4, [101, 102])],
        );

        $this->assertNotNull($conflict);
        $this->assertSame('dosen', $conflict->type);
    }

    public function test_ignores_assignments_on_different_slots(): void
    {
        $checker = new ScheduleConflictChecker();

        $conflict = $checker->firstConflict(
            new ScheduleAssignment(1, 10, 7, 3, [100]),
            [new ScheduleAssignment(2, 10, 8, 3, [100])],
        );

        $this->assertNull($conflict);
    }
}
