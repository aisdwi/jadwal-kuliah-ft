<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyRooms = [
            36 => [
                'ruangan' => 'Ruang Baru 1',
                'kapasitas' => 35,
                'gedung_id' => 1,
                'created_at' => '2024-07-26 07:40:56',
                'updated_at' => '2024-07-26 07:40:56',
            ],
            37 => [
                'ruangan' => 'Ruang Baru 2',
                'kapasitas' => 35,
                'gedung_id' => 1,
                'created_at' => '2024-07-26 07:41:06',
                'updated_at' => '2024-07-26 07:41:06',
            ],
        ];

        foreach ($legacyRooms as $roomId => $payload) {
            if (! DB::table('gedung')->where('id', $payload['gedung_id'])->exists()) {
                continue;
            }

            if (! DB::table('ruangan')->where('id', $roomId)->exists()) {
                DB::table('ruangan')->insert(['id' => $roomId] + $payload);
            }
        }
    }

    public function down(): void
    {
        // Data repair only. Rolling this back would re-break existing jadwal references.
    }
};
