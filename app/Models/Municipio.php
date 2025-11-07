<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'municipios';

    protected $primaryKey = 'codigo_ibge';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo_ibge',
        'nome',
        'uf_codigo_ibge',
    ];

    public function uf()
    {
        return $this->belongsTo(Uf::class, 'uf_codigo_ibge', 'codigo_ibge');
    }
}
