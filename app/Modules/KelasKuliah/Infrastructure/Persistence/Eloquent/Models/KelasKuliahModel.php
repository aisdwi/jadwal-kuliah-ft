<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Shared\Application\Traits\FiltersByJurusan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class KelasKuliahModel extends Model
{
    use FiltersByJurusan;

    protected $table = 'kelas_kuliah';

    protected $fillable = [
        'dosen_id',
        'matakuliah_id',
        'kelas_id',
        'jumlah_mahasiswa',
        'semester_tipe',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (KelasKuliahModel $model): void {
            KelasKuliahAcademicPeriodDefaults::fill($model);
        });

        static::updating(function (KelasKuliahModel $model): void {
            KelasKuliahAcademicPeriodDefaults::fill($model);
        });
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel::class);
    }

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\MataKuliahModel::class, 'matakuliah_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(KelasModel::class);
    }

    public function ruangan(): HasOneThrough
    {
        return $this->hasOneThrough(
            \App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\RuanganModel::class,
            \App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel::class,
            'kelas_kuliah_id',
            'id',
            'id',
            'ruangan_id',
        );
    }

    public function slot(): HasOneThrough
    {
        return $this->hasOneThrough(
            \App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models\SlotModel::class,
            \App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel::class,
            'kelas_kuliah_id',
            'id',
            'id',
            'slot_id',
        );
    }

    public function dosens(): BelongsToMany
    {
        return $this->belongsToMany(\App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel::class, 'kelas_kuliah_dosen', 'kelas_kuliah_id', 'dosen_id')
            ->withPivot('preferred_slot_id', 'is_external')
            ->withTimestamps();
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(\App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models\JadwalModel::class, 'kelas_kuliah_id');
    }
}
