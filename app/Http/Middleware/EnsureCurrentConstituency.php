<?php

namespace App\Http\Middleware;

use App\Models\Constituency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentConstituency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    /**
     * Ensure the user has a valid current constituency
     * and is authorised to access it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Has the user picked a constituency?
        $code = $user->current_constituency_code;
        if (!$code) {
            $assignedCodes = $user->constituencies()->pluck('constituencies.constituency_code');

            if ($assignedCodes->count() === 1) {
                $code = $assignedCodes->first();
                $user->update(['current_constituency_code' => $code]);
            } else {
                return redirect()->route('dashboard')
                    ->with('error', 'Please select your active constituency first.');
            }
        }

        // 2. Get the slug from the URL and resolve the Constituency
        $requestedConstituency = $request->route('constituency');
        if (is_string($requestedConstituency)) {
            $requestedConstituency = Constituency::where('slug', $requestedConstituency)->first();
        }

        if (!$requestedConstituency instanceof Constituency) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid constituency in URL.');
        }

        // 3. Is the constituency in the URL the same as the user's selected one?
        if ($requestedConstituency->constituency_code !== $code) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not authorised for this constituency.');
        }

        // 4. Is the user assigned to this constituency at all?
        $allowed = $user->constituencies()
            ->wherePivot('constituency_code', $code)
            ->exists();

        if (!$allowed) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not authorised for this constituency.');
        }

        // 5. Attach the model for controller/view use
        $request->attributes->set('activeConstituency', $requestedConstituency);

        return $next($request);
    }

}
