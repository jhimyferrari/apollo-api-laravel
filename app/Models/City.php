<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $ibge_code
 * @property string $name
 * @property string $uf_ibge_code
 * @property-read Uf $uf
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereIbgeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|City whereUfIbgeCode($value)
 *
 * @mixin \Eloquent
 */
class City extends Model
{
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
