<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlotModel extends Model
{
    protected $table = 'slot';

    protected $fillable = [
        'hari_id',
        'waktu_id',
    ];

    public function hari(): BelongsTo
    {
        return $this->belongsTo(HariModel::class);
    }

    public function waktu(): BelongsTo
    {
        return $this->belongsTo(WaktuModel::class);
    }

    public function jurusans(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel::class,
            'jurusan_slot',
            'slot_id',
            'jurusan_id',
        )->withTimestamps();
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(\App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel::class, 'slot_id');
    }
}
