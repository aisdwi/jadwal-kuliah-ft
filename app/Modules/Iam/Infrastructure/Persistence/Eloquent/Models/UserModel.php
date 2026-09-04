<?php

namespace App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\DosenModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\JurusanModel;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'nama_user',
        'email',
        'password',
        'role_id',
        'dosen_id',
        'jurusan_id',
        'program_studi_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(DosenModel::class);
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(JurusanModel::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudiModel::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function getNameAttribute(): ?string
    {
        return $this->getAttribute('nama_user');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama_user'] = $value;
    }
}
