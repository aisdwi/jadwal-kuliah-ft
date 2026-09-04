<?php

namespace App\Console\Commands\DatabaseIntegrity;

final class DatabaseIntegrityRules
{
    /**
     * @return array<int, array<string, string>>
     */
    public static function relations(): array
    {
        return config('database_integrity.relations', []);
    }

    /**
     * @return array<int, array{table: string, columns: array<int, string>, label: string}>
     */
    public static function duplicateChecks(): array
    {
        return config('database_integrity.duplicate_checks', []);
    }

    /**
     * @return array<int, string>
     */
    public static function expectedRoles(): array
    {
        return config('database_integrity.expected_roles', []);
    }
}
