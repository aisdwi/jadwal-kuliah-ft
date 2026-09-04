<?php

namespace Database\Seeders;

use App\Modules\Resource\Application\Service\JurusanRuanganAllocationService;
use Illuminate\Database\Seeder;

class JurusanRuanganSeeder extends Seeder
{
    public function run(): void
    {
        $report = app(JurusanRuanganAllocationService::class)->applyRecommendation();

        if ($this->command === null) {
            return;
        }

        $this->command->info('Penjatahan ruangan jurusan berhasil dihitung ulang.');
        $this->command->line('Dasar hitung: seluruh kelas non-MKU, posisi jadwal saat ini diabaikan.');
        $this->command->newLine();

        foreach ($report['allocations'] as $allocation) {
            $rooms = collect($allocation['rooms'])->pluck('ruangan')->implode(', ');

            $this->command->line(sprintf(
                '- %s: %d ruangan (%d tetap, %d hasil distribusi)',
                $allocation['nama_jurusan'],
                $allocation['total_room_count'],
                $allocation['fixed_room_count'],
                $allocation['variable_room_count'],
            ));

            $this->command->line('  ' . $rooms);
        }
    }
}
