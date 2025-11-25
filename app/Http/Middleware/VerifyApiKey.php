<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $validApiKey = config('app.lms_api_key');

        // Log the incoming request for debugging
        Log::info('API Key Validation Attempt', [
            'ip' => $request->ip(),
            'url' => $request->url(),
            'has_api_key' => !empty($apiKey),
            'expected_key' => $validApiKey ? '***' . substr($validApiKey, -8) : 'null'
        ]);

        // Check if API key is provided
        if (empty($apiKey)) {
            Log::warning('Missing API Key', [
                'ip' => $request->ip(),
                'url' => $request->url()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'missing_api_key',
                'message' => 'API key is required'
            ], 401);
        }

        // Validate API key
        if ($apiKey !== $validApiKey) {
            Log::warning('Invalid API Key', [
                'ip' => $request->ip(),
                'provided_key' => '***' . substr($apiKey, -8),
                'expected_key' => '***' . substr($validApiKey, -8)
            ]);

            return response()->json([
                'success' => false,
                'error' => 'invalid_api_key_requested',
                'message' => 'Invalid API key'
            ], 401);
        }

        Log::info('API Key Validation Successful', [
            'ip' => $request->ip(),
            'url' => $request->url()
        ]);

        return $next($request);
    }
}
