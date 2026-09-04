<?php

namespace App\Modules\Shared\Infrastructure\Import;

trait ReadsImportRowValues
{
    protected function stringValue(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }

    protected function intValue(array $data, string $key): int
    {
        return (int) ($data[$key] ?? 0);
    }
}
