<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency;
use App\Models\GroupMember;
use App\Models\Member;
use App\Models\GroupLoan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;

class MultiGroupMembers extends Component
{
    public $members = null;
    public $riskStats = [
        'high_risk' => 0,
        'medium_risk' => 0,
        'low_risk' => 0,
        'total_multi_group' => 0
    ];
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
        $this->getMultiGroupMembers();
    }

    private function getMultiGroupMembers(): void
    {
        $this->loading = true;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $riskStatsKey = "{$cacheKey}_risk_stats";

        // Check cache status
        $this->cacheHit = Cache::has($cacheKey) && Cache::has($summaryKey);

        // Get or calculate data
        $summary = Cache::remember($summaryKey, $this->cacheTtl, function() {
            return $this->calculateMembersSummary();
        });

        $cachedMembers = Cache::remember($cacheKey, $this->cacheTtl, function() {
            return $this->loadMembersData();
        });

        $cachedRiskStats = Cache::remember($riskStatsKey, $this->cacheTtl, function() {
            return $this->calculateRiskStats();
        });

        // Store timestamp
        Cache::put($timestampKey, now()->toDateTimeString(), $this->cacheTtl);
        $this->lastUpdated = Cache::get($timestampKey);

        // Assign data
        $this->members = $cachedMembers;
        $this->riskStats = $cachedRiskStats;

        $this->isReady = true;
        $this->loading = false;
    }

    private function calculateMembersSummary(): array
    {
        $code = $this->constituency->constituency_code;

        // Get distinct member IDs from group members in this constituency using Eloquent
        $memberIds = GroupMember::whereHas('groupLoan.loanPhase.constituency', function ($query) use ($code) {
            $query->where('constituency_code', $code);
        })
        ->distinct()
        ->pluck('member_Id');

        if ($memberIds->isEmpty()) {
            return ['total' => 0, 'male' => 0, 'female' => 0, 'disabled' => 0];
        }

        // Get counts using Eloquent
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

    private function loadMembersData()
    {
        try {
            $code = $this->constituency->constituency_code;

            // Get members with their group relationships and loan data
            $membersWithGroups = Member::whereHas('groupMembers.groupLoan.loanPhase.constituency', function ($query) use ($code) {
                    $query->where('constituency_code', $code);
                })
                ->with(['groupMembers.groupLoan' => function ($query) {
                    $query->select(['id', 'amount_issued', 'amount_repaid']);
                }])
                ->get();

            // Filter members who are in more than one group and enhance with risk data
            $multiGroupMembers = $membersWithGroups->filter(function ($member) {
                return $member->groupMembers->count() > 1;
            })->map(function ($member) {
                $groupCount = $member->groupMembers->count();

                // Calculate loan balances from group members
                $totalAmountIssued = 0;
                $totalBalance = 0;
                $highestGroupBalance = 0;
                $highRiskGroups = 0;
                $mediumRiskGroups = 0;

                foreach ($member->groupMembers as $groupMember) {
                    if ($groupMember->groupLoan) {
                        $loan = $groupMember->groupLoan;
                        $balance = ($loan->amount_issued ?? 0) - ($loan->amount_repaid ?? 0);

                        $totalAmountIssued += $loan->amount_issued ?? 0;
                        $totalBalance += $balance;

                        if ($balance > $highestGroupBalance) {
                            $highestGroupBalance = $balance;
                        }

                        // Count risk groups
                        if ($balance > 50000) {
                            $highRiskGroups++;
                        } elseif ($balance >= 10000) {
                            $mediumRiskGroups++;
                        }
                    }
                }

                // Calculate risk level
                $riskLevel = $this->calculateRiskLevel(
                    $totalBalance,
                    $highestGroupBalance,
                    $highRiskGroups,
                    $groupCount
                );

                $riskScore = $this->calculateRiskScore(
                    $totalBalance,
                    $highestGroupBalance,
                    $highRiskGroups,
                    $groupCount
                );

                return (object) [
                    'member_Id' => $member->member_Id,
                    'name' => $member->name,
                    'number' => $member->number,
                    'gender' => $member->gender,
                    'disabled' => $member->disabled,
                    'group_count' => $groupCount,
                    'total_amount_issued' => $totalAmountIssued,
                    'total_balance' => $totalBalance,
                    'highest_group_balance' => $highestGroupBalance,
                    'high_risk_groups' => $highRiskGroups,
                    'medium_risk_groups' => $mediumRiskGroups,
                    'risk_level' => $riskLevel,
                    'risk_score' => $riskScore
                ];
            })->sortByDesc('group_count')
              ->sortByDesc('total_balance')
              ->values();

            return $multiGroupMembers;

        } catch (\Exception $e) {
            \Log::error('Error loading multi-group members:', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    private function calculateRiskStats(): array
    {
        try {
            $code = $this->constituency->constituency_code;

            // Get members with their group relationships
            $membersWithGroups = Member::whereHas('groupMembers.groupLoan.loanPhase.constituency', function ($query) use ($code) {
                    $query->where('constituency_code', $code);
                })
                ->with(['groupMembers.groupLoan'])
                ->get();

            $highRisk = 0;
            $mediumRisk = 0;
            $lowRisk = 0;

            foreach ($membersWithGroups as $member) {
                // Skip members with only one group
                if ($member->groupMembers->count() <= 1) {
                    continue;
                }

                // Calculate balances for risk assessment
                $totalBalance = 0;
                $highestGroupBalance = 0;
                $highRiskGroups = 0;

                foreach ($member->groupMembers as $groupMember) {
                    if ($groupMember->groupLoan) {
                        $loan = $groupMember->groupLoan;
                        $balance = ($loan->amount_issued ?? 0) - ($loan->amount_repaid ?? 0);

                        $totalBalance += $balance;

                        if ($balance > $highestGroupBalance) {
                            $highestGroupBalance = $balance;
                        }

                        if ($balance > 50000) {
                            $highRiskGroups++;
                        }
                    }
                }

                $riskLevel = $this->calculateRiskLevel(
                    $totalBalance,
                    $highestGroupBalance,
                    $highRiskGroups,
                    $member->groupMembers->count()
                );

                switch ($riskLevel) {
                    case 'high': $highRisk++; break;
                    case 'medium': $mediumRisk++; break;
                    case 'low': $lowRisk++; break;
                }
            }

            return [
                'high_risk' => $highRisk,
                'medium_risk' => $mediumRisk,
                'low_risk' => $lowRisk,
                'total_multi_group' => $highRisk + $mediumRisk + $lowRisk
            ];

        } catch (\Exception $e) {
            \Log::error('Error calculating risk stats:', ['error' => $e->getMessage()]);
            return ['high_risk' => 0, 'medium_risk' => 0, 'low_risk' => 0, 'total_multi_group' => 0];
        }
    }

    private function calculateRiskLevel($totalBalance, $highestGroupBalance, $highRiskGroups, $groupCount): string
    {
        $riskScore = $this->calculateRiskScore($totalBalance, $highestGroupBalance, $highRiskGroups, $groupCount);

        if ($riskScore >= 7) {
            return 'high';
        } elseif ($riskScore >= 4) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function calculateRiskScore($totalBalance, $highestGroupBalance, $highRiskGroups, $groupCount): int
    {
        $score = 0;

        // Score based on total balance
        if ($totalBalance > 100000) $score += 3;
        elseif ($totalBalance > 50000) $score += 2;
        elseif ($totalBalance > 20000) $score += 1;

        // Score based on highest individual group balance
        if ($highestGroupBalance > 50000) $score += 3;
        elseif ($highestGroupBalance > 25000) $score += 2;
        elseif ($highestGroupBalance > 10000) $score += 1;

        // Score based on number of high-risk groups
        if ($highRiskGroups >= 2) $score += 2;
        elseif ($highRiskGroups == 1) $score += 1;

        // Score based on total number of groups
        if ($groupCount >= 4) $score += 2;
        elseif ($groupCount == 3) $score += 1;

        return min($score, 10); // Cap at 10
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

    #[On('refresh-members')]
    public function refreshMembers()
    {
        $this->loading = true;
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $riskStatsKey = "{$cacheKey}_risk_stats";

        Cache::forget($cacheKey);
        Cache::forget($summaryKey);
        Cache::forget($timestampKey);
        Cache::forget($riskStatsKey);

        $this->getMultiGroupMembers();

        $this->dispatch('members-refreshed', [
            'message' => 'Members data refreshed successfully',
            'constituency' => $this->constituency->constituency_name
        ]);

        usleep(500000); // 500ms delay
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.group.multi-group-members');
    }
}
