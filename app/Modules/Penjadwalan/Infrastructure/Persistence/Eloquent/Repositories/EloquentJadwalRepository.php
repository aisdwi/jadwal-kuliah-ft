<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Penjadwalan\Domain\Repositories\JadwalRepository;
use App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class EloquentJadwalRepository implements JadwalRepository
{
    private const WITHS = [
        'kelasKuliah:id,dosen_id,matakuliah_id,kelas_id,jumlah_mahasiswa',
        'kelasKuliah.dosen:id,nama_lengkap',
        'kelasKuliah.matakuliah:id,nama_mk,kode_mk,sks',
        'kelasKuliah.kelas:id,nama_kelas,semester,program_studi_id,jurusan_id',
        'kelasKuliah.kelas.programStudi:id,nama_prodi',
        'slot:id,hari_id,waktu_id',
        'slot.hari:id,nama_hari',
        'slot.waktu:id,pukul,sks,jam_index',
        'ruangan:id,ruangan,kapasitas',
    ];

    public function __construct(protected JadwalModel $model) {}

    private function scopedQuery(): Builder
    {
        return (new JadwalScopedQuery($this->model))->make();
    }

    public function findAll(?int $programStudiId = null, ?int $semester = null, int|string $perPage = 'all'): array
    {
        $q = $this->scopedQuery()->with(self::WITHS)->orderBy('id');

        if ($programStudiId !== null) {
            $q->whereHas('kelasKuliah.kelas', fn ($q) => $q->where('program_studi_id', $programStudiId));
        }
        if ($semester !== null) {
            $q->whereHas('kelasKuliah.kelas', fn ($q) => $q->where('semester', $semester));
        }

        return JadwalPagedResult::fromQuery($q, $perPage);
    }

    public function findById(int $id)
    {
        return $this->scopedQuery()->with(self::WITHS)->find($id);
    }

    public function findByKelasKuliah(int $kelasKuliahId)
    {
        return $this->scopedQuery()
            ->with(self::WITHS)
            ->where('kelas_kuliah_id', $kelasKuliahId)
            ->first();
    }

    public function findByProgramStudi(int $programStudiId, ?int $semester = null): array
    {
        return $this->scopedQuery()
            ->with(self::WITHS)
            ->whereHas('kelasKuliah.kelas', function ($q) use ($programStudiId, $semester) {
                $q->where('program_studi_id', $programStudiId);
                if ($semester !== null) {
                    $q->where('semester', $semester);
                }
            })
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    /**
     * @return list<ScheduleAssignment>
     */
    public function findAssignmentsBySlot(int $slotId, ?int $ignoreKelasKuliahId = null): array
    {
        $slot = \App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel::find($slotId, ['id', 'hari_id']);
        if (!$slot) {
            return [];
        }

        $query = $this->model->newQuery()
            ->with([
                'kelasKuliah:id,kelas_id,dosen_id',
                'kelasKuliah.dosens:id',
                'slot:id,hari_id,waktu_id',
                'slot.waktu:id,pukul',
            ])
            ->whereHas('slot', fn ($slotQuery) => $slotQuery->where('hari_id', $slot->hari_id));

        if ($ignoreKelasKuliahId !== null) {
            $query->where('kelas_kuliah_id', '!=', $ignoreKelasKuliahId);
        }

        return $query
            ->get()
            ->map(fn (JadwalModel $jadwal) => (new ScheduleAssignmentMapper())->fromJadwal($jadwal))
            ->filter()
            ->values()
            ->all();
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $j = $this->scopedQuery()->find($id);
        if ($j) {
            $j->update($data);
        }
        return $j;
    }

    public function delete(int $id): void
    {
        $this->scopedQuery()->whereKey($id)->delete();
    }

    public function deleteByKelasKuliah(int $kelasKuliahId): void
    {
        $this->scopedQuery()->where('kelas_kuliah_id', $kelasKuliahId)->delete();
    }

    public function deleteAll(?int $programStudiId = null): int
    {
        return $this->scopedQuery()
            ->when($programStudiId !== null && $programStudiId > 0, function ($query) use ($programStudiId) {
                $query->whereHas('kelasKuliah.kelas', fn ($q) => $q->where('program_studi_id', $programStudiId));
            })
            ->delete();
    }

    public function deleteGeneratedByProgramStudi(int $programStudiId, ?int $semester = null): int
    {
        return $this->scopedQuery()
            ->where('origin', 'generated')
            ->whereHas('kelasKuliah.kelas', function ($q) use ($programStudiId, $semester) {
                $q->where('program_studi_id', $programStudiId);
                if ($semester !== null) {
                    $q->where('semester', $semester);
                }
            })
            ->delete();
    }

    public function findGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): array
    {
        return $this->generatedScopeQuery($scope, $semesterTipe)
            ->orderBy('id')
            ->get(['kelas_kuliah_id', 'slot_id', 'ruangan_id', 'origin', 'scheduling_run_id'])
            ->map(fn (JadwalModel $jadwal) => [
                'kelas_kuliah_id' => (int) $jadwal->kelas_kuliah_id,
                'slot_id' => (int) $jadwal->slot_id,
                'ruangan_id' => (int) $jadwal->ruangan_id,
                'origin' => 'generated',
                'scheduling_run_id' => empty($jadwal->scheduling_run_id) ? null : (int) $jadwal->scheduling_run_id,
            ])
            ->all();
    }

    public function deleteGeneratedForSchedulingScope(array $scope, ?string $semesterTipe = null): int
    {
        return $this->generatedScopeQuery($scope, $semesterTipe)->delete();
    }

    public function restoreGeneratedSchedules(array $schedules): int
    {
        $now = now();
        $rows = collect($schedules)
            ->map(function (array $schedule) use ($now): array {
                return [
                    'kelas_kuliah_id' => (int) $schedule['kelas_kuliah_id'],
                    'slot_id' => (int) $schedule['slot_id'],
                    'ruangan_id' => (int) $schedule['ruangan_id'],
                    'origin' => 'generated',
                    'scheduling_run_id' => empty($schedule['scheduling_run_id']) ? null : (int) $schedule['scheduling_run_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        if ($rows === []) {
            return 0;
        }

        $this->model->newQuery()->insert($rows);

        return count($rows);
    }

    private function generatedScopeQuery(array $scope, ?string $semesterTipe = null): Builder
    {
        return $this->scopedQuery()
            ->where('origin', 'generated')
            ->whereHas('kelasKuliah', function (Builder $query) use ($scope, $semesterTipe): void {
                if (!empty($scope['restrict_by_program_studi']) && !empty($scope['program_studi_id'])) {
                    $query->whereHas('kelas', fn (Builder $kelas) => $kelas->where('program_studi_id', (int) $scope['program_studi_id']));
                } elseif (!empty($scope['restrict_by_jurusan']) && !empty($scope['jurusan_id'])) {
                    $query->whereHas('kelas', fn (Builder $kelas) => $kelas->where('jurusan_id', (int) $scope['jurusan_id']));
                }

                $this->applySemesterTipeFilter($query, $semesterTipe);
            });
    }

    private function applySemesterTipeFilter(Builder $query, ?string $semesterTipe): void
    {
        $tipe = strtolower((string) $semesterTipe);
        if (!in_array($tipe, ['ganjil', 'genap'], true)) {
            return;
        }

        if (Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
            $query->where('semester_tipe', $tipe);
            return;
        }

        $query->whereHas('matakuliah', function (Builder $matakuliah) use ($tipe): void {
            $operator = $tipe === 'ganjil' ? '!=' : '=';
            $matakuliah->whereRaw("semester % 2 {$operator} 0");
        });
    }

}
