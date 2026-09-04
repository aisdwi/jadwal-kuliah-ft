<?php

namespace App\Modules\KelasKuliah\Domain\Services;

use App\Modules\KelasKuliah\Domain\Entities\KelasKuliahOffering;

final class KelasKuliahDuplicateChecker
{
    /**
     * @param iterable<KelasKuliahOffering> $existingOfferings
     */
    public function hasDuplicate(KelasKuliahOffering $candidate, iterable $existingOfferings): bool
    {
        foreach ($existingOfferings as $existing) {
            if ($candidate->hasSameTeachingAssignment($existing)) {
                return true;
            }
        }

        return false;
    }
}
