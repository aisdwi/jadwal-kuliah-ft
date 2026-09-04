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
        Schema::create('jurusan_ruangan', function (Blueprint $table) {
            $table->id();
            $table->integer('jurusan_id');
            $table->integer('ruangan_id');
            
            $table->foreign('jurusan_id')->references('id')->on('jurusan')->onDelete('cascade');
            $table->foreign('ruangan_id')->references('id')->on('ruangan')->onDelete('cascade');
            
            $table->timestamps();
            
            // Ensure no duplicate mappings
            $table->unique(['jurusan_id', 'ruangan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurusan_ruangan');
    }
};
