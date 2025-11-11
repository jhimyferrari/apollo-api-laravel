<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Endereco extends Model
{
    protected $table = 'enderecos';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'bairro',
        'numero',
        'complemento',
        'cep',
        'municipio_codigo_ibge',
    ];

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_codigo_ibge', 'codigo_ibge');
    }
}
