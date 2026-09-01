<?php

namespace App\Http\Controllers;

abstract class Controller
{

    /**
     * Success message
     */
    public function successMessage(string $message, array $data, int $code): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
