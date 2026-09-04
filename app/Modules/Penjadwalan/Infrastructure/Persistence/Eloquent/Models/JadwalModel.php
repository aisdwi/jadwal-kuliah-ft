<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalModel extends Model
{
    protected $table = 'jadwal';

    protected $fillable = [
        'kelas_kuliah_id',
        'slot_id',
        'ruangan_id',
        'origin',
        'scheduling_run_id',
    ];

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel::class);
    }

    public function schedulingRun(): BelongsTo
    {
        return $this->belongsTo(SchedulingRunModel::class, 'scheduling_run_id');
    }
}
