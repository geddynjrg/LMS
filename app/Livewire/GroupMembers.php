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
        // $this->cacheTtl = config('cache-config.members.ttl', 300); // 5 minutes
        // $this->cachePrefix = config('cache-config.members.prefix', 'constituency_all_members'); // CHANGED

        $this->cacheTtl = config('cache-config.members.ttl');
        $this->cachePrefix = config('cache-config.members.prefix');

        $this->constituency = $constituency;
        $this->getMembers();
    }

    public function hydrate()
    {
        // Reset loading state when component hydrates
        $this->isReady = false;
        $this->loading = true;
    }

    public function dehydrate()
    {
        // Clean up before component dehydrates
        $this->isReady = false;
    }

    private function getMembers(): void
    {
        $this->loading = true;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";

        // Cache tags for better management
        $cacheTags = [
            "constituency:{$this->constituency->constituency_code}",
            "members:all",
            "component:group-members"
        ];

        // Check cache status
        $this->cacheHit = Cache::tags($cacheTags)->has($cacheKey) &&
             Cache::tags($cacheTags)->has($summaryKey);

        // Get or calculate summary data with tags
        $summary = Cache::tags($cacheTags)->remember($summaryKey, $this->cacheTtl, function() {
            return $this->calculateMembersSummary();
        });

        // Get members data - FIXED: Always ensure it's a collection
        $cachedMembers = Cache::tags($cacheTags)->remember($cacheKey, $this->cacheTtl, function() {
            $membersData = $this->loadMembersData();

            if ($membersData instanceof \Illuminate\Support\Collection) {
                return $membersData;
            } elseif (is_array($membersData)) {
                return collect($membersData);
            } else {
                return collect();
            }
        });

        // Store timestamp
        Cache::tags($cacheTags)->put($timestampKey, now()->toDateTimeString(), $this->cacheTtl);
        $this->lastUpdated = Cache::tags($cacheTags)->get($timestampKey);

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

        // Get counts in single queries
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
            Log::error('Error loading members data in GroupMembers:', [
                'error' => $e->getMessage(),
                'constituency' => $this->constituency->constituency_code
            ]);
            return collect(); // Return empty collection on error
        }
    }



    // Get cache status for debugging
    public function getCacheStatus()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $cacheTags = ["constituency:{$this->constituency->constituency_code}", "members:all", "component:group-members"];

        return [
            'cache_key' => $cacheKey,
            'has_cache' => Cache::tags($cacheTags)->has($cacheKey),
            'has_summary_cache' => Cache::tags($cacheTags)->has($summaryKey),
            'last_updated' => Cache::tags($cacheTags)->get($timestampKey),
            'ttl' => $this->cacheTtl,
            'tags' => $cacheTags
        ];
    }

    // #[On('refresh-members')]
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
    //     // Dispatch Livewire event
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
        $this->isReady = false;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $cacheTags = ["constituency:{$this->constituency->constituency_code}", "members:all", "component:group-members"];

        // Clear only this component's cache with tags
        Cache::tags($cacheTags)->forget($cacheKey);
        Cache::tags($cacheTags)->forget($summaryKey);
        Cache::tags($cacheTags)->forget($timestampKey);

        usleep(100000);
        $this->getMembers();

        $this->dispatch('members-refreshed', [
            'message' => 'All members data refreshed successfully',
            'constituency' => $this->constituency->constituency_name
        ]);

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
