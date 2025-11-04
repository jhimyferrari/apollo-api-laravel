<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uf extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'sigla',
        'codigo_ibge',
    ];

    protected $casts = [
        'id' => 'integer',
        'codigo_ibge' => 'string',
    ];
}
