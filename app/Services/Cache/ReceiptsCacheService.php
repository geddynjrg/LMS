<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Closure;
use Carbon\Carbon;

class ReceiptsCacheService
{
    protected function getDuration(?int $minutes = null): int
    {
        return $minutes ?? Config::get('cache.durations.receipts', 30);
    }

    public function getReceiptsTags(
        ?string $constituencyCode = null,
        ?string $dateRange = null,
        ?int $groupId = null
    ): array {
        $tags = [];

        if ($constituencyCode) {
            $tags[] = Config::get('cache.tags.receipts.constituency_stats') . $constituencyCode;
        } else {
            $tags[] = 'receipts_national';
        }

        if ($dateRange) {
            $tags[] = 'receipts_date_range_' . $dateRange;
        }

        if ($groupId) {
            $tags[] = Config::get('cache.tags.groups.group') . $groupId;
        }

        return array_unique($tags);
    }

    protected function getCacheKey(
        string $dataType,
        ?string $constituencyCode = null,
        ?int $page = null,
        ?int $perPage = null,
        string $sortField = 'transaction_date',
        string $sortDirection = 'desc'
    ): ?string {

        $parts = ['receipts', $dataType];

        if ($constituencyCode) {
            $parts[] = 'const_' . $constituencyCode;
        }

        // Include sorting in the key for the "default" views
        $parts[] = "sort_{$sortField}_{$sortDirection}";

        if ($page) {
            $parts[] = 'p_' . $page;
        }

        if ($perPage){
            $parts[] ='per_' .$perPage;
        }

        return implode('_', $parts);
    }

    public function rememberDashboardStats(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 30
    ) {
        $tags = $this->getReceiptsTags($constituencyCode);
        $key = $this->getCacheKey('dashboard_stats', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberRecentReceipts(
        Closure $callback,
        ?string $constituencyCode = null,
        int $page = 1,
        int $perPage = 15,
        string $sortField = 'transaction_date',
        string $sortDirection = 'desc',
        int $minutes = 15
    ) {
        $key = $this->getCacheKey('recent', $constituencyCode, $page, $perPage, $sortField, $sortDirection);

        $tags = $this->getReceiptsTags($constituencyCode, 'recent');

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function remeberRepaymentGroups(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15
    ) {
        $key = 'repayment_groups' . $constituencyCode;

        $tags = $this->getReceiptsTags($constituencyCode, 'groups');

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes),  $callback);
    }

    public function rememberTodayCollection(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 15  // Shorter duration for daily data
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'today');
        $key = $this->getCacheKey('today', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberWeeklyCollection(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 60
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'weekly');
        $key = $this->getCacheKey('weekly', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberMonthlyCollection(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 120
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'monthly');
        $key = $this->getCacheKey('monthly', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberTotalCollection(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 240
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'total');
        $key = $this->getCacheKey('total', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberTrends(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 60
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'trends');
        $key = $this->getCacheKey('trends', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberTopGroups(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 120
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'top_groups');
        $key = $this->getCacheKey('top_groups', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberPaymentMethods(
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 60
    ) {
        $tags = $this->getReceiptsTags($constituencyCode, 'payment_methods');
        $key = $this->getCacheKey('payment_methods', $constituencyCode);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberDateRangeSummary(
        string $startDate,
        string $endDate,
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 30
    ) {
        $dateRange = $startDate . '_to_' . $endDate;
        $tags = $this->getReceiptsTags($constituencyCode, $dateRange);
        $key = $this->getCacheKey('date_range', $constituencyCode, $dateRange);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function rememberDailyBreakdown(
        string $startDate,
        string $endDate,
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 30
    ) {
        $dateRange = $startDate . '_to_' . $endDate;
        $tags = $this->getReceiptsTags($constituencyCode, 'daily_' . $dateRange);
        $key = $this->getCacheKey('daily_breakdown', $constituencyCode, $dateRange);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), $callback);
    }

    public function forgetConstituencyReceipts(string $constituencyCode): void
    {
        $tag = Config::get('cache.tags.receipts.constituency_stats') . $constituencyCode;
        Cache::tags([$tag])->flush();
    }

    public function forgetDateRange(string $startDate, string $endDate, ?string $constituencyCode = null): void
    {
        $tags = [];

        if ($constituencyCode) {
            $tags[] = Config::get('cache.tags.receipts.constituency_stats') . $constituencyCode;
        }

        $dateRange = $startDate . '_to_' . $endDate;
        $tags[] = 'receipts_date_range_' . $dateRange;

        Cache::tags($tags)->flush();
    }

    public function forgetAllReceipts(): void
    {
        Cache::tags(['receipts_national'])->flush();
    }

    public function forgetReceiptType(string $type, ?string $constituencyCode = null): void
    {
        $tags = [];

        if ($constituencyCode) {
            $tags[] = Config::get('cache.tags.receipts.constituency_stats') . $constituencyCode;
        }

        switch ($type) {
            case 'today':
                $tags[] = 'receipts_date_range_today';
                break;
            case 'weekly':
                $tags[] = 'receipts_date_range_weekly';
                break;
            case 'monthly':
                $tags[] = 'receipts_date_range_monthly';
                break;
            case 'trends':
                $tags[] = 'receipts_trends';
                break;
            case 'top_groups':
                $tags[] = 'receipts_top_groups';
                break;
            case 'payment_methods':
                $tags[] = 'receipts_payment_methods';
                break;
        }

        if (!empty($tags)) {
            Cache::tags($tags)->flush();
        }
    }

    public function onReceiptCreated(?string $constituencyCode = null, ?int $groupId = null): void
    {
        $tags = ['receipts_national', 'receipts_date_range_today'];

        if ($constituencyCode) {
            $tags[] = Config::get('cache.tags.receipts.constituency_stats') . $constituencyCode;
        }

        if ($groupId) {
            $tags[] = Config::get('cache.tags.groups.group') . $groupId;
        }

        Cache::tags($tags)->flush();
    }

    public function rememberMultipleStats(
        array $statTypes,
        Closure $callback,
        ?string $constituencyCode = null,
        int $minutes = 30
    ): array {
        $results = [];

        foreach ($statTypes as $statType) {
            $method = 'remember' . ucfirst($statType);
            if (method_exists($this, $method)) {
                $results[$statType] = $this->$method(
                    function() use ($callback, $statType) {
                        return $callback($statType);
                    },
                    $constituencyCode,
                    $minutes
                );
            }
        }

        return $results;
    }

    public function flushConstituencyReceiptsCache(?string $constituencyCode = null): void
    {
        if ($constituencyCode) {
            $tags = [Config::get('cache.tags.receipts.constituency_stats') . $constituencyCode,];
        } else {
            $tags = ['receipts_national'];
        }

        $receiptTypes = [
            'recent',
            'trends',
            'top_groups',
            'payment_methods',
            'dashboard_stats',
            'today',
            'weekly',
            'monthly',
            'total',
            'groups',
        ];

        foreach ($receiptTypes as $type) {
            $tags[] = "receipts_{$type}_const_{$constituencyCode}";
        }

        Cache::tags(array_unique($tags))->flush();
    }

    // public function flushAllConstituenciesReceiptsCache(array $constituencyCodes): void
    // {
    //     foreach ($constituencyCodes as $code) {
    //         $this->flushConstituencyReceiptsCache($code);
    //     }

    //     $this->flushNationalReceiptsCache();

    //     Cache::forget('all_constituency_codes');
    // }
}
