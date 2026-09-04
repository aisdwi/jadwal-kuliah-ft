<?php

namespace App\Modules\Penjadwalan\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulingOperationSnapshotModel extends Model
{
    protected $table = 'scheduling_operation_snapshots';

    protected $fillable = [
        'user_id',
        'jurusan_id',
        'program_studi_id',
        'scheduling_run_id',
        'scope_label',
        'action_type',
        'snapshot_count',
        'snapshot_payload',
        'restored_at',
    ];

    protected $casts = [
        'snapshot_payload' => 'array',
        'restored_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function schedulingRun(): BelongsTo
    {
        return $this->belongsTo(SchedulingRunModel::class, 'scheduling_run_id');
    }
}
