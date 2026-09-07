<?php

use App\Exceptions\BusinessRuleException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api/admin')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('routes/adminApi.php'));
        }
    )

    ->withMiddleware(function (Middleware $middleware): void {})

    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (
            BusinessRuleException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->statusCode);
        });
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        });
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        });

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        });

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        });

        $exceptions->render(function (
            \Illuminate\Database\QueryException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        });
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 405);
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\HttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\BadRequestHttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\ConflictHttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409);
        });

        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        });

        $exceptions->render(function (
            \Throwable $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        });
    })

    ->create();
