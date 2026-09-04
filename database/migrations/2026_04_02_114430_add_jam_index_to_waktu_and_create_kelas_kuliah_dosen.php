<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1. Add jam_index to waktu table (for GA soft constraint: teaching gap detection)
     * 2. Create kelas_kuliah_dosen pivot table (for team teaching support)
     * 3. Populate pivot table from existing dosen_id data
     */
    public function up(): void
    {
        // 1. Add jam_index to waktu (integer index for ordering time slots)
        if (!Schema::hasColumn('waktu', 'jam_index')) {
            Schema::table('waktu', function (Blueprint $table) {
                $table->integer('jam_index')->nullable()->after('sks');
            });

            // Auto-populate jam_index based on existing row order
            $waktus = DB::table('waktu')->orderBy('id')->get();
            foreach ($waktus as $index => $waktu) {
                DB::table('waktu')->where('id', $waktu->id)->update(['jam_index' => $index + 1]);
            }
        }

        // 2. Create kelas_kuliah_dosen pivot table for team teaching
        if (!Schema::hasTable('kelas_kuliah_dosen')) {
            Schema::create('kelas_kuliah_dosen', function (Blueprint $table) {
                $table->id();
                // Match legacy schema: kelas_kuliah.id, dosen.id, slot.id are INT (not bigint)
                $table->unsignedInteger('kelas_kuliah_id');
                $table->unsignedInteger('dosen_id');
                $table->unsignedInteger('preferred_slot_id')->nullable();
                $table->timestamps();

                $table->foreign('kelas_kuliah_id')
                    ->references('id')
                    ->on('kelas_kuliah')
                    ->onDelete('cascade');

                $table->foreign('dosen_id')
                    ->references('id')
                    ->on('dosen')
                    ->onDelete('cascade');
            });

            // 3. Populate pivot table from existing dosen_id in kelas_kuliah
            $kelasKuliahs = DB::table('kelas_kuliah')
                ->whereNotNull('dosen_id')
                ->where('dosen_id', '>', 0)
                ->get();

            foreach ($kelasKuliahs as $kk) {
                DB::table('kelas_kuliah_dosen')->insert([
                    'kelas_kuliah_id' => $kk->id,
                    'dosen_id' => $kk->dosen_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_kuliah_dosen');

        if (Schema::hasColumn('waktu', 'jam_index')) {
            Schema::table('waktu', function (Blueprint $table) {
                $table->dropColumn('jam_index');
            });
        }
    }
};
