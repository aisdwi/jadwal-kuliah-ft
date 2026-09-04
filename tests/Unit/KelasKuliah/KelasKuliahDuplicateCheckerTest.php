<?php

namespace Tests\Unit\KelasKuliah;

use App\Modules\KelasKuliah\Domain\Entities\KelasKuliahOffering;
use App\Modules\KelasKuliah\Domain\Entities\TeachingAssignmentLabelKey;
use App\Modules\KelasKuliah\Domain\Services\KelasKuliahDuplicateChecker;
use PHPUnit\Framework\TestCase;

class KelasKuliahDuplicateCheckerTest extends TestCase
{
    public function test_detects_duplicate_teaching_assignment(): void
    {
        $checker = new KelasKuliahDuplicateChecker();
        $candidate = new KelasKuliahOffering(10, 20, 30, 40);

        $duplicate = $checker->hasDuplicate(
            $candidate,
            [new KelasKuliahOffering(10, 20, 30, 25)],
        );

        $this->assertTrue($duplicate);
    }

    public function test_ignores_different_teaching_assignment(): void
    {
        $checker = new KelasKuliahDuplicateChecker();
        $candidate = new KelasKuliahOffering(10, 20, 30, 40);

        $duplicate = $checker->hasDuplicate(
            $candidate,
            [new KelasKuliahOffering(10, 20, 31, 25)],
        );

        $this->assertFalse($duplicate);
    }

    public function test_normalizes_text_key_for_excel_duplicate_detection(): void
    {
        $left = TeachingAssignmentLabelKey::from(' IF101 ', ' TI - A ', ' Dr. Budi ');
        $right = TeachingAssignmentLabelKey::from('if101', 'ti - a', 'dr. budi');

        $this->assertSame($left, $right);
    }
}
