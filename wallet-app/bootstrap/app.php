<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Exceptions\WalletAppException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(ApiResponseMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                if ($e instanceof WalletAppException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'error' => $e->getErrorCode(),
                        'data' => []
                    ], $e->getStatusCode());
                }

                if ($e instanceof AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'You are not authorized to access this resource',
                        'error' => 'authorization_failed',
                        'data' => []
                    ], Response::HTTP_FORBIDDEN);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found',
                        'error' => 'not_found',
                        'data' => []
                    ], Response::HTTP_NOT_FOUND);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'Unauthenticated',
                        'error' => 'authentication_failed',
                        'data' => []
                    ], Response::HTTP_UNAUTHORIZED);
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'error' => 'validation_failed',
                        'errors' => $e->errors(),
                        'data' => []
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                if ($e instanceof \TypeError) {
                    $message = $e->getMessage();

                    if (preg_match('/Argument #(\d+) \(\$([^)]+)\) must be of type ([^,]+)/', $message, $matches)) {
                        $argName = $matches[2];
                        $expectedType = $matches[3];
                        $sanitizedMessage = "Invalid argument type for parameter '$argName'. Expected $expectedType.";
                    } else {
                        $sanitizedMessage = "A type validation error occurred.";
                    }

                    return response()->json([
                        'success' => false,
                        'message' => $sanitizedMessage,
                        'error' => 'invalid_type',
                        'data' => []
                    ], Response::HTTP_BAD_REQUEST);
                }

                $statusCode = method_exists($e, 'getStatusCode') ?
                    $e->getStatusCode() : Response::HTTP_BAD_REQUEST;

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred',
                    'error' => strtolower(class_basename($e)),
                    'data' => []
                ], $statusCode);
            }
        });
    })->create();
