<?php

namespace App\Services;

use App\Models\County;
use App\Models\Constituency;
use App\Models\Wards;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocationService
{
    /** @var array<string, string> Alternate constituency labels mapped to IEBC names. */
    private const CONSTITUENCY_NAME_ALIASES = [
        'MOUNT ELGON' => 'MT. ELGON',
    ];

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

    public function normalizeConstituencyName(string $name): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        $normalized = preg_replace('/\s+constituency$/i', '', $normalized) ?? $normalized;

        return Str::upper($normalized);
    }

    public function resolveConstituencyByName(string $constituencyName): ?Constituency
    {
        $normalized = $this->normalizeConstituencyName($constituencyName);

        if ($normalized === '') {
            return null;
        }

        $normalized = self::CONSTITUENCY_NAME_ALIASES[$normalized] ?? $normalized;

        $columns = ['constituency_code', 'constituency_name', 'county_code', 'slug'];

        $exact = Constituency::query()
            ->whereRaw('UPPER(constituency_name) = ?', [$normalized])
            ->first($columns);

        if ($exact) {
            return $exact;
        }

        $shortName = preg_replace('/^\d+\s+/', '', $normalized) ?? $normalized;

        return Constituency::query()
            ->where(function ($query) use ($shortName) {
                $query->whereRaw('UPPER(constituency_name) LIKE ?', ['%' . $shortName])
                    ->orWhereRaw('UPPER(TRIM(SUBSTRING_INDEX(constituency_name, " ", -3))) = ?', [$shortName]);
            })
            ->first($columns);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function mapWardsForSelect(?string $constituencyCode): array
    {
        if (empty($constituencyCode)) {
            return [];
        }

        return $this->getWardsByConstituency($constituencyCode)
            ->map(fn ($w) => [
                'id' => $w->id,
                'name' => Str::title(Str::lower($w->ward_name)),
            ])
            ->values()
            ->all();
    }

    public function flushConstituencyLocationCache(?string $constituencyCode): void
    {
        if (empty($constituencyCode)) {
            return;
        }

        Cache::forget("wards_constituency_{$constituencyCode}");
        Cache::forget($this->cachePrefix . 'wards');
        Cache::forget($this->cachePrefix . 'constituencies');
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
