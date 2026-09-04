<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waktus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hari_id')
                ->references('id')
                ->on('haris')
                ->onDelete('cascade');
            $table->foreignUuid('jam_kuliah_id')
                ->references('id')
                ->on('jam_kuliahs')
                ->onDelete('cascade');
            $table->timestamps();
            
            // Junction table: unique combination of hari_id & jam_kuliah_id
            $table->unique(['hari_id', 'jam_kuliah_id']);
            $table->index('hari_id');
            $table->index('jam_kuliah_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waktus');
    }
};
