<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role_id')) {
                    $table->unsignedBigInteger('role_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'dosen_id')) {
                    $table->unsignedBigInteger('dosen_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'jurusan_id')) {
                    $table->unsignedBigInteger('jurusan_id')->nullable();
                }
                if (!Schema::hasColumn('users', 'program_studi_id')) {
                    $table->unsignedBigInteger('program_studi_id')->nullable();
                }
            });

            // Add foreign key constraints in a separate step to avoid order issues
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role_id')) {
                    $table->foreign('role_id')->references('id')->on('role')->onDelete('restrict');
                }
                if (Schema::hasColumn('users', 'dosen_id')) {
                    $table->foreign('dosen_id')->references('id')->on('dosen')->onDelete('set null');
                }
                if (Schema::hasColumn('users', 'jurusan_id')) {
                    $table->foreign('jurusan_id')->references('id')->on('jurusan')->onDelete('set null');
                }
                if (Schema::hasColumn('users', 'program_studi_id')) {
                    $table->foreign('program_studi_id')->references('id')->on('program_studi')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'program_studi_id')) {
                    $table->dropForeign(['program_studi_id']);
                    $table->dropColumn('program_studi_id');
                }
                if (Schema::hasColumn('users', 'jurusan_id')) {
                    $table->dropForeign(['jurusan_id']);
                    $table->dropColumn('jurusan_id');
                }
                if (Schema::hasColumn('users', 'dosen_id')) {
                    $table->dropForeign(['dosen_id']);
                    $table->dropColumn('dosen_id');
                }
                if (Schema::hasColumn('users', 'role_id')) {
                    $table->dropForeign(['role_id']);
                    $table->dropColumn('role_id');
                }
            });
        }
    }
};
