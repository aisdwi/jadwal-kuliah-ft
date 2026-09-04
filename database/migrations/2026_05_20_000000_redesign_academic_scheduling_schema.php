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

        $this->ensureKelasKuliahAcademicPeriod();
        $this->ensureSchedulingRunsTable();
        $this->ensureSchedulingRunReferences();
        $this->ensureKelasKuliahDosenUniqueness();
        $this->ensureOptionalForeignKeys();
    }

    public function down(): void
    {
        // This migration intentionally keeps data and columns on rollback. It
        // upgrades production integrity and should not silently remove history.
    }

    private function ensureKelasKuliahAcademicPeriod(): void
    {
        if (!Schema::hasTable('kelas_kuliah')) {
            return;
        }

        Schema::table('kelas_kuliah', function (Blueprint $table) {
            if (!Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
                $table->string('semester_tipe', 10)->nullable()->after('jumlah_mahasiswa');
            }
        });

        $this->backfillSemesterTipe();
    }

    private function backfillSemesterTipe(): void
    {
        if (!Schema::hasTable('matakuliah') || !Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
            return;
        }

        $rows = DB::table('kelas_kuliah as kk')
            ->join('matakuliah as mk', 'mk.id', '=', 'kk.matakuliah_id')
            ->whereNull('kk.semester_tipe')
            ->select('kk.id', 'mk.semester')
            ->get();

        foreach ($rows as $row) {
            DB::table('kelas_kuliah')->where('id', $row->id)->update([
                'semester_tipe' => ((int) $row->semester % 2) === 0 ? 'genap' : 'ganjil',
                'updated_at' => now(),
            ]);
        }
    }

    private function ensureSchedulingRunsTable(): void
    {
        if (Schema::hasTable('scheduling_runs')) {
            return;
        }

        Schema::create('scheduling_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('jurusan_id')->nullable();
            $table->integer('program_studi_id')->nullable();
            $table->string('semester_tipe', 10)->nullable();
            $table->string('scope_label', 150)->default('Semua Data');
            $table->string('status', 30)->default('queued');
            $table->json('params')->nullable();
            $table->json('result_summary')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'scheduling_runs_status_created_idx');
            $table->index(['jurusan_id', 'program_studi_id', 'semester_tipe'], 'scheduling_runs_scope_idx');
        });
    }

    private function ensureSchedulingRunReferences(): void
    {
        if (Schema::hasTable('jadwal') && !Schema::hasColumn('jadwal', 'scheduling_run_id')) {
            Schema::table('jadwal', function (Blueprint $table) {
                $table->unsignedBigInteger('scheduling_run_id')->nullable()->after('origin');
            });
        }

        if (Schema::hasTable('scheduling_operation_snapshots') && !Schema::hasColumn('scheduling_operation_snapshots', 'scheduling_run_id')) {
            Schema::table('scheduling_operation_snapshots', function (Blueprint $table) {
                $table->unsignedBigInteger('scheduling_run_id')->nullable()->after('program_studi_id');
            });
        }
    }

    private function ensureKelasKuliahDosenUniqueness(): void
    {
        if (!Schema::hasTable('kelas_kuliah_dosen')) {
            return;
        }

        $duplicates = DB::table('kelas_kuliah_dosen')
            ->select('kelas_kuliah_id', 'dosen_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('kelas_kuliah_id', 'dosen_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('kelas_kuliah_dosen')
                ->where('kelas_kuliah_id', $duplicate->kelas_kuliah_id)
                ->where('dosen_id', $duplicate->dosen_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        if (!Schema::hasIndex('kelas_kuliah_dosen', 'kkd_kelas_kuliah_dosen_unique')) {
            Schema::table('kelas_kuliah_dosen', function (Blueprint $table) {
                $table->unique(['kelas_kuliah_id', 'dosen_id'], 'kkd_kelas_kuliah_dosen_unique');
            });
        }
    }

    private function ensureOptionalForeignKeys(): void
    {
        if (DB::getDriverName() !== 'mysql' || $this->databaseName === '') {
            return;
        }

        $this->ensureForeignKey('notifications', 'user_id', 'users', 'id', 'fk_notifications_user', 'cascade');
        $this->ensureForeignKey('notifications', 'actor_id', 'users', 'id', 'fk_notifications_actor', 'set null');
        $this->ensureForeignKey('activity_logs', 'user_id', 'users', 'id', 'fk_activity_logs_user_redesign', 'set null');
        $this->ensureForeignKey('scheduling_runs', 'user_id', 'users', 'id', 'fk_scheduling_runs_user', 'set null');
        $this->ensureForeignKey('scheduling_runs', 'jurusan_id', 'jurusan', 'id', 'fk_scheduling_runs_jurusan', 'set null');
        $this->ensureForeignKey('scheduling_runs', 'program_studi_id', 'program_studi', 'id', 'fk_scheduling_runs_program_studi', 'set null');
        $this->ensureForeignKey('jadwal', 'scheduling_run_id', 'scheduling_runs', 'id', 'fk_jadwal_scheduling_run', 'set null');
        $this->ensureForeignKey('scheduling_operation_snapshots', 'scheduling_run_id', 'scheduling_runs', 'id', 'fk_snapshots_scheduling_run', 'set null');
        $this->ensureForeignKey('scheduling_operation_snapshots', 'user_id', 'users', 'id', 'fk_snapshots_user', 'set null');
        $this->ensureForeignKey('scheduling_operation_snapshots', 'jurusan_id', 'jurusan', 'id', 'fk_snapshots_jurusan', 'set null');
        $this->ensureForeignKey('scheduling_operation_snapshots', 'program_studi_id', 'program_studi', 'id', 'fk_snapshots_program_studi', 'set null');
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $constraintName,
        string $onDelete
    ): void {
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
            // Legacy installs can have mismatched integer widths. Keep the
            // schema migration deployable; data cleanup migrations can tighten
            // the FK later after column types are aligned.
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
