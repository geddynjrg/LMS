<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SecureApiAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();
        $userAgent = $request->userAgent();
        $apiKey = $request->header('X-API-Key');
        $validApiKey = config('app.lms_api_key');

        Log::info('Secure API Access Check', [
            'ip' => $clientIp,
            'user_agent' => $userAgent,
            'endpoint' => $request->path(),
            'method' => $request->method()
        ]);

        // 1. Check API Key
        if (empty($apiKey)) {
            Log::warning('Missing API Key', ['ip' => $clientIp]);
            return $this->unauthorizedResponse('missing_api_key', 'API key is required');
        }

        if ($apiKey !== $validApiKey) {
            Log::warning('Invalid API Key', [
                'ip' => $clientIp,
                'provided_key' => '***' . substr($apiKey, -8)
            ]);
            return $this->unauthorizedResponse('invalid_api_key', 'Invalid API key');
        }

        // 2. Check IP Whitelist
        $allowedIps = config('app.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($clientIp, $allowedIps)) {
            Log::warning('IP Not in Whitelist', [
                'client_ip' => $clientIp,
                'allowed_ips' => $allowedIps
            ]);
            return $this->unauthorizedResponse('ip_not_allowed', 'IP address not authorized');
        }

        // 3. Check User Agent (recommended)
        // $allowedUserAgents = config('app.allowed_user_agents', []);
        // if (!empty($allowedUserAgents) && !$this->isAllowedUserAgent($userAgent, $allowedUserAgents)) {
        //     Log::warning('User Agent Not Allowed', ['user_agent' => $userAgent]);
        //     return $this->unauthorizedResponse('user_agent_not_allowed', 'User agent not allowed');
        // }

        // 4. Rate Limiting
        if ($this->isRateLimited($request)) {
            Log::warning('Rate Limit Exceeded', ['ip' => $clientIp]);
            return $this->unauthorizedResponse('rate_limited', 'Too many requests');
        }

        Log::info('Secure API Access - All checks passed', [
            'ip' => $clientIp,
            'endpoint' => $request->path()
        ]);

        return $next($request);
    }

    private function isAllowedUserAgent($userAgent, $allowedPatterns): bool
    {
        foreach ($allowedPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function isRateLimited(Request $request): bool
    {
        $key = 'api_rate_limit:' . $request->ip();
        $limit = 100; // requests per minute
        $window = 60; // seconds

        $current = Cache::get($key, 0);
        if ($current >= $limit) {
            return true;
        }

        Cache::put($key, $current + 1, $window);
        return false;
    }

    private function unauthorizedResponse($error, $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message
        ], 401);
    }
}

