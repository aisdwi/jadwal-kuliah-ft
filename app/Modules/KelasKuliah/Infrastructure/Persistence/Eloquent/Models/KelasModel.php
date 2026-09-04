<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Shared\Application\Traits\FiltersByJurusan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasModel extends Model
{
    use FiltersByJurusan;

    protected $table = 'kelas';

    protected $fillable = [
        'program_studi_id',
        'jurusan_id',
        'nama_kelas',
        'semester',
    ];

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel::class);
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel::class);
    }

    public function kelasKuliahs(): HasMany
    {
        return $this->hasMany(KelasKuliahModel::class, 'kelas_id');
    }
}
