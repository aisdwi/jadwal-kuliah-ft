<?php

namespace Tests\Unit\Penjadwalan;

use App\Modules\Penjadwalan\Application\Service\JadwalAssignmentRequest;
use PHPUnit\Framework\TestCase;

class JadwalAssignmentRequestTest extends TestCase
{
    public function test_detects_clear_assignment_when_slot_and_room_are_absent(): void
    {
        $class = JadwalAssignmentRequest::class;
        $this->assertTrue(class_exists($class));

        $request = $class::fromArray(['kelas_kuliah_id' => '15']);

        $this->assertSame(15, $request->kelasKuliahId);
        $this->assertTrue($request->isClearAssignment());
        $this->assertFalse($request->isCompleteAssignment());
    }

    public function test_builds_payload_with_optional_origin(): void
    {
        $class = JadwalAssignmentRequest::class;
        $this->assertTrue(class_exists($class));

        $request = $class::fromArray([
            'kelas_kuliah_id' => '15',
            'slot_id' => '3',
            'ruangan_id' => '7',
            'origin' => 'manual',
        ]);

        $this->assertTrue($request->isCompleteAssignment());
        $this->assertSame([
            'kelas_kuliah_id' => 15,
            'slot_id' => 3,
            'ruangan_id' => 7,
            'origin' => 'manual',
        ], $request->payload());
    }
}
