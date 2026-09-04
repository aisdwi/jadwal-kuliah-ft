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

        $canonicalRoles = [
            0 => 'Admin Fakultas',
            1 => 'Wakil Dekan I Bidang Akademik',
            2 => 'Sub-Koordinator Bidang Akademik',
            3 => 'Admin Jurusan',
            4 => 'Admin Prodi',
            5 => 'Dosen',
            999 => 'Super Admin',
        ];

        DB::transaction(function () use ($canonicalRoles): void {
            foreach ($canonicalRoles as $id => $name) {
                $exists = DB::table('role')->where('id', $id)->exists();

                if ($exists) {
                    DB::table('role')->where('id', $id)->update(['role' => $name]);
                    continue;
                }

                DB::table('role')->insert([
                    'id' => $id,
                    'role' => $name,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Irreversible safely: previous role labels vary across legacy dumps.
    }
};
