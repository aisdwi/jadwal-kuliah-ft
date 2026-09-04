<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\WaktuModel;
use App\Modules\Shared\Application\Service\AcademicScopeQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class SchedulingGenerationDatasetQuery
{
    public function __construct(
        private readonly SchedulingKelasKuliahHydrator $hydrator = new SchedulingKelasKuliahHydrator(),
    ) {}

    public function getDataset(array $scope, ?string $semesterTipe): array
    {
        $kuliah = $this->kuliahQuery($scope, $semesterTipe)
            ->whereDoesntHave('jadwals')
            ->get();

        return [
            'waktu' => WaktuModel::all(),
            'ruang' => RuanganModel::all(),
            'slot' => SchedulingSlotQuery::forJurusan($this->jurusanId($scope))->with(['hari', 'waktu'])->orderBy('id')->get(),
            'kuliah' => $kuliah,
            'manual' => $this->fixedSchedules($kuliah->pluck('id'), $semesterTipe),
            'jurusan_room_map' => $this->jurusanRoomMap(),
            'mku_room_ids' => $this->mkuRoomIds(),
        ];
    }

    private function kuliahQuery(array $scope, ?string $semesterTipe): Builder
    {
        $query = AcademicScopeQuery::applyToKelasKuliahQuery(KelasKuliahModel::query(), $scope)->with($this->relations());
        $this->applySemesterFilter($query, $semesterTipe);

        return $query;
    }

    private function fixedSchedules($targetKuliahIds, ?string $semesterTipe)
    {
        $query = KelasKuliahModel::query()->with($this->relations());
        $this->applySemesterFilter($query, $semesterTipe);

        return $query
            ->whereHas('jadwals')
            ->when($targetKuliahIds->isNotEmpty(), fn (Builder $q) => $q->whereNotIn('id', $targetKuliahIds->all()))
            ->get()
            ->map(fn (KelasKuliahModel $model) => $this->hydrator->hydrate($model))
            ->filter(fn (KelasKuliahModel $model) => !empty($model->slot_id) && !empty($model->ruangan_id))
            ->values();
    }

    private function relations(): array
    {
        return [
            'dosens',
            'matakuliah',
            'kelas',
            'dosen',
            'jadwals' => fn ($query) => $query->orderByDesc('id'),
        ];
    }

    private function applySemesterFilter(Builder $query, ?string $semesterTipe): void
    {
        $tipe = strtolower((string) $semesterTipe);
        if (!in_array($tipe, ['ganjil', 'genap'], true)) {
            return;
        }

        if (Schema::hasColumn('kelas_kuliah', 'semester_tipe')) {
            $query->where('semester_tipe', $tipe);
            return;
        }

        $query->whereHas('matakuliah', function ($q) use ($tipe) {
            $operator = $tipe === 'ganjil' ? '!=' : '=';
            $q->whereRaw("semester % 2 {$operator} 0");
        });
    }

    private function jurusanRoomMap(): array
    {
        $map = [];
        foreach (JurusanModel::with('ruangans')->get() as $jurusan) {
            $map[$jurusan->id] = $jurusan->ruangans->pluck('id')->toArray();
        }

        return $map;
    }

    private function mkuRoomIds(): array
    {
        return RuanganModel::whereHas('gedung', fn ($q) => $q->where('nama_gedung', 'LIKE', '%Integrated%'))
            ->pluck('id')
            ->toArray();
    }

    private function jurusanId(array $scope): ?int
    {
        return empty($scope['jurusan_id']) ? null : (int) $scope['jurusan_id'];
    }
}
