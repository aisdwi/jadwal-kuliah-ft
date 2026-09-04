<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Resource\Domain\Repositories\SlotRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class EloquentSlotRepository implements SlotRepository
{
    public function __construct(protected SlotModel $model) {}

    public function all(?int $jurusanId = null): Collection
    {
        return $this->baseQuery($jurusanId)->get();
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()->with('hari', 'waktu', 'jurusans')->find($id);
    }

    public function findByHari(int $hariId, ?int $jurusanId = null): Collection
    {
        return $this->baseQuery($jurusanId)->where('hari_id', $hariId)->get();
    }

    public function findByHariAndWaktu(int $hariId, int $waktuId)
    {
        return $this->model->newQuery()
            ->with('hari', 'waktu', 'jurusans')
            ->where('hari_id', $hariId)
            ->where('waktu_id', $waktuId)
            ->first();
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $s = $this->model->newQuery()->find($id);
        if ($s) {
            $s->update($data);
        }
        return $s;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            if (Schema::hasTable('jadwal') && Schema::hasColumn('jadwal', 'slot_id')) {
                DB::table('jadwal')->where('slot_id', $id)->delete();
            }

            if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', 'slot_id')) {
                DB::table('kelas_kuliah')->where('slot_id', $id)->update(['slot_id' => null]);
            }

            if (Schema::hasTable('kelas_kuliah_dosen') && Schema::hasColumn('kelas_kuliah_dosen', 'preferred_slot_id')) {
                DB::table('kelas_kuliah_dosen')->where('preferred_slot_id', $id)->update(['preferred_slot_id' => null]);
            }

            if (Schema::hasTable('jurusan_slot') && Schema::hasColumn('jurusan_slot', 'slot_id')) {
                DB::table('jurusan_slot')->where('slot_id', $id)->delete();
            }

            $this->model->newQuery()->whereKey($id)->delete();
        });
    }

    private function baseQuery(?int $jurusanId = null)
    {
        return $this->model->newQuery()
            ->with('hari', 'waktu', 'jurusans')
            ->when($jurusanId, function ($query) use ($jurusanId) {
                $query->whereHas('jurusans', fn ($jurusanQuery) => $jurusanQuery->where('jurusan.id', $jurusanId));
            })
            ->orderBy('hari_id')
            ->join('waktu', 'waktu.id', '=', 'slot.waktu_id')
            ->orderByRaw('CASE WHEN waktu.jam_index IS NULL THEN 1 ELSE 0 END')
            ->orderBy('waktu.jam_index')
            ->orderBy('waktu.pukul')
            ->orderBy('slot.id')
            ->select('slot.*');
    }
}
