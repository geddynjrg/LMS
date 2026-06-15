<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Mailer\Exception\TransportException;
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

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'trace' => config('app.debug') ? $exception->getTrace() : [],
            ], 500);
        }

        return parent::render($request, $exception);
    }

    /**
     * Register the exception handling callbacks for the application.
    */
    public function register(): void
    {
        $this->renderable(function (TransportException $e, $request) {
            return back()->with('error', 'Mail server connection timeout. Action completed, but email not sent.');
        });
    }
}
