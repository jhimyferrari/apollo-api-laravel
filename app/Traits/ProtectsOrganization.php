<?php

namespace App\Traits;

use App\Exceptions\Auth\ForbiddenException;
use Illuminate\Database\Eloquent\Model;

trait ProtectsOrganization
{
    public static function bootProtectsOrganization()
    {

        static::updating(function (Model $model) {
            if ($model->isDirty('organization_id')) {
                throw new ForbiddenException('organization_id cannot be changed after creation.');
            }
        });
    }
}
