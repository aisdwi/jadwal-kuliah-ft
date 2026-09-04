<?php

namespace App\Modules\Resource\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GedungModel extends Model
{
    protected $table = 'gedung';

    protected $fillable = [
        'nama_gedung',
    ];

    public function ruangans(): HasMany
    {
        return $this->hasMany(RuanganModel::class, 'gedung_id');
    }
}
