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
     *
     * Algorithm:
     * - 50% weight: Arrears percentage of issued amount
     * - 30% weight: Number of defaulted loans
     * - 20% weight: Percentage of members with cross-group defaults
     *
     * @param Group $group
     * @return float Risk score (0-100)
     */
    public function calculateGroupRiskLevel(?Group $group, $applicationDefaultMembers =null): float
    {
        if (is_null($group)) {
            return 0.0;
        }

        // 1. Arrears percentage
        $totalIssued = $group->total_issued;
        $totalArrears = $group->total_arrears;

        $arrearsRatio = $totalIssued > 0 ? ($totalArrears / $totalIssued) : 0;
        // We cap the ratio at 1.0 (100%) so the score doesn't exceed 50
        $arrearsScore = min($arrearsRatio, 1.0) * 50;

        // 2. Defaulted loans count (capped at 5 for scoring)
        $defaultedCount = $group->defaulted_count ?? 0;
        $defaultedLoansScore = min($defaultedCount * 6, 30);

        // 3. Cross-default members percentage
        $totalMembers = $group->currentLoan?->group_members_count ?? 0;

        $defaulterMembers = $group->currentLoan?->defaulter_members_count ?? 0;

        $crossDefaultScore = $totalMembers > 0
            ? ($defaulterMembers / $totalMembers) * 20 // Max 20 points
            : 0;

        $riskScore = $arrearsScore + $defaultedLoansScore + $crossDefaultScore;

        Log::info("Calculated risk for group {$group->id}: {$riskScore}", [
            'arrears_score' => $arrearsScore,
            'defaulted_loans_score' => $defaultedLoansScore,
            'cross_default_score' => $crossDefaultScore,
        ]);

        return (float) round(min(100, $riskScore), 2);  // Cap at 100
    }

    /**
     * Get defaulter members in a specific group
     *
     * @param Group $group
     * @return Collection
     */
    public function getDefaulterMembersInGroup(Group $group): Collection
    {
        // If we have eager-loaded the current loan and its members,
        // we can filter the collection in PHP memory rather than hitting the DB again.
        if (!$group->currentLoan || !$group->currentLoan->groupMembers) {
            return collect();
        }

        return $group->currentLoan->groupMembers
            ->filter(fn($gm) => $gm->member->is_defaulter) // Assuming an accessor or loaded relation
            ->map(fn($gm) => $gm->member);
    }

    /**
     * Check for contagion risk specifically for the members in the current application
     */
    public function getApplicationDefaulterMembers(array $idNumbers, $applicationId, $constituencyCode): Collection
    {
        // Return an empty collection instead of null
        if (empty($idNumbers)) {
            return collect();
        }

        $cacheKey = "temp_app_default_members_{$applicationId}_{$constituencyCode}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($idNumbers) {
            return Member::whereIn('member_Id', $idNumbers)
                ->whereHas('groupMembers.groupLoan', function ($query) {
                    $query->where('is_defaulted', true);
                })
                ->with(['groupMembers.groupLoan.group', 'groupMembers.groupLoan.loanPhase'])
                ->get();
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
     * Detect members from a list of ID numbers who appear in other defaulted groups
     *
     * @param array $idNumbers Array of member ID numbers (e.g. from application members)
     * @param int|null $excludeGroupId Optional: Group ID to exclude (current group)
     * @return Collection Members with their defaulted loans count
     */
    public function getCrossDefaultMembers(array $idNumbers, ?int $excludeGroupId = null): Collection
    {
        if (empty($idNumbers)) {
            return collect();
        }

        $query = Member::whereIn('member_Id', $idNumbers)
            ->whereHas('groupMembers.groupLoan', function ($q) use ($excludeGroupId) {
                $q->where('is_defaulted', true);

                // Exclude the current group if provided
                if ($excludeGroupId) {
                    $q->whereHas('groupMembers.groupLoan.group', fn($q2) => $q2->where('id', '!=', $excludeGroupId));
                }
            })
            ->withCount([
                'groupMembers as defaulted_loans_count' => function ($q) use ($excludeGroupId) {
                    $q->whereHas('groupLoan', fn($q2) => $q2->where('is_defaulted', true));

                    if ($excludeGroupId) {
                        $q->whereHas('groupLoan.group', fn($q3) => $q3->where('id', '!=', $excludeGroupId));
                    }
                }
            ])
            ->get();

        return $query;
    }

    /**
     * Prepare a group or a query builder with the necessary metrics
    */
    public function getGroupWithMetrics($group)
    {
        // If $group is an object, get its ID. Otherwise, use it as the ID.
        $groupId = is_object($group) ? $group->id : $group;

        return Group::where('id', $groupId) // Specifically filter for this group
            ->withSum('groupLoans as total_issued', 'amount_issued')
            ->withSum('groupLoans as total_arrears', 'total_arrears')
            ->withCount(['groupLoans as defaulted_count' => function ($query) {
                $query->where('is_defaulted', true);
            }])
            ->with([
                'groupLoans',
                'currentLoan' => function($q) {
                $q->withCount([
                    'groupMembers',
                    'groupMembers as defaulter_members_count' => function($sq) {
                        $sq->whereHas('member.groupMembers.groupLoan', fn($l) => $l->where('is_defaulted', true));
                    }
                ]);
            }])
            ->first(); // Now first() correctly returns the record matching the ID
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

    public function getGroupRiskAnalysis(Group $group): array
    {
        // Reuse our optimized calculation
        $score = $this->calculateGroupRiskLevel($group);
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

    public function getRiskFlags(Group $group): array
    {
        $flags = [];
        $riskAnalysis = $this->getGroupRiskAnalysis($group);
        $riskLevel = $riskAnalysis['score'];

        // 1. Primary Risk Level Flags
        if ($riskLevel > 80) {
            $flags[] = "CRITICAL RISK: This group has a risk score of " . number_format($riskLevel, 0) . "%. Immediate intervention or rejection is advised.";
        } elseif ($riskLevel > 50) {
            $flags[] = "HIGH RISK: Group risk score is " . number_format($riskLevel, 0) . "%. Requires senior management approval.";
        } elseif ($riskLevel > 20) {
            $flags[] = "MEDIUM RISK: Group risk score is " . number_format($riskLevel, 0) . "%. Monitor repayment closely.";
        }

        // 2. Specific Contagion/Social Flags (from our Part 3 logic)
        $defaulterCount = $group->currentLoan?->defaulter_members_count ?? 0;
        if ($defaulterCount > 0) {
            $flags[] = "CONTAGION RISK: {$defaulterCount} member(s) in this group have defaulted in other groups.";
        }

        // 3. Arrears Exposure Flags (from our Part 1 logic)
        if ($group->total_arrears > 0 && $group->total_issued > 0) {
            $arrearsRatio = ($group->total_arrears / $group->total_issued) * 100;
            if ($arrearsRatio > 25) {
                $flags[] = "FINANCIAL RISK: Historical arrears exceed " . number_format($arrearsRatio, 0) . "% of total borrowed funds.";
            }
        }

        return $flags;
    }

    public function getApplicationRiskFlags(Group $group): array
    {
        $flags = [];
        $riskAnalysis = $this->getGroupRiskAnalysis($group);
        $riskLevel = $riskAnalysis['score'];

        // 1. Primary Risk Level Flags
        if ($riskLevel > 80) {
            $flags[] = "CRITICAL RISK: This group has a risk score of " . number_format($riskLevel, 0) . "%. Immediate intervention or rejection is advised.";
        } elseif ($riskLevel > 50) {
            $flags[] = "HIGH RISK: Group risk score is " . number_format($riskLevel, 0) . "%. Requires senior management approval.";
        } elseif ($riskLevel > 20) {
            $flags[] = "MEDIUM RISK: Group risk score is " . number_format($riskLevel, 0) . "%. Monitor repayment closely.";
        }

        // 2. Specific Contagion/Social Flags (from our Part 3 logic)
        $defaulterCount = $group->currentLoan?->defaulter_members_count ?? 0;
        if ($defaulterCount > 0) {
            $flags[] = "CONTAGION RISK: {$defaulterCount} member(s) in this group have defaulted in other groups.";
        }

        // 3. Arrears Exposure Flags (from our Part 1 logic)
        if ($group->total_arrears > 0 && $group->total_issued > 0) {
            $arrearsRatio = ($group->total_arrears / $group->total_issued) * 100;
            if ($arrearsRatio > 25) {
                $flags[] = "FINANCIAL RISK: Historical arrears exceed " . number_format($arrearsRatio, 0) . "% of total borrowed funds.";
            }
        }

        return $flags;
    }


}
