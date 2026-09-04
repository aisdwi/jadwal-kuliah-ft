<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $exists = DB::table('users')->where('email', 'admin@unri.ac.id')->exists();
        if (!$exists) {
            DB::table('users')->insertOrIgnore([
                [
                    'nama_user'  => 'Administrator',
                    'email'      => 'admin@unri.ac.id',
                    'password'   => Hash::make('admin123'),
                    'role_id'    => 1,
                    'jurusan_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
