<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $originalMode = DB::selectOne('SELECT @@SESSION.sql_mode AS sql_mode');
        $sqlMode = is_object($originalMode) && isset($originalMode->sql_mode)
            ? (string) $originalMode->sql_mode
            : '';

        DB::statement(
            "SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE', ''), 'NO_ZERO_DATE', '')"
        );

        $tables = [
            'dosen',
            'program_studi',
            'matakuliah',
            'kelas',
            'kelas_kuliah',
            'ruangan',
            'slot',
            'waktu',
            'users',
            'jadwal',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (['created_at', 'updated_at'] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement(sprintf(
                    "UPDATE `%s` SET `%s` = NULL WHERE `%s` = '0000-00-00 00:00:00'",
                    $table,
                    $column,
                    $column
                ));
            }
        }

        DB::statement("SET SESSION sql_mode = '" . str_replace("'", "''", $sqlMode) . "'");
    }

    public function down(): void
    {
        // Irreversible safely. Normalized timestamps intentionally stay nullable.
    }
};
