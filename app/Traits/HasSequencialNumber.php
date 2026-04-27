<?php

namespace App\Traits;

use App\Actions\GetNextSequencialNumber;
use Illuminate\Database\Eloquent\Model;

trait HasSequencialNumber
{
    protected static function bootHasSequencialNumber(): void
    {
        static::creating(function (Model $model) {
            $model->number = app(GetNextSequencialNumber::class)->execute($model->organization, $model->getTable());
        });

    }
}
