<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseException extends Exception
{
    public function __construct(
        string $message = '',
        protected int $statusCode = 500,
        protected string $errorCode = 'INTERNAL ERROR',
        ?\Throwable $previous = null)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->message,
            ],
        ], $this->statusCode);
    }
}
