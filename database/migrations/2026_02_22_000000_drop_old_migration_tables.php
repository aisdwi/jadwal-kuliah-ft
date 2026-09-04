<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old tables yang menggunakan UUID
        // Ini untuk cleanup sebelum migrate ke schema keropi yang menggunakan INT
        
        Schema::disableForeignKeyConstraints();
        
        // Drop dalam urutan reverse
        Schema::dropIfExists('waktus');
        Schema::dropIfExists('jam_kuliahs');
        Schema::dropIfExists('haris');
        Schema::dropIfExists('jadwals');
        Schema::dropIfExists('mata_kuliahs');
        Schema::dropIfExists('ruangans');
        Schema::dropIfExists('dosens');
        Schema::dropIfExists('jurusans');
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot rollback - old schema is gone
        // Rollback to previous backup if needed
    }
};
