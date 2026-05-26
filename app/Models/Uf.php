<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $ibge_code
 * @property string $name
 * @property string $abbreviation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\City> $cities
 * @property-read int|null $cities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Uf newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Uf newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Uf query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Uf whereAbbreviation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Uf whereIbgeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Uf whereName($value)
 * @mixin \Eloquent
 */
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
