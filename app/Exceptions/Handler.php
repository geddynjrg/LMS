<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Force JSON for anything starting with /api
        if ($request->is('api/*')) {
            return response()->json([
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                // Only show trace if APP_DEBUG is true
                'trace' => config('app.debug') ? $exception->getTrace() : [],
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
