<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheduling_operation_snapshots')) {
            Schema::create('scheduling_operation_snapshots', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable();
                $table->integer('jurusan_id')->nullable();
                $table->integer('program_studi_id')->nullable();
                $table->string('scope_label', 100)->default('Semua Data');
                $table->string('action_type', 30);
                $table->unsignedInteger('snapshot_count')->default(0);
                $table->json('snapshot_payload');
                $table->timestamp('restored_at')->nullable();
                $table->timestamps();
                $table->index(['program_studi_id', 'jurusan_id', 'action_type', 'restored_at'], 'sched_snap_scope_idx');
            });

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `scheduling_operation_snapshots` MODIFY `user_id` INT NULL');
            DB::statement('ALTER TABLE `scheduling_operation_snapshots` MODIFY `jurusan_id` INT NULL');
            DB::statement('ALTER TABLE `scheduling_operation_snapshots` MODIFY `program_studi_id` INT NULL');
        }

        if (!$this->indexExists('scheduling_operation_snapshots', 'sched_snap_scope_idx')) {
            Schema::table('scheduling_operation_snapshots', function (Blueprint $table) {
                $table->index(['program_studi_id', 'jurusan_id', 'action_type', 'restored_at'], 'sched_snap_scope_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduling_operation_snapshots');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
