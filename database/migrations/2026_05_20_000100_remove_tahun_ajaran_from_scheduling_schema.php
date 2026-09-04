<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $databaseName = '';

    public function up(): void
    {
        $this->databaseName = DB::getDatabaseName() ?? '';

        $this->dropForeignKeysOnColumn('jadwal', 'scheduling_run_id');
        $this->dropForeignKeysOnColumn('scheduling_operation_snapshots', 'scheduling_run_id');
        $this->dropForeignKeysOnColumn('kelas_kuliah', 'tahun_ajaran_id');
        $this->dropForeignKeysOnColumn('scheduling_runs', 'tahun_ajaran_id');

        if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', 'tahun_ajaran_id')) {
            Schema::table('kelas_kuliah', function (Blueprint $table) {
                $table->dropColumn('tahun_ajaran_id');
            });
        }

        if (Schema::hasTable('scheduling_runs') && Schema::hasColumn('scheduling_runs', 'tahun_ajaran_id')) {
            Schema::table('scheduling_runs', function (Blueprint $table) {
                $table->dropColumn('tahun_ajaran_id');
            });
        }

        Schema::dropIfExists('tahun_ajarans');

        $this->ensureForeignKey('jadwal', 'scheduling_run_id', 'scheduling_runs', 'id', 'fk_jadwal_scheduling_run', 'set null');
        $this->ensureForeignKey('scheduling_operation_snapshots', 'scheduling_run_id', 'scheduling_runs', 'id', 'fk_snapshots_scheduling_run', 'set null');
    }

    public function down(): void
    {
        // Tahun ajaran intentionally removed because the current application
        // only exposes semester ganjil/genap and does not manage academic years.
    }

    private function dropForeignKeysOnColumn(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql' || $this->databaseName === '' || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $constraints = DB::table('information_schema.key_column_usage')
            ->where('table_schema', $this->databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->selectRaw('CONSTRAINT_NAME as constraint_name')
            ->pluck('constraint_name')
            ->unique()
            ->values();

        foreach ($constraints as $constraint) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint));
        }
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $constraintName,
        string $onDelete
    ): void {
        if (DB::getDriverName() !== 'mysql' || $this->databaseName === '') {
            return;
        }

        if (!$this->canAddForeignKey($table, $column, $referencedTable, $referencedColumn)) {
            return;
        }

        if ($this->hasForeignKeyOnColumn($table, $column)) {
            return;
        }

        try {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`) ON DELETE %s',
                $table,
                $constraintName,
                $column,
                $referencedTable,
                $referencedColumn,
                strtoupper($onDelete)
            ));
        } catch (Throwable) {
        }
    }

    private function canAddForeignKey(string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable)) {
            return false;
        }

        if (!Schema::hasColumn($table, $column) || !Schema::hasColumn($referencedTable, $referencedColumn)) {
            return false;
        }

        return $this->hasCompatibleColumnTypes($table, $column, $referencedTable, $referencedColumn)
            && !$this->hasOrphans($table, $column, $referencedTable, $referencedColumn);
    }

    private function hasCompatibleColumnTypes(string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        $local = $this->columnType($table, $column);
        $referenced = $this->columnType($referencedTable, $referencedColumn);

        return $local !== null && $referenced !== null && $local === $referenced;
    }

    private function columnType(string $table, string $column): ?string
    {
        $row = DB::table('information_schema.columns')
            ->where('table_schema', $this->databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->selectRaw('COLUMN_TYPE as column_type')
            ->first();

        return is_object($row) ? (string) $row->column_type : null;
    }

    private function hasForeignKeyOnColumn(string $table, string $column): bool
    {
        return DB::table('information_schema.key_column_usage')
            ->where('table_schema', $this->databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }

    private function hasOrphans(string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        return DB::table("$table as child")
            ->leftJoin("$referencedTable as parent", "parent.$referencedColumn", '=', "child.$column")
            ->whereNotNull("child.$column")
            ->whereNull("parent.$referencedColumn")
            ->exists();
    }
};
