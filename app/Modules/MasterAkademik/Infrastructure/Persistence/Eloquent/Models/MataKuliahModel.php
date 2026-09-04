<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Shared\Application\Traits\FiltersByJurusan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliahModel extends Model
{
    use FiltersByJurusan;

    protected $table = 'matakuliah';

    protected $fillable = [
        'program_studi_id',
        'jurusan_id',
        'kode_mk',
        'nama_mk',
        'sks',
        'semester',
    ];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudiModel::class);
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(JurusanModel::class);
    }

    public function kelasKuliahs(): HasMany
    {
        return $this->hasMany(\App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel::class, 'matakuliah_id');
    }
}
