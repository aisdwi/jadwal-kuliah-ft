<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing') || ! Schema::hasTable('waktu')) {
            return;
        }

        // Keep production waktu rows aligned with the final scheduling slot catalog.
        $hasJamIndex = Schema::hasColumn('waktu', 'jam_index');
        $rows = $this->rows($hasJamIndex);
        $waktuIds = array_column($rows, 'id');

        DB::transaction(function () use ($rows, $waktuIds, $hasJamIndex): void {
            $slotIdsToRemove = Schema::hasTable('slot')
                ? DB::table('slot')->whereNotIn('waktu_id', $waktuIds)->pluck('id')->all()
                : [];

            if ($slotIdsToRemove !== []) {
                if (Schema::hasTable('jadwal')) {
                    DB::table('jadwal')->whereIn('slot_id', $slotIdsToRemove)->delete();
                }

                if (Schema::hasTable('jurusan_slot')) {
                    DB::table('jurusan_slot')->whereIn('slot_id', $slotIdsToRemove)->delete();
                }

                if (Schema::hasTable('kelas_kuliah_dosen') && Schema::hasColumn('kelas_kuliah_dosen', 'preferred_slot_id')) {
                    DB::table('kelas_kuliah_dosen')
                        ->whereIn('preferred_slot_id', $slotIdsToRemove)
                        ->update(['preferred_slot_id' => null]);
                }

                DB::table('slot')->whereIn('id', $slotIdsToRemove)->delete();
            }

            DB::table('waktu')->whereNotIn('id', $waktuIds)->delete();

            $updateColumns = ['pukul', 'sks', 'updated_at', 'created_at'];
            if ($hasJamIndex) {
                $updateColumns[] = 'jam_index';
            }

            DB::table('waktu')->upsert($rows, ['id'], $updateColumns);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE waktu AUTO_INCREMENT = 38');
        }
    }

    public function down(): void
    {
        //
    }

    private function rows(bool $hasJamIndex): array
    {
        return collect([
            [1, '08.00 - 09.40', 2, 1, '2024-07-25 17:51:33', '2024-07-25 17:51:33'],
            [2, '09.50 - 11.30', 2, 3, '2024-07-25 17:51:48', '2024-07-25 17:51:48'],
            [3, '11.40 - 12.30', 1, 4, null, null],
            [4, '13.00 - 14.40', 2, 5, '2024-07-25 17:52:14', '2024-07-25 17:52:14'],
            [5, '14.50 - 16.30', 2, 6, '2024-07-25 17:53:37', '2024-07-25 17:53:37'],
            [6, '16.40 - 17.30', 1, 7, '2024-07-25 17:54:16', '2024-07-25 17:54:16'],
            [7, '08.00 - 10.30', 3, 2, '2024-07-25 17:54:37', '2024-07-25 17:54:37'],
            [11, '16.40 - 18.20', 2, 7, '2024-07-25 17:57:30', '2024-07-25 17:57:30'],
            [12, '10.40 - 12.20', 2, 4, '2024-07-25 17:57:55', '2024-07-25 17:57:55'],
            [13, '13.00 - 16.20', 4, 6, '2024-07-25 17:58:10', '2024-07-25 17:58:10'],
            [14, '16.40 - 17.20', 1, 7, '2024-07-25 17:59:41', '2024-07-25 17:59:41'],
            [15, '13:00 - 15:30', 3, 5, null, null],
            [16, '15:40 - 17:20', 2, 6, null, null],
            [17, '13.30 - 16.00', 3, 5, '2024-07-25 18:00:38', '2024-07-25 18:00:38'],
            [18, '16.10 - 17.50', 2, 6, '2024-07-25 18:00:50', '2024-07-25 18:00:50'],
            [22, '14.50 - 17.20', 3, 6, '2026-05-19 02:08:43', '2026-05-19 02:08:43'],
            [23, '10.40 - 11.30', 1, 3, null, null],
            [26, '08.00 - 11.20', 4, 2, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [27, '10.40 - 11.30', 1, 3, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [28, '13.00 - 13.50', 1, 5, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [29, '14.00 - 14.50', 1, 6, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [30, '15.00 - 15.50', 1, 7, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [31, '16.00 - 16.50', 1, 8, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [32, '17.00 - 17.50', 1, 9, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [33, '08.00 - 08.50', 1, 1, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [34, '09.00 - 09.50', 1, 2, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [35, '10.00 - 10.50', 1, 3, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
            [36, '11.00 - 11.50', 1, 4, '2026-05-19 02:10:35', '2026-05-19 02:10:35'],
        ])->map(function (array $row) use ($hasJamIndex): array {
            $payload = [
                'id' => $row[0],
                'pukul' => $row[1],
                'sks' => $row[2],
                'updated_at' => $row[4],
                'created_at' => $row[5],
            ];

            if ($hasJamIndex) {
                $payload['jam_index'] = $row[3];
            }

            return $payload;
        })->all();
    }
};
