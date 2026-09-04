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
        Schema::create('jadwals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // GANTI DARI foreignId KE foreignUuid
            $table->foreignUuid('jurusan_id')->constrained();
            $table->foreignUuid('mata_kuliah_id')->constrained();
            $table->foreignUuid('dosen_id')->constrained();
            $table->foreignUuid('ruangan_id')->constrained();

            $table->string('kelas')->nullable();
            $table->string('hari')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
