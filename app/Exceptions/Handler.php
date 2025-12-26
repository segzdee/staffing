<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'key_secret',
        'webhook_secret',
        'ccbill_salt',
        'api_key',
        'secret',
        'token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions with context
            if ($this->shouldReport($e)) {
                Log::error('Exception occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     */
    public function render($request, Throwable $e): Response
    {
        // API requests: return JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($request, $e);
        }

        // Web requests: use default Laravel rendering
        return parent::render($request, $e);
    }

    /**
     * Render API exceptions as JSON.
     */
    protected function renderApiException(Request $request, Throwable $e): \Illuminate\Http\JsonResponse
    {
        // Validation exceptions
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        // HTTP exceptions (404, 403, etc.)
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: $this->getDefaultMessage($statusCode);

            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => $this->getErrorCode($statusCode),
            ], $statusCode);
        }

        // Database/Query exceptions
        if ($e instanceof \Illuminate\Database\QueryException) {
            Log::error('Database query exception', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'A database error occurred',
                'error_code' => 'DATABASE_ERROR',
            ], 500);
        }

        // Authentication exceptions
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Authorization exceptions
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized',
                'error_code' => 'UNAUTHORIZED',
            ], 403);
        }

        // Model not found
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        // Generic exception
        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            'error_code' => 'INTERNAL_ERROR',
            'file' => config('app.debug') ? $e->getFile() : null,
            'line' => config('app.debug') ? $e->getLine() : null,
        ], 500);
    }

    /**
     * Get default message for HTTP status code.
     */
    protected function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad request',
            401 => 'Unauthenticated',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Method not allowed',
            422 => 'Validation failed',
            429 => 'Too many requests',
            500 => 'Internal server error',
            503 => 'Service unavailable',
            default => 'An error occurred',
        };
    }

    /**
     * Get error code for HTTP status code.
     */
    protected function getErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
    }
}
