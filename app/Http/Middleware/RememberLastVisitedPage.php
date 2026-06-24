<?php

namespace App\Http\Middleware;

use App\Support\ReturnPath;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RememberLastVisitedPage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!Auth::check() || !ReturnPath::shouldRemember($request)) {
            return $response;
        }

        $path = $request->getRequestUri();

        if (!ReturnPath::isValid($path)) {
            return $response;
        }

        return $response->withCookie(cookie(
            ReturnPath::COOKIE_NAME,
            $path,
            ReturnPath::COOKIE_MINUTES,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'lax',
        ));
    }
}
