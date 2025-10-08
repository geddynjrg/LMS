<?php

namespace App\Livewire;

use App\Models\Constituency;
use Livewire\Component;
use App\Models\GroupMember;
use App\Models\Member;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\On;

class GroupMembers extends Component
{
    public $members = null;
    public $totalMembers = 0;
    public $male = 0;
    public $female = 0;
    public $disabled = 0;
    public Constituency $constituency;
    public $isReady = false;
    public $cacheHit = false;
    public $lastUpdated = null;
    public $loading = true;

    // Cache configuration
    protected $cacheTtl;
    protected $cachePrefix;

    public function mount(Constituency $constituency)
    {
        $this->cacheTtl = config('cache-config.members.ttl', 300); // 5 minutes
        $this->cachePrefix = config('cache-config.members.prefix', 'constituency_members');

        $this->constituency = $constituency;
        $this->getMembers();
    }

    private function getMembers(): void
    {
        $this->loading = true;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";

        // Check cache status
        $this->cacheHit = Cache::has($cacheKey) && Cache::has($summaryKey);

        // Get or calculate summary data FIRST
        $summary = Cache::remember($summaryKey, $this->cacheTtl, function() {
            return $this->calculateMembersSummary();
        });

        // Get members data - FIXED: Always ensure it's a collection
        $cachedMembers = Cache::remember($cacheKey, $this->cacheTtl, function() {
            $membersData = $this->loadMembersData();

            // Ensure we always return a collection
            if ($membersData instanceof \Illuminate\Support\Collection) {
                return $membersData;
            } elseif (is_array($membersData)) {
                return collect($membersData);
            } else {
                return collect();
            }
        });

        // Store timestamp
        Cache::put($timestampKey, now()->toDateTimeString(), $this->cacheTtl);
        $this->lastUpdated = Cache::get($timestampKey);

        // Assign data - FIXED: Ensure $members is always a collection
        $this->members = $cachedMembers;
        $this->totalMembers = $summary['total'];
        $this->male = $summary['male'];
        $this->female = $summary['female'];
        $this->disabled = $summary['disabled'];

        $this->isReady = true;
        $this->loading = false;
    }
    // private function calculateMembersSummary(): array
    // {
    //     $code = $this->constituency->constituency_code;

    //     // Get distinct member IDs from group members in this constituency
    //     $memberIds = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
    //         $query->where('constituency_code', $code);
    //     })
    //     ->distinct('member_Id')
    //     ->pluck('member_Id');

    //     if ($memberIds->isEmpty()) {
    //         return ['total' => 0, 'male' => 0, 'female' => 0, 'disabled' => 0];
    //     }

    //     // Get counts in single queries (no N+1)
    //     $maleCount = Member::whereIn('member_Id', $memberIds)
    //         ->where('gender', 'Male')
    //         ->count();

    //     $disabledCount = Member::whereIn('member_Id', $memberIds)
    //         ->where('disabled', true)
    //         ->count();

    //     return [
    //         'total' => $memberIds->count(),
    //         'male' => $maleCount,
    //         'female' => $memberIds->count() - $maleCount,
    //         'disabled' => $disabledCount
    //     ];
    // }

    private function calculateMembersSummary(): array
    {
        $code = $this->constituency->constituency_code;

        // Get distinct member IDs from group members in this constituency
        $memberIds = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })
        ->distinct('member_Id')
        ->pluck('member_Id');

        if ($memberIds->isEmpty()) {
            return ['total' => 0, 'male' => 0, 'female' => 0, 'disabled' => 0];
        }

        // Get counts in single queries (no N+1)
        $maleCount = Member::whereIn('member_Id', $memberIds)
            ->where('gender', 'Male')
            ->count();

        $disabledCount = Member::whereIn('member_Id', $memberIds)
            ->where('disabled', true)
            ->count();

        $totalCount = $memberIds->count();

        return [
            'total' => $totalCount,
            'male' => $maleCount,
            'female' => $totalCount - $maleCount,
            'disabled' => $disabledCount
        ];
    }

    // private function loadMembersData()
    // {
    //     $code = $this->constituency->constituency_code;

    //     // Get distinct member IDs first
    //     $memberIds = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
    //         $query->where('constituency_code', $code);
    //     })
    //     ->distinct('member_Id')
    //     ->pluck('member_Id');

    //     if ($memberIds->isEmpty()) {
    //         return collect();
    //     }

    //     // Eager load all members data in one query
    //     return Member::whereIn('member_Id', $memberIds)
    //         ->select(['member_Id', 'name', 'number', 'gender', 'disabled'])
    //         ->orderBy('name')
    //         ->get();
    // }
    private function loadMembersData()
    {
        try {
            $code = $this->constituency->constituency_code;

            // Get distinct member IDs first
            $memberIds = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
                $query->where('constituency_code', $code);
            })
            ->distinct('member_Id')
            ->pluck('member_Id');


            if ($memberIds->isEmpty()) {
                return collect(); // Explicitly return empty collection
            }

            // Eager load all members data in one query
            $members = Member::whereIn('member_Id', $memberIds)
                ->select(['member_Id', 'name', 'number', 'gender', 'disabled'])
                ->orderBy('name')
                ->get();


            return $members;

        } catch (\Exception $e) {
            return collect(); // Return empty collection on error
        }
    }



    // Get cache status for debugging
    public function getCacheStatus()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";

        return [
            'cache_key' => $cacheKey,
            'has_cache' => Cache::has($cacheKey),
            'has_summary_cache' => Cache::has($summaryKey),
            'last_updated' => Cache::get($timestampKey),
            'ttl' => $this->cacheTtl,
        ];
    }
     // Refresh method
    // public function refreshMembers()
    // {
    //     $this->loading = true;
    //     $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
    //     $summaryKey = "{$cacheKey}_summary";
    //     $timestampKey = "{$cacheKey}_timestamp";

    //     Cache::forget($cacheKey);
    //     Cache::forget($summaryKey);
    //     Cache::forget($timestampKey);

    //     $this->getMembers();

    //     $this->dispatch('members-refreshed', [
    //         'message' => 'Members data refreshed successfully',
    //         'constituency' => $this->constituency->constituency_name
    //     ]);

    //     // Add a small delay to ensure DOM is updated
    //     usleep(500000); // 500ms delay
    //     $this->loading = false;
    // }
    #[On('refresh-members')]
    public function refreshMembers()
    {
        $this->loading = true;
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";

        Cache::forget($cacheKey);
        Cache::forget($summaryKey);
        Cache::forget($timestampKey);

        $this->getMembers();
        // Dispatch Livewire event
        $this->dispatch('members-refreshed', [
            'message' => 'Members data refreshed successfully',
            'constituency' => $this->constituency->constituency_name
        ]);
        // Add a small delay to ensure DOM is updated
        usleep(500000); // 500ms delay


        $this->loading = false;
    }

    public function ViewMember($member_Id)
    {
        // Implement member view logic here
        // dd($member_Id);
    }

    public function render(): View
    {
        return view('livewire.group.group-members');
    }
}
