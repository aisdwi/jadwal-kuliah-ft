<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearInvalidRoomAssignmentsCommand extends Command
{
    protected $signature = 'schedule:clear-invalid-room-assignments {--force : Hapus jadwal invalid. Tanpa opsi ini hanya menghitung.}';

    protected $description = 'Hapus jadwal yang memakai ruangan di luar alokasi jurusan mata kuliah.';

    public function handle(): int
    {
        $ids = $this->invalidScheduleIds();

        if (! $this->option('force')) {
            $this->info('Jadwal invalid ditemukan: ' . count($ids));
            $this->warn('Jalankan ulang dengan --force untuk menghapus data tersebut.');

            return self::SUCCESS;
        }

        $deleted = 0;
        if ($ids !== []) {
            $deleted = DB::table('jadwal')->whereIn('id', $ids)->delete();
        }

        $this->info('Jadwal invalid yang dihapus: ' . $deleted);

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function invalidScheduleIds(): array
    {
        return DB::table('jadwal as j')
            ->join('kelas_kuliah as kk', 'kk.id', '=', 'j.kelas_kuliah_id')
            ->join('matakuliah as mk', 'mk.id', '=', 'kk.matakuliah_id')
            ->leftJoin('jurusan_ruangan as jr', function ($join): void {
                $join->on('jr.ruangan_id', '=', 'j.ruangan_id')
                    ->on('jr.jurusan_id', '=', 'mk.jurusan_id');
            })
            ->whereNotNull('mk.jurusan_id')
            ->whereNull('jr.ruangan_id')
            ->pluck('j.id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
