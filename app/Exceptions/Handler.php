<?php

namespace App\Exceptions;

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
            $e instanceof AuthenticationException => [401, 'UNAUTHENTICATED', []],
            $e instanceof AuthorizationException == 403 => [403, 'FORBIDDEN', []],
            $e instanceof HttpException && $e->getStatusCode() == 403 => [403, 'FORBIDDEN', []],
            $e instanceof ModelNotFoundException => [404, 'NOT_FOUND', []],
            $e instanceof NotFoundHttpException => [404, 'NOT_FOUND', []],
            $e instanceof MethodNotAllowedHttpException => [405, 'METHOD_NOT_ALLOWED', []],
            $e instanceof ValidationException => [422, 'VALIDATION_ERROR', ['fields' => $e->getMessage()]],
            default => [500, 'INTERNAL_ERROR', []],
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
