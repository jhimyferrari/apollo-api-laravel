<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidStatusException;
use App\Interfaces\HasStatus;

class ValidateStatusEnum
{
    public function execute(HasStatus $model, string $value)
    {
        $enum = $model->statusEnumClass();
        $newStatus = $enum::tryFrom($value);
        if ($newStatus === null) {
            throw new InvalidStatusException("`$value` status doesn`t exist for ".class_basename($model));
        }
    }
}
