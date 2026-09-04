<?php

namespace App\Modules\KelasKuliah\Infrastructure\Import;

final class KelasKuliahImportRowCompleteness
{
    public static function hasValues(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === '' || $value === null || $value === 0) {
                return false;
            }
        }

        return true;
    }
}
