<?php

namespace App\Services\Loan;

use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DefaultAnalysisService
{
    protected $analysisService;

    public function __construct(LoanAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Get all defaulter groups (with optional filters)
     *
     * @param string|null $constituencyCode Filter by constituency
     * @param int $minArrears Minimum arrears amount
     * @param int $minMonthsDefaulted Minimum months defaulted
     * @return Collection
     */
    public function getDefaulterGroups(
        ?string $constituencyCode = null,
        int $minArrears = 0,
        int $minMonthsDefaulted = 0
     ): Collection {
        $query = Group::query()
            ->whereHas('currentLoan', function ($q) use ($minArrears, $minMonthsDefaulted) {
                $q->where('is_defaulted', true)
                  ->where('total_arrears', '>=', $minArrears)
                  ->where('months_defaulted', '>=', $minMonthsDefaulted);
            })
            ->with(['currentLoan' => function ($q) {
                $q->where('is_defaulted', true)
                  ->with(['groupMembers.member']);
            }]);

        if ($constituencyCode) {
            $query->where('ward.constituency_code', $constituencyCode);
        }

        return $query->get();
    }

    /**
     * Get defaulter status for a single group
     *
     * @param Group $group
     * @return array Defaulter details
     */
    public function getGroupDefaultStatus(Group $group): array
    {
        $defaultedLoans = $group->currentLoan()->where('is_defaulted', true)->get();

        if ($defaultedLoans->isEmpty()) {
            return [
                'is_defaulter' => false,
                'total_arrears' => 0,
                'total_defaulted_loans' => 0,
                'defaulted_loans' => [],
            ];
        }

        $totalArrears = $defaultedLoans->sum('total_arrears');

        return [
            'is_defaulter' => true,
            'total_arrears' => $totalArrears,
            'total_defaulted_loans' => $defaultedLoans->count(),
            'defaulted_loans' => $defaultedLoans->map(function ($loan) {
                return [
                    'loan_id' => $loan->id,
                    'arrears' => $loan->total_arrears,
                    'months_defaulted' => $loan->months_defaulted,
                    'disbursement_date' => $loan->disbursement_date,
                    'defaulted_members' => $loan->groupMembers->map(fn($gm) => $gm->member->name),
                ];
            }),
        ];
    }

    /**
     * Get all defaulter members (cross-group, with optional filters)
     *
     * @param string|null $constituencyCode
     * @return Collection
     */
    public function getDefaulterMembers(?string $constituencyCode = null): Collection
    {
        $query = Member::query()
            ->whereHas('groupMembers.groupLoan', function ($q) {
                $q->where('is_defaulted', true);
            })
            ->with(['groupMembers.groupLoan.group']);

        if ($constituencyCode) {
            $query->whereHas('groupMembers.groupLoan.group.ward', fn($q) => $q->where('constituency_code', $constituencyCode));
        }

        return $query->get();
    }

    /**
     * Get default status for a single member
     *
     * @param Member $member
     * @return array
     */
    public function getMemberDefaultStatus(Member $member): array
    {
        $defaultedGroupMembers = $member->groupMembers()
            ->with('groupLoan')
            ->whereHas('groupLoan', fn($q) => $q->where('is_defaulted', true))
            ->get();

        if ($defaultedGroupMembers->isEmpty()) {
            return [
                'is_defaulter' => false,
                'total_defaulted_loans' => 0,
                'total_arrears' => 0,
                'defaulted_groups' => [],
            ];
        }

        $totalArrears = $defaultedGroupMembers->sum(fn($gm) => $gm->groupLoan->total_arrears);

        return [
            'is_defaulter' => true,
            'total_defaulted_loans' => $defaultedGroupMembers->count(),
            'total_arrears' => $totalArrears,
            'defaulted_groups' => $defaultedGroupMembers->map(function ($gm) {
                $loan = $gm->groupLoan;
                return [
                    'group_name' => $loan->group->name,
                    'loan_id' => $loan->id,
                    'arrears' => $loan->total_arrears,
                    'months_defaulted' => $loan->months_defaulted,
                ];
            }),
        ];
    }

    /**
     * Calculate risk level for a group (0-100 score, low to high risk)
     * members are based on the current loan
     * Algorithm:
     * - 50% weight: Arrears percentage of issued amount
     * - 30% weight: Number of defaulted loans
     * - 20% weight: Percentage of members with cross-group defaults
     *
     * @param Group $group
     * @return float Risk score (0-100)
     */
    public function calculateGroupRiskLevel(?Group $group, $applicationMembers = null): array
    {
        $reasons = [];
        $allReasons = [];
        $categoryScores = [
            'financial' => ['score' => 0, 'category' => 'No Risk', 'reasons' => []],
            'historical' => ['score' => 0, 'category' => 'No Risk', 'reasons' => []],
            'contagion' => ['score' => 0, 'category' => 'No Risk', 'reasons' => []],
            'member_exposure' => ['score' => 0, 'category' => 'No Risk', 'reasons' => []],
        ];

        $cfg = config('risk');

        if ($group) {
            // 1. Financial Risk (Current Arrears in linked Group) — highest priority
            $financial = $this->calculateFinancialRisk($group, $cfg);
            $categoryScores['financial'] = $financial;
            if ($financial['score'] > 0) {
                $allReasons = array_merge($allReasons, $financial['reasons']);
            }

            // 2. Historical Risk (Past defaulted loans in linked Group)
            $historical = $this->calculateHistoricalRisk($group, $cfg);
            $categoryScores['historical'] = $historical;
            if ($historical['score'] > 0) {
                $allReasons = array_merge($allReasons, $historical['reasons']);
            }

        }


        // 3. Contagion Risk (Multiple external defaulters)
        $contagion = $this->calculateContagionRisk($group, $applicationMembers, $cfg);
        $categoryScores['contagion'] = $contagion;
        if ($contagion['score'] > 0) {
            $allReasons = array_merge($allReasons, $contagion['reasons']);
        }


        // 4. Member Exposure (Average arrears per defaulter member)
        $memberExposure = $this->calculateMemberExposureRisk($applicationMembers, $cfg);
        $categoryScores['member_exposure'] = $memberExposure;
        if ($memberExposure['score'] > 0) {
            $allReasons = array_merge($allReasons, $memberExposure['reasons']);
        }

        // Weighted total score (adjust weights in config if needed)
        $totalScore = (
            ($categoryScores['financial']['score'] ?? 0) * 0.40 +
            ($categoryScores['contagion']['score'] ?? 0) * 0.25 +
            ($categoryScores['historical']['score'] ?? 0) * 0.20 +
            ($categoryScores['member_exposure']['score'] ?? 0) * 0.15
        );

        // Overall category = highest severity
        $maxScore = max(array_column($categoryScores, 'score'));
        $overallCategory = $maxScore > $cfg['categories']['high'] ? 'High Risk' :
                        ($maxScore > $cfg['categories']['medium'] ? 'Medium Risk' : 'Low Risk');

        // If any category is High Risk, overall is High
        if ($overallCategory === 'High Risk' && $maxScore <= 100) {
            $totalScore = 100.0;
        }

        return [
            'total_score' => (float) round(min(100, $totalScore), 2),
            'overall_category' => $overallCategory,
            'categories' => $categoryScores,
            'reasons' => array_unique($allReasons),
        ];
    }

    /**
     * Financial Risk (Current Arrears)
     */
    private function calculateFinancialRisk($group, $cfg): array
    {
        $arrears = $group->total_arrears ?? 0;
        $score = $arrears > 0 ? $cfg['policy']['any_arrears'] : 0;

        return [
            'score' => $score,
            'category' => $score >= $cfg['categories']['high'] ? 'High Risk' : ($score > 0 ? 'Medium Risk' : 'No Risk'),
            'reasons' => $arrears > 0 ? ["Active arrears: KES " . number_format($arrears)] : [],
        ];
    }

    /**
     * Contagion Risk (Multiple external defaulters)
     */
    private function calculateContagionRisk($group, $applicationMembers, $cfg): array
    {
        $score = 0;
        $reasons = [];

        $totalMembers = $applicationMembers ? count($applicationMembers) : ($group->currentLoan?->group_members_count ?? 0);

        $defaulterMembers = 0;
        $totalMemberArrears = 0;
        $multiGroupCount = 0;

        if ($applicationMembers && $applicationMembers->isNotEmpty()) {
            foreach ($applicationMembers as $gm) {
                if ($gm->is_defaulter ?? false) {
                    $defaulterMembers++;

                    $history = $gm->default_history ?? collect();

                    $memberArrears = $history->sum('default_arrears');
                    $totalMemberArrears += $memberArrears;

                    // Multi-group check: Count unique groups this member defaulted in
                    $uniqueDefaultGroups = $history->pluck('groupLoan.group.name')->unique()->count();
                    if ($uniqueDefaultGroups > 1) {
                        $multiGroupCount++;
                    }

                    // per-member reason
                    if ($memberArrears > 0) {
                        $reasons[] = "Member {$gm->name} (ID: {$gm->id_number}) has KES " .
                            number_format($memberArrears) . " in external arrears across {$uniqueDefaultGroups} group(s).";
                    }
                }
            }
        } else {
            $defaulterMembers = $group->currentLoan?->defaulter_members_count ?? 0;
        }

        if ($defaulterMembers >= $cfg['thresholds']['contagion']['min_members'] ||
            ($totalMembers > 0 && ($defaulterMembers / $totalMembers) >= $cfg['thresholds']['contagion']['min_percentage'])) {
            $score = 100; // Immediate high risk
            $reasons[] = "High contagion: {$defaulterMembers}/{$totalMembers} members have external defaults.";
        } elseif ($defaulterMembers > 0) {
            $score = ($defaulterMembers / $totalMembers) * 50; // Proportional
            $reasons[] = "{$defaulterMembers} member(s) with external defaults ({$score}% contagion risk).";
        }

        return [
            'score' => (float) round($score, 2),
            'category' => $score >= $cfg['categories']['high'] ? 'High Risk' : ($score > 0 ? 'Medium Risk' : 'No Risk'),
            'reasons' => $reasons,
        ];
    }

    /**
     * Historical Risk (Past defaulted loans in this group)
     */
    private function calculateHistoricalRisk($group, $cfg): array
    {
        $defaultedCount = $group->defaulted_count ?? 0;
        $score = min($defaultedCount * 20, 100); // e.g. 5 defaults = 100%

        return [
            'score' => $score,
            'category' => $score >= $cfg['categories']['high'] ? 'High Risk' : ($score > 0 ? 'Medium Risk' : 'No Risk'),
            'reasons' => $defaultedCount > 0 ? ["{$defaultedCount} historical defaulted loans."] : [],
        ];
    }

    /**
     * Member Exposure (Average arrears per defaulter member)
     */
    private function calculateMemberExposureRisk($applicationMembers, $cfg): array
    {
        if (!$applicationMembers || $applicationMembers->isEmpty()) {
            return ['score' => 0, 'category' => 'No Risk', 'reasons' => []];
        }

        $defaulterMembers = 0;
        $totalArrears = 0;

        foreach ($applicationMembers as $gm) {
            if ($gm->is_defaulter ?? false) {
                $defaulterMembers++;
                $history = $gm->default_history ?? collect();
                $totalArrears += $history->sum(fn($item) => (float) ($item->groupLoan->total_arrears ?? 0));
            }
        }

        if ($defaulterMembers === 0) {
            return ['score' => 0, 'category' => 'No Risk', 'reasons' => []];
        }

        $avgArrears = $totalArrears / $defaulterMembers;
        $score = min(log($avgArrears + 1) / log(1000000) * 100, 100); // Log scale to 100

        return [
            'score' => (float) round($score, 2),
            'category' => $score >= $cfg['categories']['high'] ? 'High Risk' : ($score > 0 ? 'Medium Risk' : 'Low Risk'),
            'reasons' => $defaulterMembers > 0 ? ["Avg arrears per defaulter member: KES " . number_format($avgArrears)] : [],
        ];
    }

    /**
     * Get defaulter members in a specific group
     *
     * @param Group $group
     * @return Collection
     */
    public function getDefaulterMembersInGroup(Group $group): Collection
    {
        if (!$group->currentLoan || !$group->currentLoan->groupMembers) {
            return collect();
        }

        $idNumbers = $group->currentLoan->groupMembers->pluck('member_Id')->toArray();
        $excludeGroupId = $group->id;

        return Member::whereIn('member_Id', $idNumbers)
            ->whereHas('groupMembers.groupLoan', function ($query) use ($excludeGroupId) {
                $query->where('is_defaulted', true);
                if ($excludeGroupId) {
                    $query->whereHas('groupMembers.groupLoan.group', fn($q2) => $q2->where('id', '!=', $excludeGroupId));
                }
            })
            ->get()
            ->keyBy('member_Id');
    }

    /**
     * Check for contagion risk specifically for the members in the current application
     */
    public function getApplicationDefaulterMembers(array $idNumbers, $applicationId, ?int $excludeGroupId = null, $constituencyCode): Collection
    {
        // Return an empty collection instead of null
        if (empty($idNumbers)) {
            return collect();
        }

        $cacheKey = "temp_app_default_members_{$applicationId}_{$constituencyCode}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($idNumbers, $excludeGroupId) {
            return Member::whereIn('member_Id', $idNumbers)
                ->whereHas('groupMembers.groupLoan', function ($query) use ($excludeGroupId) {
                    $query->where('is_defaulted', true);
                    if ($excludeGroupId) {
                        $query->whereHas('groupMembers.groupLoan.group', fn($q2) => $q2->where('id', '!=', $excludeGroupId));
                    }
                })
                ->with([
                    'groupMembers' => fn($q) => $q->select('id', 'member_Id', 'group_loans_id', 'role'),
                    'groupMembers.groupLoan' => fn($q) => $q->select(
                        'id',
                        'group_id',
                        'loan_phase_id',
                        'total_arrears',
                        'months_defaulted',
                        'is_defaulted',
                        'amount_issued'
                    ),
                    'groupMembers.groupLoan.group' => fn($q) => $q->select('id', 'name', 'ward_id', 'slug'),
                    'groupMembers.groupLoan.group.ward' => fn($q) => $q->select('id', 'ward_name', 'constituency_code'),
                    'groupMembers.groupLoan.group.ward.constituency',
                    'groupMembers.groupLoan.loanPhase' => fn($q) => $q->select('id', 'phase')
                    ])
                ->get()
                ->keyBy('member_Id');
        });
    }

    /**
     * Check if a group is eligible for a new loan (based on defaults)
     *
     * @param Group $group
     * @return bool
     */
    public function isGroupEligibleForNewLoan(Group $group): bool
    {
        return !$group->currentLoan()->where('is_defaulted', true)->exists();
    }

    /**
     * Prepare a group or a query builder with the necessary metrics
    */
    public function getGroupWithMetrics($group)
    {
        // If $group is an object, get its ID. Otherwise, use it as the ID.
        $groupId = is_object($group) ? $group->id : $group;

        $cacheKey = "temp_app_linked_group_{$groupId}";


        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($groupId) {
            return Group::where('id', $groupId) // Specifically filter for this group
                ->withSum('groupLoans as total_issued', 'amount_issued')
                ->withSum('groupLoans as total_repaid', 'amount_repaid')
                ->withSum('groupLoans as total_arrears', 'total_arrears')
                ->withCount(['groupLoans as defaulted_count' => function ($query) {
                    $query->where('is_defaulted', true);
                }])
                ->with([
                    'groupLoans',
                    'groupLoans.loanReceipts',
                    'currentLoan' => function($q) use ($groupId){
                    $q->withCount([
                        'groupMembers as defaulter_members_count' => function($sq) use ($groupId){
                            $sq->whereHas('member.groupMembers.groupLoan', function($l) use ($groupId) {
                                $l->where('is_defaulted', true)
                                ->whereHas('group', function($g) use ($groupId) {
                                    $g->where('id', '!=', $groupId);
                                });
                            });
                        }
                    ])->with([
                        'groupMembers'
                    ]);
                }])
                ->first();
        });
    }

    /**
     * Prepare groups or a query builder with the necessary metrics
     */
    public function getGroupsWithMetrics($constituencyCode = null) : Collection
    {
        $query = Group::query();

        if ($constituencyCode) {
            $query->whereHas('ward', fn($q) => $q->where('constituency_code', $constituencyCode));
        }

        return $query
            // 1. Financial Aggregates
            ->withSum('groupLoans as total_issued', 'amount_issued')
            ->withSum('groupLoans as total_arrears', 'total_arrears')
            ->withCount(['groupLoans as defaulted_count' => fn($q) => $q->where('is_defaulted', true)])

            // 2. Member Statistics for Risk Part 3
            ->with(['currentLoan' => function($query) {
                $query->withCount([
                    'groupMembers', // Total members in the current group
                    // Count members who are defaulters (have at least one defaulted loan anywhere)
                    'groupMembers as defaulter_members_count' => function($q) {
                        $q->whereHas('member.groupMembers.groupLoan', function($inner) {
                            $inner->where('is_defaulted', true);
                        });
                    }
                ]);
            }])
            ->get();
    }

    public function generateRiskReport(?string $constituencyCode = null)
    {
        // One query to fetch everything
        $groups = $this->getGroupsWithMetrics($constituencyCode);

        return $groups->map(function ($group) {
            return [
                'group_id'   => $group->id,
                'group_name' => $group->name,
                'risk_score' => $this->calculateGroupRiskLevel($group),
                'metrics'    => [
                    'total_arrears'     => $group->total_arrears,
                    'defaulted_loans'   => $group->defaulted_count,
                    'defaulter_members' => $group->currentLoan?->defaulter_members_count ?? 0
                ]
            ];
        })->sortByDesc('risk_score');
    }

    /**
     * Get a human-readable label for the risk level
     * * @param float $score
     * @return array
     */
    public function getRiskCategory(float $score): array
    {
        return match (true) {
            $score <= 20 => [
                'label' => 'Low',
                'color' => 'success', // Green
                'level' => 1
            ],
            $score <= 50 => [
                'label' => 'Medium',
                'color' => 'warning', // Yellow
                'level' => 2
            ],
            $score <= 80 => [
                'label' => 'High',
                'color' => 'orange',  // Orange
                'level' => 3
            ],
            default => [
                'label' => 'Critical',
                'color' => 'danger',  // Red
                'level' => 4
            ],
        };
    }

    public function getGroupRiskAnalysis(Group $group, $isApplicationRisk = false): array
    {
        // Reuse our optimized calculation
        $score = $this->calculateGroupRiskLevel($group, $isApplicationRisk);
        $category = $this->getRiskCategory($score);

        return [
            'score' => $score,
            'label' => $category['label'],
            'color' => $category['color'],
            'is_eligible' => $score <= 50, // Business rule: High/Critical are ineligible
            'breakdown' => [
                'financial_risk' => min(($group->total_arrears / ($group->total_issued ?: 1)), 1) * 50,
                'historical_risk' => min(($group->defaulted_count ?? 0) * 6, 30),
                'social_risk' => $group->currentLoan?->group_members_count > 0
                    ? ($group->currentLoan->defaulter_members_count / $group->currentLoan->group_members_count) * 20
                    : 0,
            ]
        ];
    }

    public function getRiskFlags(Group $group, $applicationMembers = null): array
    {
        $flags  = [];
        // $flags[] = $this->calculateGroupRiskLevel($group, $applicationMembers);

        dd($this->calculateGroupRiskLevel($group, $applicationMembers));
        // foreach ($applicationMembers as $gm) {
        //     if ($gm->is_defaulter ?? false) {


        //         $history = $gm->default_history ?? collect();
        //         $memberArrears = $history->sum('default_arrears');
        //         dd($history->sum(fn($item) => (float) ($item->groupLoan->total_arrears ?? 0)));
        //         dd($history->sum(fn($item) => $item->groupLoan->total_arrears ?? 0));
        //         dd($history->map(fn($item) => [
        //             'group' => $item->groupLoan->group->name ?? 'N/A',
        //             'arrears' => $item->groupLoan->total_arrears ?? 0,
        //             'months' => $item->groupLoan->months_defaulted ?? 0,
        //         ]));
        //         $totalMemberArrears += $history->sum('arrears') ?? 0;

        //         $defaultGroups = $history->pluck('group_name')->unique()->count();
        //         if ($defaultGroups > 1) {
        //             $multiGroupCount++;
        //         }
        //     }
        // }
        // $score = $this->calculateGroupRiskLevel($group, $applicationMembers);

        // $riskAnalysis = [
        //     'score' => $score,
        //     'breakdown' => [
        //         'financial_risk' => min(($group->total_arrears / ($group->total_issued ?: 1)), 1) * 50,
        //         'historical_risk' => min(($group->defaulted_count ?? 0) * 6, 30),
        //         'social_risk' => count($applicationMembers) > 0
        //             ? ($applicationMembers
        //                 ->filter(fn($gm) => $gm->is_defaulter)
        //                 ->count() / count($applicationMembers) * 20)
        //             : 0,
        //     ]
        // ];

        // $riskLevel = $riskAnalysis['score'];

        // if ($riskLevel > 80) {
        //     $flags[] = "CRITICAL RISK: This group has a risk score of " . number_format($riskLevel, 0) . "%. Immediate intervention or rejection is advised.";
        // } elseif ($riskLevel > 50) {
        //     $flags[] = "HIGH RISK: Group risk score is " . number_format($riskLevel, 0) . "%. Requires Monitoring closely.";
        // } elseif ($riskLevel > 20) {
        //     $flags[] = "MEDIUM RISK: Group risk score is " . number_format($riskLevel, 0) . "%. Monitor repayment closely.";
        // }

        // if ($riskAnalysis['breakdown']['financial_risk'] > 25){
        //     $flags[] = "FINANCIAL RISK: Group has failed to pay " . round(($group->total_arrears / ($group->total_issued ?: 1)) * 100) . "% of historical loans.";
        // }

        // if ($group->defaulted_count > 0) {
        //     $flags[] = "HISTORICAL RISK: Group has {$group->defaulted_count} fully defaulted loans.";
        // }

        return $flags;
    }


}
