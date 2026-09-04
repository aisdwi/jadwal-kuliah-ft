<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jurusan_slot')) {
            if (DB::table('jurusan_slot')->count() > 0) {
                return;
            }

            Schema::drop('jurusan_slot');
        }

        Schema::create('jurusan_slot', function (Blueprint $table) {
            $table->id();
            $table->integer('jurusan_id');
            $table->integer('slot_id');
            $table->timestamps();

            $table->unique(['jurusan_id', 'slot_id'], 'jurusan_slot_jurusan_id_slot_id_unique');
            $table->foreign('jurusan_id', 'jurusan_slot_jurusan_id_foreign')
                ->references('id')
                ->on('jurusan')
                ->onDelete('cascade');
            $table->foreign('slot_id', 'jurusan_slot_slot_id_foreign')
                ->references('id')
                ->on('slot')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusan_slot');
    }
};
