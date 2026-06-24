<?php

namespace App\Support;

use Illuminate\Http\Request;

class ReturnPath
{
    public const COOKIE_NAME = 'uwezo_return_path';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    private const EXCLUDED_PATHS = [
        '/',
        '/login',
        '/logout',
        '/register',
        '/password',
        '/clear',
        '/debug-ip',
    ];

    private const EXCLUDED_PREFIXES = [
        '/livewire',
        '/api',
        '/password/',
        '/sanctum',
        '/storage',
        '/plugins',
        '/images',
        '/css',
        '/js',
    ];

    private const ALLOWED_PREFIXES = [
        '/dashboard',
        '/hq',
        '/secretariat',
        '/accountant',
        '/profile',
    ];

    public static function isValid(?string $path): bool
    {
        if (!$path || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return false;
        }

        $pathname = strtok($path, '?') ?: $path;

        if (in_array($pathname, self::EXCLUDED_PATHS, true)) {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($pathname, $prefix)) {
                return false;
            }
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($pathname === $prefix || str_starts_with($pathname, $prefix.'/')) {
                return true;
            }
        }

        // Constituency routes: /{county}/{constituency}/...
        return (bool) preg_match('#^/[^/]+/[^/]+/.+#', $pathname);
    }

    public static function shouldRemember(Request $request): bool
    {
        if (!$request->isMethod('GET') || $request->ajax()) {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        if (in_array($path, self::EXCLUDED_PATHS, true)) {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
