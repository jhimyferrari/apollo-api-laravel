<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $primaryKey = 'ibge_code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ibge_code',
        'name',
        'uf_ibge_code',
    ];

    public function uf()
    {
        return $this->belongsTo(Uf::class, 'uf_ibge_code', 'ibge_code');
    }

    public function ibgeCode(): string
    {
        return $this->ibge_code;
    }
}
