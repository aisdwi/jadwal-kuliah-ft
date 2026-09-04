<?php

namespace App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Shared\Application\Traits\FiltersByJurusan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudiModel extends Model
{
    use FiltersByJurusan;

    protected $table = 'program_studi';

    protected $fillable = [
        'jurusan_id',
        'nama_prodi',
    ];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(JurusanModel::class);
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliahModel::class, 'program_studi_id');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(\App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasModel::class, 'program_studi_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel::class, 'program_studi_id');
    }
}
