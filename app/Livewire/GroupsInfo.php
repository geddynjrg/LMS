<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GroupsInfo extends Component
{
    public $totalgroups = 0;
    public $youths = 0;
    public $women = 0;
    public $pwds = 0;
    public $groups = null;
    public Constituency $constituency;
    public $county;
    public $cacheHit = false;
    public $lastUpdated = null;

    // Cache configuration from config file
    protected $cacheTtl;
    protected $cachePrefix;

    public function mount(County $county, Constituency $constituency)
    {
        $this->cacheTtl = config('cache-config.groups.ttl', 600);
        $this->cachePrefix = config('cache-config.groups.prefix', 'constituency_groups');

        $this->constituency = $constituency;
        $this->county = $county;
        $this->loadCachedGroups();
    }

    // private function loadCachedGroups()
    // {
    //     $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
    //     $timestampKey = "{$cacheKey}_timestamp";

    //     // Check if cache exists and track hit/miss
    //     if (Cache::has($cacheKey)) {
    //         $this->cacheHit = true;
    //         Log::info("Groups cache HIT for constituency: {$this->constituency->constituency_code}");
    //     } else {
    //         $this->cacheHit = false;
    //         Log::info("Groups cache MISS for constituency: {$this->constituency->constituency_code}");
    //     }

    //     $data = Cache::remember($cacheKey, $this->cacheTtl, function() {
    //         return $this->loadGroupsData();
    //     });

    //     // Store cache timestamp
    //     Cache::remember($timestampKey, $this->cacheTtl, function() {
    //         return now()->toDateTimeString();
    //     });

    //     $this->groups = $data['groups'];
    //     $this->totalgroups = $data['totalgroups'];
    //     $this->youths = $data['youths'];
    //     $this->women = $data['women'];
    //     $this->pwds = $data['pwds'];
    //     $this->lastUpdated = Cache::get($timestampKey);
    // }
        private function loadCachedGroups()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $timestampKey = "{$cacheKey}_timestamp";

        // Check cache status
        $this->cacheHit = Cache::has($cacheKey);

        if ($this->cacheHit) {
            Log::info("Groups cache HIT", [
                'constituency' => $this->constituency->constituency_code,
                'key' => $cacheKey,
                'ttl' => $this->cacheTtl
            ]);
        } else {
            Log::info("Groups cache MISS", [
                'constituency' => $this->constituency->constituency_code,
                'key' => $cacheKey
            ]);
        }

        $data = Cache::remember($cacheKey, $this->cacheTtl, function() {
            return $this->loadGroupsData();
        });

        // Store timestamp
        Cache::put($timestampKey, now()->toDateTimeString(), $this->cacheTtl);
        $this->lastUpdated = Cache::get($timestampKey);

        $this->groups = $data['groups'];
        $this->totalgroups = $data['totalgroups'];
        $this->youths = $data['youths'];
        $this->women = $data['women'];
        $this->pwds = $data['pwds'];
    }

    private function loadGroupsData()
    {
        try {
            $const_code = $this->constituency->constituency_code;

            $groups = Group::whereHas('ward.constituency', function ($query) use ($const_code) {
                $query->where('constituency_code', $const_code);
            })
            ->with(['ward:id,ward_name'])
            ->select(['id', 'name', 'code', 'gfs_code', 'ward_id', 'identifier', 'category', 'activity', 'slug'])
            ->get();

            $totalgroups = $groups->count();
            $youths = $groups->where('category', 'Youth')->count();
            $women = $groups->where('category', 'Women')->count();
            $pwds = $groups->where('category', 'PWD')->count();

            return [
                'groups' => $groups,
                'totalgroups' => $totalgroups,
                'youths' => $youths,
                'women' => $women,
                'pwds' => $pwds,
            ];

        } catch (\Exception $e) {
            Log::error("Error loading groups data for constituency {$this->constituency->constituency_code}: " . $e->getMessage());

            return [
                'groups' => collect(),
                'totalgroups' => 0,
                'youths' => 0,
                'women' => 0,
                'pwds' => 0,
            ];
        }
    }

    // Refresh method with better feedback
    public function refreshGroups()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $timestampKey = "{$cacheKey}_timestamp";

        Cache::forget($cacheKey);
        Cache::forget($timestampKey);

        $this->loadCachedGroups();

        $this->dispatch('groups-refreshed', [
            'message' => 'Groups data refreshed successfully',
            'constituency' => $this->constituency->constituency_name
        ]);
    }

    // Get cache status for debugging
    public function getCacheStatus()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $timestampKey = "{$cacheKey}_timestamp";

        return [
            'cache_key' => $cacheKey,
            'has_cache' => Cache::has($cacheKey),
            'last_updated' => Cache::get($timestampKey),
            'ttl' => $this->cacheTtl,
        ];
    }

    public function render()
    {
        return view('livewire.group.groups-info');
    }
}
