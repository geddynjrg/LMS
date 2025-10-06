<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class GroupsInfo extends Component
{
    public $totalgroups = 0;
    public $youths = 0;
    public $women = 0;
    public $pwds = 0;
    public $groups = null;
    public Constituency $constituency;
    public $county;

    public function mount(County $county, Constituency $constituency) {
        $this->constituency = $constituency;
        $this->county = $county;
        $this->loadCachedGroups();
    }

    private function loadCachedGroups()
    {
        $cacheKey = "constituency_groups_{$this->constituency->constituency_code}";

        $data = Cache::remember($cacheKey, 600, function() { // Cache for 10 minutes
            return $this->loadGroupsData();
        });

        $this->groups = $data['groups'];
        $this->totalgroups = $data['totalgroups'];
        $this->youths = $data['youths'];
        $this->women = $data['women'];
        $this->pwds = $data['pwds'];
    }

    private function loadGroupsData()
    {
        $const_code = $this->constituency->constituency_code;

        $groups = Group::whereHas('ward.constituency', function ($query) use ($const_code) {
            $query->where('constituency_code', $const_code);
        })
        ->with(['ward:id,ward_name']) // Only select needed columns
        ->select(['id', 'name', 'code', 'gfs_code', 'ward_id', 'identifier', 'category', 'activity', 'slug']) // Only needed columns
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
    }

    // Optional: Refresh method if data changes frequently
    public function refreshGroups()
    {
        Cache::forget("constituency_groups_{$this->constituency->constituency_code}");
        $this->loadCachedGroups();
        $this->dispatch('groups-refreshed');
    }

    public function render()
    {
        return view('livewire.group.groups-info');
    }
}
