<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function success($data, $message, $statusCode = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
