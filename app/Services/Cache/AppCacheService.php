<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Closure;
use Carbon\Carbon;

class AppCacheService
{

    protected function getDuration(?int $minutes = null): int
    {
        return $minutes ?? Config::get('cache.durations.main', 30);
    }

    public function getTags(
        string $tagKey,
        ?string $type = null,
        ?string $constituencyCode = null
    ): array
    {
        $prefix = config("cache.tags.{$tagKey}.{$type}");

        $tags = ["all_{$tagKey}"];

        $tags[] = $prefix;

        if ($constituencyCode) {
            $tags[] = $prefix . $constituencyCode;
        }

        return $tags;
    }

    public function getCacheTags(
        ?string $constituencyCode = null,
        ?string $dataType = null,
        string $config,
        ?int $modelId = null
    ): array {
        $tag = 'cache.tags'.$config;
        $tags = [];

        if ($constituencyCode) {
            $tags[] = Config::get($tag) . $constituencyCode;
        } else {
            $tags[] = Str::before($config, '.').'_national';
        }

        if ($dataType) {
            $tags[] = $dataType;
        }

        if ($modelId) {
            $tags[] = Config::get($tag) . $modelId;
        }

        return array_unique($tags);
    }

    protected function getCacheKey(
        string $dataType,
        ?string $constituencyCode = null,
        ?int $page = null,
        ?int $perPage = null,
        ?string $sortField = null,
        ?string $sortDirection = null,
    ): ?string {

        $parts = [$dataType];

        if ($constituencyCode) {
            $parts[] = 'const_' . $constituencyCode;
        }

        $parts[] = "sort_{$sortField}_{$sortDirection}";

        if ($page) {
            $parts[] = 'p_' . $page;
        }

        if ($perPage){
            $parts[] ='per_' .$perPage;
        }

        return implode('_', $parts);
    }


    public function rememberGroupLoans(
        Closure $callback,
        ?string $constituencyCode = null,
        int $page = 1,
        int $perPage = 15,
        string $sortField = 'disbursement_date',
        string $sortDirection = 'desc',
        int $minutes = 15
    ) {
        $key = $this->getCacheKey('groupLoans', $constituencyCode, $page, $perPage, $sortField, $sortDirection); // groupLoans_const_026_sort_disbursement_date_desc_p_1_per_15

        $tags = $this->getCacheTags($constituencyCode, 'groupLoans', 'group_loans.constituency_loans');
        // array:2 [
        //     0 => "026"
        //     1 => "groupLoans"
        //

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberGroups(
        Closure $callback,
        ?string $constituencyCode = null,
        int $page = 1,
        int $perPage = 15,
        string $sortField = 'disbursement_date',
        string $sortDirection = 'desc',
        int $minutes = 15
    ) {
        $key = $this->getCacheKey('groups', $constituencyCode, $page, $perPage, $sortField, $sortDirection); // groups_const_026_sort_disbursement_date_desc_p_1_per_15

        $tags = $this->getCacheTags($constituencyCode, 'groups_data', 'groups.groups');

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberGroupLoansDashboardStats(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15
    ) {
        $key = $this->getCacheKey('groupLoans_stats', $constituencyCode); // groupLoans_stats_const_026_sort__

        $tags = $this->getCacheTags($constituencyCode, 'groupLoans_stats', 'group_loans.constituency_loans');

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberGroupsDashboardStats(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15
    ) {
        $key = $this->getCacheKey('group_stats', $constituencyCode); // group_stats_const_026_sort__

        $tags = $this->getCacheTags($constituencyCode, 'groups_stats', 'groups.constituency_stats');

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberDashboardStats(
        string $keyPrefix = 'dashboard_stats',
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15
    ) {
        $tagType = $constituencyCode ? 'constituency_stats' : 'national_stats';

        $tags = $this->getTags('dashboard', $tagType, $constituencyCode);

        return Cache::tags($tags)->remember($keyPrefix, now()->addMinutes($minutes), $callback);
    }

    public function rememberApplications(
        string $keyPrefix = 'applications',
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15
    ) {
        $tagType = $constituencyCode ? 'constituency_apps' : 'national_apps';

        $tags = $this->getTags('applications', $tagType, $constituencyCode);

        return Cache::tags($tags)->remember($keyPrefix, now()->addMinutes($minutes), $callback);
    }

    public function rememberApplicationsStats(
        string $keyPrefix = 'applications',
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15
    ) {
        $tagType = $constituencyCode ? 'constituency_stats' : 'national_stats';

        $tags = $this->getTags('applications', $tagType, $constituencyCode);

        return Cache::tags($tags)->remember($keyPrefix, now()->addMinutes($minutes), $callback);
    }

    public function rememberReceipts(
        ?string $key = 'receipts',
        ?string $tagType = null,
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 10
    ) {
        $tags = $this->getTags('receipts', $tagType, $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function flushGroupLoansCache(?string $constituencyCode = null): void
    {
        if ($constituencyCode) {
            $tags = [Config::get('cache.tags.group_loans.constituency_loans') . $constituencyCode];
        } else {
            $tags = ['group_loans_national'];
        }

        $groupLoansType = [
            'groupLoans',
            'groupLoans_stats',
        ];

        foreach ($groupLoansType as $type) {
            $tags[] = "{$type}_const_{$constituencyCode}";
        }

        Cache::tags(array_unique($tags))->flush();
    }

    public function flushGroupsCache(?string $constituencyCode = null): void
    {
        if ($constituencyCode) {
            $tags = [Config::get('cache.tags.groups.constituency_stats') . $constituencyCode];
        } else {
            $tags = ['group_national'];
        }

        $groupType = [
            'groups_stats',
            'groups_data',
        ];

        foreach ($groupType as $type) {
            $tags[] = "{$type}_const_{$constituencyCode}";
        }

        Cache::tags(array_unique($tags))->flush();
    }

    public function flushDashboardCache(
        string $tagKey,
        ?string $tagType = null,
        ?string $constituencyCode = null
    ): void
    {

        $tags = $this->getDashboardTags('dashboard', $tagType, $constituencyCode);

        dd($tags);

        Cache::tags($tags)->flush();
    }

    public function flashCache(
        string $tagKey,
        ?string $tagType = null,
        ?string $constituencyCode = null
    ): void
    {
        // Clear everything under key 'dashboard'
        if (!$tagType) {
            Cache::tags(["all_{$tagKey}"])->flush();
            return;
        }

        // clear National HQ / all Constituencies
        if ($tagType && !$constituencyCode) {
            $prefix = config("cache.tags.{$tagKey}.{$tagType}");
            Cache::tags([$prefix])->flush();
            return;
        }

        // clear specific Constituency '005'
        if ($tagType && $constituencyCode) {
            $prefix = config("cache.tags.{$tagKey}.{$tagType}");
            Cache::tags([$prefix . $constituencyCode])->flush();
        }
    }

}
