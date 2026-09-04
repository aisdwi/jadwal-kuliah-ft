<?php

namespace App\Console\Commands\DatabaseIntegrity;

use Illuminate\Support\Facades\DB;

final class DatabaseColumnDefinition
{
    public function get(string $table, string $column): ?string
    {
        $databaseName = DB::getDatabaseName();

        if ($databaseName === null || $databaseName === '') {
            return null;
        }

        $definition = DB::table('information_schema.columns')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('column_type');

        return is_string($definition) ? strtolower($definition) : null;
    }

    public function matches(?string $childDefinition, ?string $parentDefinition): bool
    {
        if ($childDefinition === null || $parentDefinition === null) {
            return true;
        }

        return trim($childDefinition) === trim($parentDefinition);
    }
}
