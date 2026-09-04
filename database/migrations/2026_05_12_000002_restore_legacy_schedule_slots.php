<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->restoreLegacyWaktu();
        $this->restoreLegacySlots();
    }

    public function down(): void
    {
        // Data repair only. Rolling this back would re-break existing jadwal references.
    }

    private function restoreLegacyWaktu(): void
    {
        if (! DB::table('waktu')->where('id', 11)->exists()) {
            $nextJamIndex = ((int) DB::table('waktu')->max('jam_index')) + 1;

            DB::table('waktu')->insert([
                'id' => 11,
                'pukul' => '16.40 - 18.20',
                'sks' => 2,
                'jam_index' => $nextJamIndex > 0 ? $nextJamIndex : 16,
                'created_at' => '2024-07-26 00:57:30',
                'updated_at' => '2024-07-26 00:57:30',
            ]);
        }
    }

    private function restoreLegacySlots(): void
    {
        $legacySlots = [
            1 => [
                'hari_id' => 1,
                'waktu_id' => 2,
                'created_at' => '2024-07-26 01:01:58',
                'updated_at' => '2024-07-26 01:01:58',
            ],
            24 => [
                'hari_id' => 2,
                'waktu_id' => 11,
                'created_at' => '2024-07-26 18:24:24',
                'updated_at' => '2024-07-26 18:24:24',
            ],
        ];

        foreach ($legacySlots as $slotId => $payload) {
            $hasReferences = DB::table('hari')->where('id', $payload['hari_id'])->exists()
                && DB::table('waktu')->where('id', $payload['waktu_id'])->exists();

            if (! $hasReferences) {
                continue;
            }

            if (! DB::table('slot')->where('id', $slotId)->exists()) {
                DB::table('slot')->insert(['id' => $slotId] + $payload);
            }
        }
    }
};
