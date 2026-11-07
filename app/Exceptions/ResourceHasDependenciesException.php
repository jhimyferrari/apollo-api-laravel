<?php

namespace App\Exceptions;

use App\Enum\ErrorType;
use Illuminate\Database\Eloquent\Model;

class ResourceHasDependenciesException extends BaseException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: ErrorType::ResourceHasDependencies
        );
    }

    /**
     * @param  array<int, string>  $relations
     */
    public static function withRelations(Model|string $resource, array $relations): self
    {
        $resourceName = is_string($resource) ? $resource : class_basename($resource);

        return new self(
            message: __('messages.resource_has_dependencies', [
                'resource' => $resourceName,
                'dependencies' => implode(', ', $relations),
            ])
        );
    }
}
