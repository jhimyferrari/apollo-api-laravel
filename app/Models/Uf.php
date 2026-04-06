<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uf extends Model
{
    protected $table = 'ufs';

    protected $primaryKey = 'ibge_code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ibge_code',
        'name',
        'abbreviation',
    ];

    public function cities()
    {
        return $this->hasMany(City::class, 'uf_ibge_code', 'ibge_code');
    }
}
