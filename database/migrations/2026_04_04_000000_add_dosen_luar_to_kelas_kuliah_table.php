<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kelas_kuliah', function (Blueprint $table) {
            if (!Schema::hasColumn('kelas_kuliah', 'dosen_luar')) {
                $table->string('dosen_luar')->nullable()->after('dosen_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas_kuliah', function (Blueprint $table) {
            if (Schema::hasColumn('kelas_kuliah', 'dosen_luar')) {
                $table->dropColumn('dosen_luar');
            }
        });
    }
};