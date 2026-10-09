<?php

namespace App\Exceptions;

use App\Enum\ErrorType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseException extends Exception
{
    public function __construct(
        /**
         * @param  string  $message  message explaining the error
         * @param  int  $statusCode  http status code of the error
         * @param  ErrorType  $errorCode  type of errors name
         */
        string $message = '',
        protected int $statusCode = 500,
        protected ErrorType $errorCode = ErrorType::InternalError,
        ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ErrorType
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
