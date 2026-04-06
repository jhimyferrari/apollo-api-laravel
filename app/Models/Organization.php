<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Str;

class Organization extends Model
{
    use HasFactory;

    protected $table = 'organizations';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'document',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

        });
    }

    public function document(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $formatedValue = preg_replace('/[^\d]/', '', $value);

                if (\strlen($formatedValue) === 11 || \strlen($formatedValue) === 14) {
                    return $formatedValue;
                }
                throw new InvalidArgumentException;
            }
        );
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
