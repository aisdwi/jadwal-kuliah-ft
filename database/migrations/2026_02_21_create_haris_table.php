<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haris', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_hari'); // Senin, Selasa, dst
            $table->integer('urutan');   // 1, 2, 3, dst (untuk sorting)
            $table->timestamps();
            
            $table->index('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haris');
    }
};
