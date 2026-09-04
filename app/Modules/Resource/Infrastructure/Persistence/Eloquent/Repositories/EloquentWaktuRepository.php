<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Resource\Domain\Repositories\WaktuRepository;
use App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\WaktuModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentWaktuRepository implements WaktuRepository
{
    public function __construct(protected WaktuModel $model) {}

    public function all(?string $search = null): Collection
    {
        $q = $this->model->newQuery();
        if ($search) {
            $q->where('pukul', 'like', "%{$search}%");
        }
        return $q
            ->orderByRaw('CASE WHEN jam_index IS NULL THEN 1 ELSE 0 END')
            ->orderBy('jam_index')
            ->orderBy('pukul')
            ->orderBy('id')
            ->get();
    }

    public function findById(int $id)
    {
        return $this->model->newQuery()->find($id);
    }

    public function create(array $data)
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data)
    {
        $w = $this->model->newQuery()->find($id);
        if ($w) {
            $w->update($data);
        }
        return $w;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $slotIds = [];
            if (Schema::hasTable('slot') && Schema::hasColumn('slot', 'waktu_id')) {
                $slotIds = DB::table('slot')->where('waktu_id', $id)->pluck('id')->all();
            }

            if ($slotIds !== []) {
                if (Schema::hasTable('jadwal') && Schema::hasColumn('jadwal', 'slot_id')) {
                    DB::table('jadwal')->whereIn('slot_id', $slotIds)->delete();
                }

                if (Schema::hasTable('kelas_kuliah') && Schema::hasColumn('kelas_kuliah', 'slot_id')) {
                    DB::table('kelas_kuliah')->whereIn('slot_id', $slotIds)->update(['slot_id' => null]);
                }

                if (Schema::hasTable('kelas_kuliah_dosen') && Schema::hasColumn('kelas_kuliah_dosen', 'preferred_slot_id')) {
                    DB::table('kelas_kuliah_dosen')->whereIn('preferred_slot_id', $slotIds)->update(['preferred_slot_id' => null]);
                }

                if (Schema::hasTable('jurusan_slot') && Schema::hasColumn('jurusan_slot', 'slot_id')) {
                    DB::table('jurusan_slot')->whereIn('slot_id', $slotIds)->delete();
                }

                DB::table('slot')->whereIn('id', $slotIds)->delete();
            }

            $this->model->newQuery()->whereKey($id)->delete();
        });
    }
}
