<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidStatusException;
use App\Interfaces\Models\HasStatus;
use Illuminate\Validation\ValidationException;

class ValidateStatusEnum
{
    public function execute(HasStatus $model, string $value)
    {
        $enum = $model->statusEnumClass();
        $newStatus = $enum::tryFrom($value);
        if ($newStatus === null) {
            report(new InvalidStatusException(__('errors.invalid_status', ['value' => $value, 'model' => \class_basename($model)])));
            throw ValidationException::withMessages(['status' => __('validation.status', ['attribute' => 'status', 'model' => \class_basename($model)])]);
        }
    }
}
