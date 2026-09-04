<?php

namespace App\Console\Commands;

use App\Console\Commands\Scheduling\JurusanSlotRegenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegenerateJurusanSlotsCommand extends Command
{
    protected $signature = 'schedule:regenerate-jurusan-slots {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus jadwal dan slot lama, lalu buat slot jadwal per jurusan berdasarkan SKS mata kuliah.';

    public function handle(): int
    {
        if (! Schema::hasTable('jurusan_slot')) {
            $this->error('Tabel jurusan_slot belum ada. Jalankan php artisan migrate terlebih dahulu.');
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Operasi ini akan menghapus jadwal dan slot lama. Lanjutkan?')) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        $summary = DB::transaction(fn (): array => (new JurusanSlotRegenerator())->regenerate());

        $this->info('Data jadwal lama dan slot lama sudah dibersihkan.');
        $this->table(['Jurusan', 'SKS Mata Kuliah', 'Slot Jurusan', 'Catatan'], $summary);
        $this->info('Total slot global: ' . DB::table('slot')->count());
        $this->info('Total relasi jurusan_slot: ' . DB::table('jurusan_slot')->count());

        return self::SUCCESS;
    }
}
