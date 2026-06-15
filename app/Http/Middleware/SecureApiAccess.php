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

        if (!hash_equals($validApiKey, $apiKey)) {
            Log::warning('Invalid API Key', [
                'ip' => $clientIp,
                'provided_key' => '***' . substr($apiKey, -8)
            ]);
            return $this->unauthorizedResponse('invalid_api_key', 'Invalid API key');
        }

        // 2. Check IP Whitelist (exact match or CIDR, e.g. 172.18.0.0/16)
        $allowedIps = array_filter(config('app.allowed_ips', []));
        if (!empty($allowedIps) && !$this->isIpAllowed($clientIp, $allowedIps)) {
            Log::warning('IP Not in Whitelist', [
                'client_ip' => $clientIp,
                'allowed_ips' => $allowedIps
            ]);
            return $this->unauthorizedResponse('ip_not_allowed', 'IP address not authorized');
        }

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

    private function isIpAllowed(string $clientIp, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowed) {
            $allowed = trim((string) $allowed);
            if ($allowed === '') {
                continue;
            }
            if ($allowed === $clientIp) {
                return TRUE;
            }
            if (str_contains($allowed, '/') && $this->ipMatchesCidr($clientIp, $allowed)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return FALSE;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === FALSE || $subnetLong === FALSE || $bits < 0 || $bits > 32) {
                return FALSE;
            }
            $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));

            return ($ipLong & $mask) === ($subnetLong & $mask);
        }

        return FALSE;
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

