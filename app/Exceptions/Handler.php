<?php

namespace App\Exceptions;

use App\Enum\ErrorType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler
{
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        $headers = [];

        [$status, $code, $message, $extra] = match (true) {
            $e instanceof BaseException => [$e->getStatusCode(), $e->getErrorCode(), null, []],
            $e instanceof AuthenticationException => [401, ErrorType::Unauthenticated, __('errors.unauthenticated'), []],
            $e instanceof AuthorizationException => [403, ErrorType::Forbidden, __('errors.forbidden'), []],
            $e instanceof HttpException && $e->getStatusCode() === 403 => [403, ErrorType::Forbidden, __('errors.forbidden'), []],
            $e instanceof ThrottleRequestsException => [429, ErrorType::TooManyRequests, __('errors.too_many_requests'), []],
            $e instanceof HttpException && $e->getStatusCode() === 429 => [429, ErrorType::TooManyRequests, __('errors.too_many_requests'), []],
            $e instanceof ModelNotFoundException => [404, ErrorType::NotFound, __('errors.resource_not_found'), []],
            $e instanceof NotFoundHttpException => [404, ErrorType::NotFound, __('errors.resource_not_found'), []],
            $e instanceof MethodNotAllowedHttpException => [405, ErrorType::MethodNotAllowed, __('errors.method_not_allowed'), []],
            $e instanceof ValidationException => [422, ErrorType::ValidationError, null, ['fields' => $e->errors()]],
            default => [500, ErrorType::InternalError, __('errors.internal_error'), []],
        };

        if ($e instanceof HttpException) {
            $headers = $e->getHeaders();
        }

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message ?? $e->getMessage(),
                ...$extra,
            ],
        ], $status, $headers);
    }
}
