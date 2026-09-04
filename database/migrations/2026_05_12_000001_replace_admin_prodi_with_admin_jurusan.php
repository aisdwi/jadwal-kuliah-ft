<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move users from Admin Prodi role to Admin Jurusan role
        $adminJurusan = DB::table('role')->where('role', 'Admin Jurusan')->first();
        $adminProdi   = DB::table('role')->where('role', 'Admin Prodi')->first();

        if ($adminProdi && $adminJurusan) {
            DB::table('users')
                ->where('role_id', $adminProdi->id)
                ->update(['role_id' => $adminJurusan->id]);

            DB::table('role')->where('id', $adminProdi->id)->delete();
        }

        // Also remove Kaprodi if present (no longer needed)
        DB::table('role')->where('role', 'Kaprodi')->delete();
    }

    public function down(): void
    {
        // Recreate Admin Prodi role
        if (!DB::table('role')->where('role', 'Admin Prodi')->exists()) {
            DB::table('role')->insert(['role' => 'Admin Prodi']);
        }
    }
};
