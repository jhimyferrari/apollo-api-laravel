<?php

namespace App\Exceptions;

use App\Enum\ErrorType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler
{
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        [$status, $code, $extra] = match (true) {
            $e instanceof BaseException => [$e->getStatusCode(), $e->getErrorCode(), []],
            $e instanceof AuthenticationException => [401, ErrorType::Unauthenticated, []],
            $e instanceof AuthorizationException == 403 => [403, ErrorType::Forbidden, []],
            $e instanceof HttpException && $e->getStatusCode() == 403 => [403, ErrorType::Forbidden, []],
            $e instanceof ModelNotFoundException => [404, ErrorType::NotFound, []],
            $e instanceof NotFoundHttpException => [404, ErrorType::NotFound, []],
            $e instanceof MethodNotAllowedHttpException => [405, ErrorType::MethodNotAllowed, []],
            $e instanceof ValidationException => [422, ErrorType::ValidationError, ['fields' => $e->getMessage()]],
            default => [500, ErrorType::InternalError, []],
        };

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $e->getMessage(),
                ...$extra,
            ],
        ], $status);
    }
}
