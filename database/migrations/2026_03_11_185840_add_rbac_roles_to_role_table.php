<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename existing "Super User" to "Super Admin"
        DB::table('role')->where('role', 'Super User')->update(['role' => 'Super Admin']);

        // Add new roles if not already present (table has no timestamps)
        $newRoles = ['Admin Fakultas', 'Admin Jurusan'];
        foreach ($newRoles as $roleName) {
            if (!DB::table('role')->where('role', $roleName)->exists()) {
                DB::table('role')->insert(['role' => $roleName]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role')->where('role', 'Super Admin')->update(['role' => 'Super User']);
        DB::table('role')->whereIn('role', ['Admin Fakultas', 'Admin Jurusan'])->delete();
    }
};
