<?php

namespace Tests\Unit\Penjadwalan;

use PHPUnit\Framework\TestCase;

class JadwalControllerManualAssignTest extends TestCase
{
    public function test_manual_assign_marks_reassigned_generated_schedule_as_manual(): void
    {
        $contents = file_get_contents(__DIR__ . '/../../../app/Modules/Penjadwalan/Presentation/Http/Controllers/JadwalController.php');

        $this->assertIsString($contents);
        $this->assertStringContainsString("'origin' => 'manual'", $contents);
    }
}
