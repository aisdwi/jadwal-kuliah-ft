<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! Schema::hasTable('waktu')) {
            return;
        }

        $hasJamIndex = Schema::hasColumn('waktu', 'jam_index');
        $rows = collect([
            [1, '08.00 - 09.40', 2, 1, '2024-07-26 00:51:33', '2024-07-26 00:51:33'],
            [2, '09.50 - 11.30', 2, 2, '2024-07-26 00:51:48', '2024-07-26 00:51:48'],
            [3, '11.40 - 12.30', 1, 3, null, null],
            [4, '13.00 - 14.40', 2, 5, '2024-07-26 00:52:14', '2024-07-26 00:52:14'],
            [5, '14.50 - 16.30', 2, 6, '2024-07-26 00:53:37', '2024-07-26 00:53:37'],
            [6, '16.40 - 17.30', 1, 7, '2024-07-26 00:54:16', '2024-07-26 00:54:16'],
            [7, '08.00 - 10.30', 3, 1, '2024-07-26 00:54:37', '2024-07-26 00:54:37'],
            [12, '10.40 - 12.20', 2, 3, '2024-07-26 00:57:55', '2024-07-26 00:57:55'],
            [13, '13.00 - 16.20', 4, 5, '2024-07-26 00:58:10', '2024-07-26 00:58:10'],
            [14, '16.40 - 17.20', 1, 7, '2024-07-26 00:59:41', '2024-07-26 00:59:41'],
            [15, '13:00 - 15:30', 3, 5, null, null],
            [16, '15:40 - 17:20', 2, 6, null, null],
            [17, '13.30 - 16.00', 3, 5, '2024-07-26 01:00:38', '2024-07-26 01:00:38'],
            [18, '16.10 - 17.50', 2, 7, '2024-07-26 01:00:50', '2024-07-26 01:00:50'],
            [23, '10.40 - 11.30', 1, 2, null, null],
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

        $updateColumns = ['pukul', 'sks', 'updated_at', 'created_at'];
        if ($hasJamIndex) {
            $updateColumns[] = 'jam_index';
        }

        DB::table('waktu')->upsert($rows, ['id'], $updateColumns);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE waktu AUTO_INCREMENT = 24');
        }
    }

    public function down(): void
    {
        //
    }
};
