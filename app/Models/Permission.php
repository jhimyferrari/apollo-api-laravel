<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'status_permission',
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions');
    }
}
