<?php

namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AsMoney implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return ($value == null) ? null : Money::fromStorage($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {

        if ($value == null) {
            return null;
        }
        throw_unless(
            $value instanceof Money,
            \RuntimeException::class,
            "The field `$key` must be of type Money,".\gettype($value).' received'
        );

        /**
         * @var Money $value
         */
        return $value->toStorageString();

    }
}
