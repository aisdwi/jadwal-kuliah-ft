<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HariModel extends Model
{
    protected $table = 'hari';

    protected $fillable = ['nama_hari'];

    public function slots(): HasMany
    {
        return $this->hasMany(SlotModel::class, 'hari_id');
    }
}
