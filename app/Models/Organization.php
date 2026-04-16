<?php

namespace App\Models;

use App\Helpers\DocumentHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

class Organization extends Model
{
    use HasFactory,HasUuids;

    protected $table = 'organizations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'document',
    ];

    public static function boot()
    {
        parent::boot();

        static::created(function (Organization $organization) {
            $organization->sequencialNumber()->create();
        });
    }

    public function document(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $formatedValue = DocumentHelper::formatCpfAndCnpj($value);

                if (\strlen($formatedValue) === 11 || \strlen($formatedValue) === 14) {
                    return $formatedValue;
                }
                throw new InvalidArgumentException;
            }
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function sequencialNumber(): HasOne
    {
        return $this->hasOne(SequencialNumber::class);
    }
}
