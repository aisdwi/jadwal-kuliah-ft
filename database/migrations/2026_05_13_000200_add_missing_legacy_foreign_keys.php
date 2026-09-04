<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $databaseName = '';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->databaseName = DB::getDatabaseName() ?? '';

        if ($this->databaseName === '') {
            return;
        }

        $this->alignKelasKuliahDosenColumnTypes();

        $this->ensureUniqueIndex('slot', ['hari_id', 'waktu_id'], 'slot_hari_id_waktu_id_unique');
        $this->ensureUniqueIndex('jadwal', ['slot_id', 'ruangan_id'], 'jadwal_slot_ruangan_unique');
        $this->ensureUniqueIndex('jurusan_ruangan', ['jurusan_id', 'ruangan_id'], 'jurusan_ruangan_jurusan_id_ruangan_id_unique');

        $this->ensureForeignKey('program_studi', 'jurusan_id', 'jurusan', 'id', 'fk_program_studi_jurusan', 'restrict');
        $this->ensureForeignKey('dosen', 'jurusan_id', 'jurusan', 'id', 'fk_dosen_jurusan', 'restrict');
        $this->ensureForeignKey('matakuliah', 'program_studi_id', 'program_studi', 'id', 'fk_matakuliah_program_studi', 'restrict');
        $this->ensureForeignKey('matakuliah', 'jurusan_id', 'jurusan', 'id', 'fk_matakuliah_jurusan', 'restrict');
        $this->ensureForeignKey('kelas', 'program_studi_id', 'program_studi', 'id', 'fk_kelas_program_studi', 'cascade');
        $this->ensureForeignKey('kelas', 'jurusan_id', 'jurusan', 'id', 'fk_kelas_jurusan', 'cascade');
        $this->ensureForeignKey('kelas_kuliah', 'dosen_id', 'dosen', 'id', 'fk_kelas_kuliah_dosen', 'restrict');
        $this->ensureForeignKey('kelas_kuliah', 'matakuliah_id', 'matakuliah', 'id', 'fk_kelas_kuliah_matakuliah', 'restrict');
        $this->ensureForeignKey('kelas_kuliah', 'kelas_id', 'kelas', 'id', 'fk_kelas_kuliah_kelas', 'restrict');
        $this->ensureForeignKey('ruangan', 'gedung_id', 'gedung', 'id', 'fk_ruangan_gedung', 'restrict');
        $this->ensureForeignKey('slot', 'hari_id', 'hari', 'id', 'fk_slot_hari', 'cascade');
        $this->ensureForeignKey('slot', 'waktu_id', 'waktu', 'id', 'fk_slot_waktu', 'cascade');
        $this->ensureForeignKey('jurusan_ruangan', 'jurusan_id', 'jurusan', 'id', 'fk_jurusan_ruangan_jurusan', 'cascade');
        $this->ensureForeignKey('jurusan_ruangan', 'ruangan_id', 'ruangan', 'id', 'fk_jurusan_ruangan_ruangan', 'cascade');
        $this->ensureForeignKey('jadwal', 'kelas_kuliah_id', 'kelas_kuliah', 'id', 'fk_jadwal_kelas_kuliah', 'cascade');
        $this->ensureForeignKey('jadwal', 'slot_id', 'slot', 'id', 'fk_jadwal_slot', 'restrict');
        $this->ensureForeignKey('jadwal', 'ruangan_id', 'ruangan', 'id', 'fk_jadwal_ruangan', 'restrict');
        $this->ensureForeignKey('users', 'role_id', 'role', 'id', 'fk_users_role', 'restrict');
        $this->ensureForeignKey('users', 'dosen_id', 'dosen', 'id', 'fk_users_dosen', 'set null');
        $this->ensureForeignKey('users', 'jurusan_id', 'jurusan', 'id', 'fk_users_jurusan', 'set null');
        $this->ensureForeignKey('users', 'program_studi_id', 'program_studi', 'id', 'fk_users_program_studi', 'set null');
        $this->ensureForeignKey('kelas_kuliah_dosen', 'kelas_kuliah_id', 'kelas_kuliah', 'id', 'fk_kkd_kelas_kuliah', 'cascade');
        $this->ensureForeignKey('kelas_kuliah_dosen', 'dosen_id', 'dosen', 'id', 'fk_kkd_dosen', 'cascade');
        $this->ensureForeignKey('kelas_kuliah_dosen', 'preferred_slot_id', 'slot', 'id', 'fk_kkd_preferred_slot', 'set null');
    }

    public function down(): void
    {
        // Intentionally left blank. Rolling back FK standardization on legacy data
        // without a coordinated downgrade plan is not safe.
    }

    private function alignKelasKuliahDosenColumnTypes(): void
    {
        if (! Schema::hasTable('kelas_kuliah_dosen')) {
            return;
        }

        $this->modifyColumnIfNeeded('kelas_kuliah_dosen', 'kelas_kuliah_id', 'INT NOT NULL');
        $this->modifyColumnIfNeeded('kelas_kuliah_dosen', 'dosen_id', 'INT NOT NULL');
        $this->modifyColumnIfNeeded('kelas_kuliah_dosen', 'preferred_slot_id', 'INT NULL');
    }

    private function modifyColumnIfNeeded(string $table, string $column, string $sqlDefinition): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $currentType = $this->getColumnType($table, $column);
        $normalizedExpected = strtolower(str_replace('  ', ' ', trim($sqlDefinition)));

        if ($currentType === null) {
            return;
        }

        $normalizedCurrent = strtolower(preg_replace('/\s+/', ' ', trim($currentType)));

        if ($normalizedCurrent === $normalizedExpected) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY COLUMN `%s` %s',
            $table,
            $column,
            $sqlDefinition
        ));
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $constraintName,
        string $onDelete
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasTable($referencedTable)) {
            return;
        }

        if (! Schema::hasColumn($table, $column) || ! Schema::hasColumn($referencedTable, $referencedColumn)) {
            return;
        }

        if ($this->hasForeignKeyOnColumn($table, $column)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`) ON DELETE %s',
            $table,
            $constraintName,
            $column,
            $referencedTable,
            $referencedColumn,
            strtoupper($onDelete)
        ));
    }

    private function ensureUniqueIndex(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->hasIndex($table, $columns, true)) {
            return;
        }

        $quotedColumns = implode('`, `', $columns);

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` UNIQUE (`%s`)',
            $table,
            $indexName,
            $quotedColumns
        ));
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

    private function hasIndex(string $table, array $columns, bool $unique): bool
    {
        $statistics = DB::table('information_schema.statistics')
            ->selectRaw('INDEX_NAME as index_name, NON_UNIQUE as non_unique, SEQ_IN_INDEX as seq_in_index, COLUMN_NAME as column_name')
            ->where('table_schema', $this->databaseName)
            ->where('table_name', $table)
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get()
            ->groupBy('index_name');

        foreach ($statistics as $items) {
            $first = $items->first();

            if ($first === null) {
                continue;
            }

            $isUniqueIndex = (int) $first->non_unique === 0;

            if ($isUniqueIndex !== $unique) {
                continue;
            }

            $indexedColumns = $items->pluck('column_name')->all();

            if ($indexedColumns === $columns) {
                return true;
            }
        }

        return false;
    }

    private function getColumnType(string $table, string $column): ?string
    {
        $columnType = DB::table('information_schema.columns')
            ->where('table_schema', $this->databaseName)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value(DB::raw("CONCAT(UPPER(DATA_TYPE), IF(IS_NULLABLE = 'NO', ' NOT NULL', ' NULL'))"));

        return is_string($columnType) ? $columnType : null;
    }
};
