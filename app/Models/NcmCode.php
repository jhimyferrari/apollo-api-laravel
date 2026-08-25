<?php

namespace App\Models;

use Database\Factories\NcmCodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NcmCode extends Model
{
    /** @use HasFactory<NcmCodeFactory> */
    use HasFactory,HasUuids;

    protected $table = 'ncm_codes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'description',
        'valid_from',
        'isActive',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);

    }
}
