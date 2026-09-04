<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulingRunModel extends Model
{
    protected $table = 'scheduling_runs';

    protected $fillable = [
        'user_id',
        'jurusan_id',
        'program_studi_id',
        'semester_tipe',
        'scope_label',
        'status',
        'params',
        'result_summary',
        'message',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'params' => 'array',
        'result_summary' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(JadwalModel::class, 'scheduling_run_id');
    }
}
