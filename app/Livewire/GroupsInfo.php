<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

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
    public $loading = true;

    // Cache configuration from config file
    protected $cacheTtl;
    protected $cachePrefix;

    public function mount(County $county, Constituency $constituency)
    {
        $this->cacheTtl = config('cache-config.groups.ttl');
        $this->cachePrefix = config('cache-config.groups.prefix');

        $this->constituency = $constituency;
        $this->county = $county;
        $this->loadCachedGroups();
    }

    public function hydrate()
    {
        $this->loading = true;
    }

    private function loadCachedGroups()
    {
        $this->loading = true;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";

        // Cache tags for better management
        $cacheTags = [
            "constituency:{$this->constituency->constituency_code}",
            "groups:all",
            "component:groups-info"
        ];

        // Check cache status with tags
        $this->cacheHit = Cache::tags($cacheTags)->has($cacheKey) &&
                         Cache::tags($cacheTags)->has($summaryKey);

        if ($this->cacheHit) {
            Log::info("Groups cache HIT", [
                'constituency' => $this->constituency->constituency_code,
                'key' => $cacheKey,
                'ttl' => $this->cacheTtl,
                'tags' => $cacheTags
            ]);
        } else {
            Log::info("Groups cache MISS", [
                'constituency' => $this->constituency->constituency_code,
                'key' => $cacheKey,
                'tags' => $cacheTags
            ]);
        }

        // Get groups data with tags
        $cachedGroups = Cache::tags($cacheTags)->remember($cacheKey, $this->cacheTtl, function() {
            return $this->loadGroupsData();
        });

        // Get summary data with tags
        $summary = Cache::tags($cacheTags)->remember($summaryKey, $this->cacheTtl, function() {
            return $this->calculateGroupsSummary();
        });

        // Store timestamp with tags
        Cache::tags($cacheTags)->put($timestampKey, now()->toDateTimeString(), $this->cacheTtl);
        $this->lastUpdated = Cache::tags($cacheTags)->get($timestampKey);

        // Assign data
        $this->groups = $cachedGroups;
        $this->totalgroups = $summary['totalgroups'];
        $this->youths = $summary['youths'];
        $this->women = $summary['women'];
        $this->pwds = $summary['pwds'];

        $this->loading = false;
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

            return $groups;

        } catch (\Exception $e) {
            Log::error("Error loading groups data for constituency {$this->constituency->constituency_code}: " . $e->getMessage());
            return collect();
        }
    }

    private function calculateGroupsSummary()
    {
        try {
            $const_code = $this->constituency->constituency_code;

            $groups = Group::whereHas('ward.constituency', function ($query) use ($const_code) {
                $query->where('constituency_code', $const_code);
            })->get();

            $totalgroups = $groups->count();
            $youths = $groups->where('category', 'Youth')->count();
            $women = $groups->where('category', 'Women')->count();
            $pwds = $groups->where('category', 'PWD')->count();

            return [
                'totalgroups' => $totalgroups,
                'youths' => $youths,
                'women' => $women,
                'pwds' => $pwds,
            ];

        } catch (\Exception $e) {
            Log::error("Error calculating groups summary for constituency {$this->constituency->constituency_code}: " . $e->getMessage());

            return [
                'totalgroups' => 0,
                'youths' => 0,
                'women' => 0,
                'pwds' => 0,
            ];
        }
    }

    #[On('refresh-groups')]
    public function refreshGroups()
    {
        $this->loading = true;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";

        // Cache tags for clearing
        $cacheTags = [
            "constituency:{$this->constituency->constituency_code}",
            "groups:all",
            "component:groups-info"
        ];

        // Clear cache with tags
        Cache::tags($cacheTags)->forget($cacheKey);
        Cache::tags($cacheTags)->forget($summaryKey);
        Cache::tags($cacheTags)->forget($timestampKey);

        // Small delay to ensure cache is cleared
        usleep(100000);

        $this->loadCachedGroups();

        $this->dispatch('groups-refreshed', [
            'message' => 'Groups data refreshed successfully',
            'constituency' => $this->constituency->constituency_name
        ]);

        $this->loading = false;
    }

    // Get cache status for debugging
    public function getCacheStatus()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $cacheTags = [
            "constituency:{$this->constituency->constituency_code}",
            "groups:all",
            "component:groups-info"
        ];

        return [
            'cache_key' => $cacheKey,
            'has_cache' => Cache::tags($cacheTags)->has($cacheKey),
            'has_summary_cache' => Cache::tags($cacheTags)->has($summaryKey),
            'last_updated' => Cache::tags($cacheTags)->get($timestampKey),
            'ttl' => $this->cacheTtl,
            'tags' => $cacheTags,
            'component' => 'groups-info'
        ];
    }

    public function render()
    {
        return view('livewire.group.groups-info');
    }
}
