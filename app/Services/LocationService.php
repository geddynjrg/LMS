<?php

namespace App\Services;

use App\Models\County;
use App\Models\Constituency;
use App\Models\Wards;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class LocationService
{
    protected int $cacheTtl = 3600; // 1 hour - same as your existing filters
    protected string $cachePrefix = 'location_';
    protected int $cacheMonthTtl = 2592000; // 30 days in seconds

    /**
     * Get all counties
     */
    public function getAllCounties(): Collection
    {
        $cacheKey = $this->cachePrefix . 'counties';

        return Cache::remember($cacheKey, $this->cacheMonthTtl, function () {
            return County::orderBy('county_name')
                ->select('county_code', 'county_name')
                ->get();
        });
    }

    /**
     * Get all constituencies
     */
    public function getAllConstituencies(): Collection
    {
        $cacheKey = $this->cachePrefix . 'constituencies';

        return Cache::remember($cacheKey, $this->cacheMonthTtl, function () {
            return Constituency::orderBy('constituency_name')
                ->select('constituency_code', 'constituency_name', 'slug', 'county_code')
                ->get();
        });
    }

    /**
     * Get all wards
     */
    public function getAllWards(): Collection
    {
        $cacheKey = $this->cachePrefix . 'wards';

        return Cache::remember($cacheKey, $this->cacheMonthTtl, function () {
            return Wards::orderBy('ward_name')
                ->select('id', 'constituency_code', 'ward_name')
                ->get();
        });
    }

    /**
     * Get constituencies for a specific county
     */
    public function getConstituenciesByCounty(?string $countyCode): Collection
    {
        if (empty($countyCode)) {
            return collect();
        }

        $cacheKey = "constituencies_county_{$countyCode}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($countyCode) {
            return Constituency::where('county_code', $countyCode)
                ->orderBy('constituency_name')
                ->select('constituency_code', 'constituency_name')
                ->get();
        });
    }

    /**
     * Get wards for a specific constituency
     */
    public function getWardsByConstituency(?string $constituencyCode): Collection
    {
        if (empty($constituencyCode)) {
            return collect();
        }

        $cacheKey = "wards_constituency_{$constituencyCode}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($constituencyCode) {
            return Wards::where('constituency_code', $constituencyCode)
                ->orderBy('ward_name')
                ->select('id', 'ward_name')
                ->get();
        });
    }

    /**
     * Get full hierarchy for a county (constituencies + their wards)
     */
    public function getFullHierarchyForCounty(string $countyCode): Collection
    {
        $constituencies = $this->getConstituenciesByCounty($countyCode);

        return $constituencies->map(function ($constituency) {
            $constituency->wards = $this->getWardsByConstituency($constituency->constituency_code);
            return $constituency;
        });
    }
}
