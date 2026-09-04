<?php

namespace App\Modules\Shared\Infrastructure\Import;

use Illuminate\Support\Collection;

trait ValidatesImportRows
{
    protected function ensureRowsNotEmpty(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new ImportValidationException('File Excel kosong.');
        }
    }

    /**
     * @param array<int, string> $columns
     */
    protected function ensureRequiredColumns(array $firstRow, array $columns, string $message): void
    {
        foreach ($columns as $column) {
            if (!array_key_exists($column, $firstRow)) {
                throw new ImportValidationException($message);
            }
        }
    }

}
