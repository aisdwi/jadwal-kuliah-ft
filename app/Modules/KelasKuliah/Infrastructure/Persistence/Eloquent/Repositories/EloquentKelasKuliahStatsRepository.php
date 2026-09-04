<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\KelasKuliah\Domain\Repositories\KelasKuliahStatsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentKelasKuliahStatsRepository implements KelasKuliahStatsRepository
{
    public function stats(?string $semesterTipe = null, ?int $programStudiId = null, ?int $jurusanId = null): array
    {
        $slotJurusanId = $jurusanId ?? $this->jurusanIdForProgramStudi($programStudiId);
        $jumlahRuang = DB::table('ruangan')->count();
        $jumlahSlot = (clone $this->slotQuery($slotJurusanId))->count();

        $sksFor = fn (int $sks) => (clone $this->slotQuery($slotJurusanId))
            ->join('waktu', 'slot.waktu_id', '=', 'waktu.id')
            ->where('waktu.sks', $sks)
            ->count();

        $base = DB::table('kelas_kuliah')
            ->join('matakuliah', 'kelas_kuliah.matakuliah_id', '=', 'matakuliah.id');

        if ($semesterTipe) {
            $tipe = strtolower(trim($semesterTipe));
            if (Schema::hasColumn('kelas_kuliah', 'semester_tipe') && in_array($tipe, ['ganjil', 'genap'], true)) {
                $base->where('kelas_kuliah.semester_tipe', $tipe);
            } elseif ($tipe === 'ganjil') {
                $base->whereRaw('matakuliah.semester % 2 != 0');
            } elseif ($tipe === 'genap') {
                $base->whereRaw('matakuliah.semester % 2 = 0');
            }
        }

        if ($programStudiId !== null) {
            $base->where('matakuliah.program_studi_id', $programStudiId);
        }

        if ($jurusanId !== null) {
            $base->where('matakuliah.jurusan_id', $jurusanId);
        }

        $baseForSks = fn (int $sks) => (clone $base)->where('matakuliah.sks', $sks)->count();

        return [
            'slots' => [
                'total' => $jumlahRuang * $jumlahSlot,
                'sks_1' => $sksFor(1) * $jumlahRuang,
                'sks_2' => $sksFor(2) * $jumlahRuang,
                'sks_3' => $sksFor(3) * $jumlahRuang,
                'sks_4' => $sksFor(4) * $jumlahRuang,
            ],
            'classes' => [
                'total' => (clone $base)->count(),
                'sks_1' => $baseForSks(1),
                'sks_2' => $baseForSks(2),
                'sks_3' => $baseForSks(3),
                'sks_4' => $baseForSks(4),
            ],
        ];
    }

    private function slotQuery(?int $jurusanId)
    {
        return DB::table('slot')
            ->when($jurusanId !== null, function ($query) use ($jurusanId) {
                $query->join('jurusan_slot', 'slot.id', '=', 'jurusan_slot.slot_id')
                    ->where('jurusan_slot.jurusan_id', $jurusanId);
            });
    }

    private function jurusanIdForProgramStudi(?int $programStudiId): ?int
    {
        if ($programStudiId === null) {
            return null;
        }

        $jurusanId = DB::table('program_studi')->where('id', $programStudiId)->value('jurusan_id');

        return $jurusanId === null ? null : (int) $jurusanId;
    }
}
