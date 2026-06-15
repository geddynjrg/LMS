<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Closure;

class ApplicationCacheService
{
    /**
     * Cache application data with multiple tags for flexible clearing
     */
    public function rememberApp(
        string $appId,
        string $dataType,
        Closure $callback,
        ?string $constituencyCode = null,
        ?int $phaseId = null,
        int $minutes = 30
    ) {
        $tags = $this->getAppTags($appId, $constituencyCode, $phaseId);
        $key = $this->getCacheKey($appId, $dataType, $constituencyCode, $phaseId);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    /**
     * Cache with additional custom tags
     */
    public function rememberAppWithCustomTags(
        string $appId,
        string $dataType,
        array $customTags,
        Closure $callback,
        ?string $constituencyCode = null,
        ?int $phaseId = null,
        int $minutes = 30
    ) {
        $tags = array_merge(
            $this->getAppTags($appId, $constituencyCode, $phaseId),
            $customTags
        );

        $key = $this->getCacheKey($appId, $dataType, $constituencyCode, $phaseId);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    /**
     * Clear cache for ONE specific application
     */
    public function forgetApplication(string $appId): void
    {
        $tag = Config::get('cache.tags.application.per_application') . $appId;
        Cache::tags([$tag])->flush();
    }

    /**
     * Clear cache for an entire constituency's applications
     */
    public function forgetConstituencyApps(string $constituencyCode): void
    {
        $tag = Config::get('cache.tags.application.constituency') . $constituencyCode;
        Cache::tags([$tag])->flush();
    }

    /**
     * Clear cache for all applications in a specific phase
     */
    public function forgetPhaseApps(int $phaseId): void
    {
        $tag = Config::get('cache.tags.application.phase') . $phaseId;
        Cache::tags([$tag])->flush();
    }

    /**
     * Clear all phase-related data (Summary & Detailed)
     */
    public function forgetPhaseDetails(int $phaseId): void
    {
        $tags = [
            Config::get('cache.tags.phases.individual_summary') . $phaseId,
            Config::get('cache.tags.phases.individual_detailed') . $phaseId
        ];
        Cache::tags($tags)->flush();
    }

    /**
     * Clear all caches for a specific application including phase data
     */
    public function forgetApplicationComplete(string $appId, ?string $constituencyCode = null, ?int $phaseId = null): void
    {
        $tags = [
            Config::get('cache.tags.application.per_application') . $appId
        ];

        if ($constituencyCode) {
            $tags[] = Config::get('cache.tags.application.constituency') . $constituencyCode;
        }

        if ($phaseId) {
            $tags[] = Config::get('cache.tags.phases.individual_detailed') . $phaseId;
        }

        Cache::tags($tags)->flush();
    }

    /**
     * Check if application data is cached
     */
    public function hasAppData(string $appId, string $dataType, ?string $constituencyCode = null, ?int $phaseId = null): bool
    {
        $tags = $this->getAppTags($appId, $constituencyCode, $phaseId);
        $key = $this->getCacheKey($appId, $dataType, $constituencyCode, $phaseId);

        // Note: Cache::tags()->has() may not be available in all drivers
        // Alternative approach below
        return Cache::tags($tags)->get($key) !== null;
    }

    /**
     * Get multiple application data types at once
     */
    public function rememberAppMultiple(
        string $appId,
        array $dataTypes,
        Closure $callback,
        ?string $constituencyCode = null,
        ?int $phaseId = null,
        int $minutes = 30
    ): array {
        $results = [];

        foreach ($dataTypes as $dataType) {
            $results[$dataType] = $this->rememberApp(
                $appId,
                $dataType,
                function() use ($callback, $dataType) {
                    return $callback($dataType);
                },
                $constituencyCode,
                $phaseId,
                $minutes
            );
        }

        return $results;
    }

    /**
     * Get tags for application caching
     */
    public function getAppTags(string $appId, ?string $constituencyCode = null, ?int $phaseId = null): array
    {
        $tags = [
            Config::get('cache.tags.application.per_application') . $appId
        ];

        if ($constituencyCode) {
            $tags[] = Config::get('cache.tags.application.constituency') . $constituencyCode;
        }

        if ($phaseId) {
            $tags[] = Config::get('cache.tags.application.phase') . $phaseId;
            // Also add phase tag for detailed phase data if needed
            $tags[] = Config::get('cache.tags.phases.individual_detailed') . $phaseId;
        }

        return array_unique($tags);
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $appId, string $dataType, ?string $constituencyCode = null, ?int $phaseId = null): string
    {
        $parts = ['app', $appId, $dataType];

        if ($constituencyCode) {
            $parts[] = 'const_' . $constituencyCode;
        }

        if ($phaseId) {
            $parts[] = 'phase_' . $phaseId;
        }

        return implode('_', $parts);
    }

    /**
     * Clear multiple applications at once
     */
    public function forgetApplications(array $appIds): void
    {
        foreach ($appIds as $appId) {
            $this->forgetApplication($appId);
        }
    }

    /**
     * Clear all application caches (use with caution!)
     */
    public function forgetAllApplications(): void
    {
        Cache::tags(['application'])->flush();
    }

}
