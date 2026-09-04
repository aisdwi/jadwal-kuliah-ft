<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Shared\Application\Traits\FiltersByJurusan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DosenModel extends Model
{
    use FiltersByJurusan;

    protected $table = 'dosen';

    protected $fillable = [
        'jurusan_id',
        'nip',
        'nama_lengkap',
        'inisial',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'json',
    ];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(JurusanModel::class);
    }

    public function kelasKuliahs(): HasMany
    {
        return $this->hasMany(\App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel::class, 'dosen_id');
    }

    public function kelasKuliahMany(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel::class, 'kelas_kuliah_dosen', 'dosen_id', 'kelas_kuliah_id')
            ->withPivot('preferred_slot_id')
            ->withTimestamps();
    }
}
