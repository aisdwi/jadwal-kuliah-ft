<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaktuModel extends Model
{
    protected $table = 'waktu';

    protected $fillable = [
        'pukul',
        'sks',
        'jam_index',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(SlotModel::class, 'waktu_id');
    }
}
