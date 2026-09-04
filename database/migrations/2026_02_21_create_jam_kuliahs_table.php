<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jam_kuliahs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('sesi');           // 1, 2, 3, dst
            $table->time('jam_mulai');         // 08:00
            $table->time('jam_selesai');       // 09:30
            $table->timestamps();
            
            $table->unique('sesi');
            $table->index('sesi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_kuliahs');
    }
};
