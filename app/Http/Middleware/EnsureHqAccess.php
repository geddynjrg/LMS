<?php

namespace App\Http\Middleware;

use App\Support\HqAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHqAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! HqAccess::userCanAccessRoutes($request->user())) {
            abort(403, 'You do not have permission to access HQ administration.');
        }

        return $next($request);
    }
}
