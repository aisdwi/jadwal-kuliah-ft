<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role')) {
            return;
        }

        $roles = [
            6 => 'Ketua Jurusan',
            7 => 'Koordinator Program Studi',
        ];

        DB::transaction(function () use ($roles): void {
            foreach ($roles as $id => $name) {
                DB::table('role')->updateOrInsert(
                    ['id' => $id],
                    ['role' => $name]
                );
            }

            DB::table('role')->where('role', 'Kajur')->delete();
            DB::table('role')->where('role', 'Kaprodi')->delete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('role')) {
            return;
        }

        DB::table('role')->whereIn('id', [6, 7])->delete();
    }
};
