<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency;
use App\Models\GroupMember;
use App\Models\Member;
use App\Models\GroupLoan;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        // $this->cacheTtl = config('cache-config.members.ttl', 300); // 5 minutes
        // $this->cachePrefix = config('cache-config.multi_members.prefix', 'constituency_multi_group_members');

        $this->cacheTtl = config('cache-config.multi_members.ttl');
        $this->cachePrefix = config('cache-config.multi_members.prefix');

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

        $cacheTags = [
            "constituency:{$this->constituency->constituency_code}",
            "members:multi_group",
            "component:multi-group-members",
            "risk:analysis"
        ];

        // Check cache status
        $this->cacheHit = Cache::tags($cacheTags)->has($cacheKey) &&
                         Cache::tags($cacheTags)->has($summaryKey) &&
                         Cache::tags($cacheTags)->has($riskStatsKey);

        // Get or calculate data
        $summary = Cache::tags($cacheTags)->remember($summaryKey, $this->cacheTtl, function() {
            return $this->calculateMembersSummary();
        });

        $cachedMembers = Cache::tags($cacheTags)->remember($cacheKey, $this->cacheTtl, function() {
            return $this->loadMembersData();
        });

        $cachedRiskStats = Cache::tags($cacheTags)->remember($riskStatsKey, $this->cacheTtl, function() {
            return $this->calculateRiskStats();
        });

        // Store timestamp
        Cache::tags($cacheTags)->put($timestampKey, now()->toDateTimeString(), $this->cacheTtl);
        $this->lastUpdated = Cache::tags($cacheTags)->get($timestampKey);

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
                    $query->select(['id', 'amount_issued', 'amount_repaid', 'disbursement_date', 'loan_status', 'group_id']);
                }])
                ->with(['groupMembers.groupLoan.group' => function ($query) {
                    $query->select(['id', 'name', 'code']);
                }])
                ->get();

            // Filter members who are in more than one group and enhance with risk data
            $multiGroupMembers = $membersWithGroups->filter(function ($member) {
                // Count distinct groups instead of group members
                $distinctGroups = $member->groupMembers->groupBy(function ($groupMember) {
                    return $groupMember->groupLoan->group_id ?? 'unknown';
                });

                return $distinctGroups->count() > 1;
            })->map(function ($member) {
                // Group groupMembers by actual group (not by loan)
                $groupedByGroup = $member->groupMembers->groupBy(function ($groupMember) {
                    return $groupMember->groupLoan->group_id ?? 'unknown';
                });

                $groups = [];
                $totalArrears = 0;
                $highestGroupArrears = 0;
                $highRiskGroups = 0;
                $mediumRiskGroups = 0;
                $activeLoansCount = 0;
                $totalDefaultDays = 0;
                $maxDefaultDays = 0;

                foreach ($groupedByGroup as $groupLoans) {
                    $firstGroupMember = $groupLoans->first();
                    $group = $firstGroupMember->groupLoan->group ?? null;

                    if (!$group) continue;

                    // Calculate arrears and default information for this group
                    $groupArrears = 0;
                    $groupActiveLoans = 0;
                    $isGroupDefaulter = false;
                    $groupDefaultDays = 0;

                    foreach ($groupLoans as $groupMember) {
                        if ($groupMember->groupLoan) {
                            $loan = $groupMember->groupLoan;

                            // Analyze loan status
                            $loanAnalysis = $this->analyzeSingleLoan($loan);

                            if ($loanAnalysis['is_active']) {
                                $groupActiveLoans++;
                                $groupArrears += $loanAnalysis['arrears'];

                                if ($loanAnalysis['is_defaulted']) {
                                    $isGroupDefaulter = true;
                                    $groupDefaultDays = max($groupDefaultDays, $loanAnalysis['default_days']);
                                }
                            }
                        }
                    }

                    $totalArrears += $groupArrears;

                    // Update highest group arrears
                    if ($groupArrears > $highestGroupArrears) {
                        $highestGroupArrears = $groupArrears;
                    }

                    // Update maximum default days
                    if ($groupDefaultDays > $maxDefaultDays) {
                        $maxDefaultDays = $groupDefaultDays;
                    }

                    $totalDefaultDays += $groupDefaultDays;

                    // Count risk groups based on arrears and default duration
                    $groupRiskLevel = $this->calculateGroupRiskLevel($groupArrears, $groupDefaultDays);

                    if ($groupRiskLevel === 'high') {
                        $highRiskGroups++;
                    } elseif ($groupRiskLevel === 'medium') {
                        $mediumRiskGroups++;
                    }

                    // Add to active loans count
                    if ($groupActiveLoans > 0) {
                        $activeLoansCount++;
                    }

                    // Add group information
                    $groups[] = (object) [
                        'name' => $group->name ?? 'Unknown Group',
                        'code' => $group->code ?? 'N/A',
                        'arrears' => $groupArrears,
                        'is_defaulter' => $isGroupDefaulter,
                        'default_days' => $groupDefaultDays,
                        'active_loans_count' => $groupActiveLoans,
                        'total_loans' => $groupLoans->count(),
                        'risk_level' => $groupRiskLevel
                    ];
                }

                $groupCount = count($groups);

                // Calculate overall member risk level using enhanced algorithm
                $riskLevel = $this->calculateEnhancedRiskLevel(
                    $totalArrears,
                    $highestGroupArrears,
                    $highRiskGroups,
                    $groupCount,
                    $maxDefaultDays,
                    $totalDefaultDays
                );

                $riskScore = $this->calculateEnhancedRiskScore(
                    $totalArrears,
                    $highestGroupArrears,
                    $highRiskGroups,
                    $groupCount,
                    $maxDefaultDays,
                    $totalDefaultDays
                );

                return (object) [
                    'member_Id' => $member->member_Id,
                    'name' => $member->name,
                    'number' => $member->number,
                    'gender' => $member->gender,
                    'disabled' => $member->disabled,
                    'group_count' => $groupCount,
                    'groups' => $groups,
                    'total_arrears' => $totalArrears,
                    'highest_group_arrears' => $highestGroupArrears,
                    'high_risk_groups' => $highRiskGroups,
                    'medium_risk_groups' => $mediumRiskGroups,
                    'active_loans_count' => $activeLoansCount,
                    'max_default_days' => $maxDefaultDays,
                    'total_default_days' => $totalDefaultDays,
                    'risk_level' => $riskLevel,
                    'risk_score' => $riskScore
                ];
            })->sortByDesc('group_count')
              ->sortByDesc('total_arrears')
              ->values();

            return $multiGroupMembers;

        } catch (\Exception $e) {
            Log::error('Error loading multi-group members:', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    private function analyzeSingleLoan($loan)
    {
        if (!$loan->disbursement_date) {
            return [
                'is_active' => false,
                'is_defaulted' => false,
                'amount_due' => 0,
                'arrears' => 0,
                'months_defaulted' => 0,
                'default_days' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => 0,
            ];
        }

        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $today = Carbon::now();

        // Calculate total months since disbursement
        $totalMonthsSinceDisbursement = $disbursementDate->diffInMonths($today);

        // Grace period: 6 months
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        // Check if still in grace period
        if ($today->lte($gracePeriodEnd)) {
            return [
                'is_active' => true,
                'is_defaulted' => false,
                'amount_due' => 0,
                'arrears' => 0,
                'months_defaulted' => 0,
                'default_days' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => $loan->amount_issued / 24,
            ];
        }

        // Repayment period calculation
        $repaymentStart = $gracePeriodEnd->copy()->addDay();
        $monthsInRepayment = $repaymentStart->diffInMonths($today);

        // IMPORTANT: Don't cap at 24 months for amount due calculation
        // Loans older than 30 months still owe the full amount until repaid
        $monthsInRepaymentForDue = min($monthsInRepayment, 24); // Cap for due calculation

        // Monthly installment
        $monthlyInstallment = $loan->amount_issued / 24;

        // Amount due (what should have been paid by now) - capped at full loan amount
        $amountDue = $monthlyInstallment * $monthsInRepaymentForDue;

        // Arrears (amount due minus amount repaid)
        $arrears = max($amountDue - $loan->amount_repaid, 0);

        // Check if defaulted (arrears > 0)
        $isDefaulted = $arrears > 0;

        // Calculate months defaulted
        $monthsDefaulted = 0;
        if ($isDefaulted && $monthlyInstallment > 0) {
            $monthsDefaulted = floor($arrears / $monthlyInstallment);
        }

        // Calculate days in default (since repayment start + months defaulted)
        $defaultDays = 0;
        if ($isDefaulted && $monthsDefaulted > 0) {
            $defaultStartDate = $repaymentStart->copy()->addMonths($monthsInRepaymentForDue - $monthsDefaulted);
            $defaultDays = $defaultStartDate->diffInDays($today);
        }

        // Loan is active if still within repayment period or has arrears
        // This includes loans older than 30 months that still owe money
        $isActive = $totalMonthsSinceDisbursement <= 30 || $arrears > 0;

        return [
            'is_active' => $isActive,
            'is_defaulted' => $isDefaulted,
            'amount_due' => $amountDue,
            'arrears' => $arrears,
            'months_defaulted' => $monthsDefaulted,
            'default_days' => $defaultDays,
            'months_in_repayment' => $monthsInRepaymentForDue,
            'monthly_installment' => $monthlyInstallment,
        ];
    }

    private function calculateGroupRiskLevel($arrears, $defaultDays): string
    {
        if ($arrears > 100000 || $defaultDays > 180) {
            return 'high';
        } elseif ($arrears > 50000 || $defaultDays > 90) {
            return 'medium';
        } elseif ($arrears > 10000 || $defaultDays > 30) {
            return 'low';
        }
        return 'none';
    }

    private function calculateEnhancedRiskLevel($totalArrears, $highestGroupArrears, $highRiskGroups, $groupCount, $maxDefaultDays, $totalDefaultDays): string
    {
        $riskScore = $this->calculateEnhancedRiskScore($totalArrears, $highestGroupArrears, $highRiskGroups, $groupCount, $maxDefaultDays, $totalDefaultDays);

        if ($riskScore >= 8) {
            return 'high';
        } elseif ($riskScore >= 5) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function calculateEnhancedRiskScore($totalArrears, $highestGroupArrears, $highRiskGroups, $groupCount, $maxDefaultDays, $totalDefaultDays): int
    {
        $score = 0;

        // Score based on total arrears (40% weight)
        if ($totalArrears > 200000) $score += 4;
        elseif ($totalArrears > 100000) $score += 3;
        elseif ($totalArrears > 50000) $score += 2;
        elseif ($totalArrears > 20000) $score += 1;

        // Score based on highest individual group arrears (20% weight)
        if ($highestGroupArrears > 100000) $score += 2;
        elseif ($highestGroupArrears > 50000) $score += 1;

        // Score based on number of high-risk groups (15% weight)
        if ($highRiskGroups >= 3) $score += 3;
        elseif ($highRiskGroups == 2) $score += 2;
        elseif ($highRiskGroups == 1) $score += 1;

        // Score based on total number of groups (10% weight)
        if ($groupCount >= 4) $score += 2;
        elseif ($groupCount == 3) $score += 1;

        // Score based on maximum default duration (10% weight)
        if ($maxDefaultDays > 365) $score += 2;
        elseif ($maxDefaultDays > 180) $score += 1;

        // Score based on total default days across groups (5% weight)
        if ($totalDefaultDays > 365) $score += 1;

        return min($score, 10); // Cap at 10
    }

    private function calculateRiskStats(): array
    {
        try {
            $code = $this->constituency->constituency_code;

            // Get members with their group relationships
            $membersWithGroups = Member::whereHas('groupMembers.groupLoan.loanPhase.constituency', function ($query) use ($code) {
                    $query->where('constituency_code', $code);
                })
                ->with(['groupMembers.groupLoan.group'])
                ->get();

            $highRisk = 0;
            $mediumRisk = 0;
            $lowRisk = 0;

            foreach ($membersWithGroups as $member) {
                // Group by actual group (not by loan)
                $groupedByGroup = $member->groupMembers->groupBy(function ($groupMember) {
                    return $groupMember->groupLoan->group_id ?? 'unknown';
                });

                // Skip members with only one distinct group
                if ($groupedByGroup->count() <= 1) {
                    continue;
                }

                // Calculate arrears for risk assessment
                $totalArrears = 0;
                $highestGroupArrears = 0;
                $highRiskGroups = 0;
                $maxDefaultDays = 0;

                foreach ($groupedByGroup as $groupLoans) {
                    $groupArrears = 0;
                    $groupDefaultDays = 0;

                    foreach ($groupLoans as $groupMember) {
                        if ($groupMember->groupLoan) {
                            $loan = $groupMember->groupLoan;
                            $loanAnalysis = $this->analyzeSingleLoan($loan);

                            if ($loanAnalysis['is_active']) {
                                $groupArrears += $loanAnalysis['arrears'];
                                $groupDefaultDays = max($groupDefaultDays, $loanAnalysis['default_days']);
                            }
                        }
                    }

                    $totalArrears += $groupArrears;

                    if ($groupArrears > $highestGroupArrears) {
                        $highestGroupArrears = $groupArrears;
                    }

                    if ($groupDefaultDays > $maxDefaultDays) {
                        $maxDefaultDays = $groupDefaultDays;
                    }

                    // Count high-risk groups
                    if ($groupArrears > 100000 || $groupDefaultDays > 180) {
                        $highRiskGroups++;
                    }
                }

                $riskLevel = $this->calculateEnhancedRiskLevel(
                    $totalArrears,
                    $highestGroupArrears,
                    $highRiskGroups,
                    $groupedByGroup->count(),
                    $maxDefaultDays,
                    0 // We don't need total default days for stats
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
            Log::error('Error calculating risk stats in MultiGroupMembers:', [
                'error' => $e->getMessage(),
                'constituency' => $this->constituency->constituency_code
            ]);
            return ['high_risk' => 0, 'medium_risk' => 0, 'low_risk' => 0, 'total_multi_group' => 0];
        }
    }

    // Get cache status for debugging
    public function getCacheStatus()
    {
        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $riskStatsKey = "{$cacheKey}_risk_stats";
        $cacheTags = ["constituency:{$this->constituency->constituency_code}", "members:multi_group", "component:multi-group-members", "risk:analysis"];

        return [
            'cache_key' => $cacheKey,
            'has_cache' => Cache::tags($cacheTags)->has($cacheKey),
            'has_summary_cache' => Cache::tags($cacheTags)->has($summaryKey),
            'has_risk_stats' => Cache::tags($cacheTags)->has($riskStatsKey),
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
    //     $riskStatsKey = "{$cacheKey}_risk_stats";

    //     Cache::forget($cacheKey);
    //     Cache::forget($summaryKey);
    //     Cache::forget($timestampKey);
    //     Cache::forget($riskStatsKey);

    //     $this->getMultiGroupMembers();

    //     $this->dispatch('members-refreshed', [
    //         'message' => 'Members data refreshed successfully',
    //         'constituency' => $this->constituency->constituency_name
    //     ]);

    //     usleep(500000); // 500ms delay
    //     $this->loading = false;
    // }
    #[On('refresh-multi-group-members')] // CHANGED: Unique event name
    public function refreshMembers()
    {
        $this->loading = true;
        $this->isReady = false;

        $cacheKey = "{$this->cachePrefix}_{$this->constituency->constituency_code}";
        $summaryKey = "{$cacheKey}_summary";
        $timestampKey = "{$cacheKey}_timestamp";
        $riskStatsKey = "{$cacheKey}_risk_stats";
        $cacheTags = ["constituency:{$this->constituency->constituency_code}", "members:multi_group", "component:multi-group-members", "risk:analysis"];

        // Clear only this component's cache with tags
        Cache::tags($cacheTags)->forget($cacheKey);
        Cache::tags($cacheTags)->forget($summaryKey);
        Cache::tags($cacheTags)->forget($timestampKey);
        Cache::tags($cacheTags)->forget($riskStatsKey);

        usleep(100000);
        $this->getMultiGroupMembers();

        $this->dispatch('multi-group-members-refreshed', [ // CHANGED: Unique event name
            'message' => 'Multi-group members data refreshed successfully',
            'constituency' => $this->constituency->constituency_name
        ]);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.group.multi-group-members');
    }
}
