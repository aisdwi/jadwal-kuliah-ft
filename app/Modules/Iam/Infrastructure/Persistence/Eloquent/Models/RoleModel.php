<?php

namespace App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleModel extends Model
{
    protected $table = 'role';

    protected $fillable = [
        'role',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(UserModel::class, 'role_id');
    }
}
