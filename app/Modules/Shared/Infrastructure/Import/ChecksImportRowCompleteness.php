<?php

namespace App\Modules\Shared\Infrastructure\Import;

trait ChecksImportRowCompleteness
{
    protected function hasEmptyValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === '' || $value === null || $value === 0) {
                return true;
            }
        }

        return false;
    }
}
