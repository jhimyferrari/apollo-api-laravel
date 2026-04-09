<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([OrganizationScope::class])]
class Address extends Model
{
    use HasFactory,HasUuids,SoftDeletes;

    protected $table = 'addresses';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'neighborhood',
        'street',
        'number',
        'complement',
        'cep',
        'city_ibge_code',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Address $address) {
            if (auth()->check()) {
                $address->organization_id ??= auth()->user()->organization_id;
            }
        });
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_ibge_code', 'ibge_code');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}
