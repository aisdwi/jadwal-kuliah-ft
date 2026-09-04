<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurusanModel extends Model
{
    protected $table = 'jurusan';

    protected $fillable = [
        'nama_jurusan',
    ];

    public function programStudis(): HasMany
    {
        return $this->hasMany(ProgramStudiModel::class, 'jurusan_id');
    }

    public function dosens(): HasMany
    {
        return $this->hasMany(DosenModel::class, 'jurusan_id');
    }

    public function ruangans(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel::class, 'jurusan_ruangan', 'jurusan_id', 'ruangan_id');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(\App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasModel::class, 'jurusan_id');
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliahModel::class, 'jurusan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel::class, 'jurusan_id');
    }
}
