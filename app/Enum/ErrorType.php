<?php

namespace App\Enum;

enum ErrorType: string
{
    case InternalError = 'INTERNAL_ERROR';
    case Unauthenticated = 'UNAUTHENTICATED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';
    case ValidationError = 'VALIDATION_ERROR';

    case ResourceNotFound = 'RESOURCE_NOT_FOUND';
    case ModelIsDeleted = 'MODEL_IS_DELETED';
    case DuplicateField = 'DUPLICATE_FIELD';
    case InvalidPassword = 'INVALID_PASSWORD';
    case InvalidStatusTransition = 'INVALID_STATUS_TRANSITION';
    case InvalidStatus = 'INVALID_STATUS';
    case InsufficientStock = 'INSUFFICIENT_STOCK';
    case OrderNotDeletable = 'ORDER_NOT_DELETABLE';
    case InvalidField = 'INVALID_FIELD';
    case AdminCannotBeDeleted = 'ADMIN_CANNOT_BE_DELETED';
}
