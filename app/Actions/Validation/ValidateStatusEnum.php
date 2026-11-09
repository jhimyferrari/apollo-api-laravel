<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidStatusException;
use App\Interfaces\Models\HasStatus;
use Illuminate\Validation\ValidationException;

class ValidateStatusEnum
{
    public function execute(HasStatus $model, string|\BackedEnum $value): void
    {
        $enum = $model->statusEnumClass();
        $strValue = $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
        $newStatus = $enum::tryFrom($strValue);
        if ($newStatus === null) {
            report(new InvalidStatusException(__('errors.invalid_status', ['value' => $strValue, 'model' => \class_basename($model)])));
            throw ValidationException::withMessages(['status' => __('validation.status', ['attribute' => 'status', 'model' => \class_basename($model)])]);
        }
    }
}
