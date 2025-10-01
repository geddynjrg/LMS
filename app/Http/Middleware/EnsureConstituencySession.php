<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConstituencySession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('constituency')) {
             //check if the user has right to access the constituency
            $codes = auth()->user()->constituencies;
            if(!in_array(session('constituency')->constituency_code, $codes )){
                return redirect()->route('dashboard')->with('error', 'You do not have the rights.');
            }
            return $next($request);
        }

        return redirect()->route('dashboard')->with('error', 'Please select a constituency.');
        
    }
}
