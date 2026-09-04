<?php

namespace App\Modules\Penjadwalan\Infrastructure\Scheduling\Repositories;

use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;

final class SchedulingKelasKuliahHydrator
{
    public function hydrate(KelasKuliahModel $model): KelasKuliahModel
    {
        $jadwal = $model->jadwals->sortByDesc('id')->first();
        if ($jadwal) {
            $model->setAttribute('slot_id', $jadwal->slot_id);
            $model->setAttribute('ruangan_id', $jadwal->ruangan_id);
        }

        return $model;
    }
}
