<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $street
 * @property string $neighborhood
 * @property string $number
 * @property string|null $complement
 * @property string $cep
 * @property string $city_ibge_code
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $organization_id
 * @property string $addressable_type
 * @property string $addressable_id
 * @property bool $is_default
 * @property-read Model|\Eloquent $addressable
 * @property-read City $city
 *
 * @method static \Database\Factories\AddressFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereAddressableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereAddressableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCityIbgeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereComplement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereNeighborhood($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withoutTrashed()
 *
 * @mixin \Eloquent
 */
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

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
