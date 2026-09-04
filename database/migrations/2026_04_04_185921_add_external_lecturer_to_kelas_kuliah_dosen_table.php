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
        Schema::table('kelas_kuliah_dosen', function (Blueprint $table) {
            if (!Schema::hasColumn('kelas_kuliah_dosen', 'is_external')) {
                $table->boolean('is_external')->default(false)->after('preferred_slot_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas_kuliah_dosen', function (Blueprint $table) {
            if (Schema::hasColumn('kelas_kuliah_dosen', 'is_external')) {
                $table->dropColumn('is_external');
            }
        });
    }
};
