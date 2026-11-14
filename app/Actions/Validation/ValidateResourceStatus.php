<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidStatusException;
use App\Interfaces\Models\HasStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ValidateResourceStatus
{
    public function execute(HasStatus $resource, string $field): void
    {
        if (! $resource->isActive()) {
            $resourceKey = Str::snake(class_basename($resource));
            $resourceLabel = __('validation.resources.'.$resourceKey);

            report(new InvalidStatusException(__('errors.resource_inactive', [
                'resource' => class_basename($resource),
                'id' => (string) $resource->getKey(),
            ])));

            throw ValidationException::withMessages([
                $field => __('validation.resource_inactive', ['resource' => $resourceLabel]),
            ]);
        }
    }
}
