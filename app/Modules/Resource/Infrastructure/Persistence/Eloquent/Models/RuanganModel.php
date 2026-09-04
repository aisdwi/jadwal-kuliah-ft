<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuanganModel extends Model
{
    protected $table = 'ruangan';

    protected $fillable = [
        'gedung_id',
        'ruangan',
        'kapasitas',
    ];

    public function gedung(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\GedungModel::class);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(\App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel::class, 'ruangan_id');
    }

    public function jurusans(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel::class, 'jurusan_ruangan', 'ruangan_id', 'jurusan_id');
    }
}
