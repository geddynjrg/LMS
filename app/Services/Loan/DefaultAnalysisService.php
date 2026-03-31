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
use App\Services\Cache\ApplicationCacheService;

class DefaultAnalysisService
{
    protected $analysisService;
    protected $appCache;

    public function __construct(
        LoanAnalysisService $analysisService,
        ApplicationCacheService $appCache
        )
    {
        $this->analysisService = $analysisService;
        $this->appCache = $appCache;
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

    public function getRiskCategory(int $totalScore): array
    {
        $categories = config('risk.categories');

        arsort($categories);

        foreach ($categories as $key => $data) {
            if ($totalScore >= $data['min']) {
                return $data;
            }
        }

        return $categories['none'];
    }

    /**
     * Calculate risk level for a group (0-100 score, low to high risk)
     * members are based on the current loan
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
        $weightedScore = (
            ($categoryScores['financial']['score'] ?? 0) * 0.40 +
            ($categoryScores['historical']['score'] ?? 0) * 0.20 +
            ($categoryScores['contagion']['score'] ?? 0) * 0.25 +
            ($categoryScores['member_exposure']['score'] ?? 0) * 0.15
        );


        $maxScore = !empty($categoryScores) ? max(array_column($categoryScores, 'score')) : 0;

        $totalScore = max($weightedScore, ($maxScore - 20));

        $overallCategory = $this->getRiskCategory($totalScore)['label'];

        return [
            'total_score' => (float) round(min(100, $totalScore), 2),
            'overall_category' => $overallCategory,
            'categories' => $categoryScores,
            'reasons' => array_unique($allReasons),
        ];
    }

    // Safety Floor
    public function calculateSafetyScore(array $categoryScores, int $buffer = 20): array
    {
        $weightedScore = 0;
        $individualScores = [];

        // 1. Calculate Weighted Average & Collect Raw Scores
        foreach ($categoryScores as $key => $data) {
            $score = $data['score'] ?? 0;
            $weight = $data['weight'] ?? 0;

            $weightedScore += ($score * $weight);
            $individualScores[] = $score;
        }

        // 2. Identify the "Worst Case" (e.g., Financial 100)
        $maxIndividualScore = !empty($individualScores) ? max($individualScores) : 0;

        // 3. Apply the Safety Floor Logic
        // If Max is 100, Floor is 80. If Weighted is 45.5, max(45.5, 80) = 80.
        $safetyFloor = $maxIndividualScore - $buffer;
        $finalScore = max($weightedScore, $safetyFloor);

        // 4. Map to Category (Using your existing config logic)
        $categoryData = $this->getRiskCategoryData($finalScore);

        return [
            'total_score' => round($finalScore, 1),
            'weighted_base' => round($weightedScore, 1),
            'max_factor_impact' => $maxIndividualScore,
            'severity' => $categoryData['label'],
            'color' => $categoryData['color'],
            'is_blocked' => $finalScore >= config('risk.categories.critical'), // e.g., 80
        ];
    }

    /**
     * Financial Risk (Current Arrears)
     */
    private function calculateFinancialRisk($group, $cfg): array
    {
        $arrears = (float) ($group->total_arrears ?? 0);



        if ($arrears <= 0) {
            return [
                'score'    => 0,
                'category' => 'Low Risk',
                'reasons'  => [],
            ];
        }

        $totalIssued = (float) ($group->total_issued ?? 0);

        if ($totalIssued <= 0) {
            $ratio      = 1.0;
            $percentStr = 'N/A (total issued not recorded)';
            $severity   = 'Unknown — data issue';
            $score      = $cfg['policy']['any_arrears'] ?? 100;
            $category   = 'High Risk';
        } else {
            $ratio = $arrears / $totalIssued;
            $percentStr = number_format($ratio * 100, 1) . '%';

            $score    = $cfg['policy']['any_arrears'] ?? 100;
            $severity = 'Negligible';
            $category = 'Low Risk';

            $buckets = $cfg['financial']['buckets'] ?? [];

            foreach ($buckets as $bucket) {
                if ($ratio >= ($bucket['min_ratio'] ?? 0)) {
                    $score    = min($score, $bucket['score'] ?? $score);
                    $severity = $bucket['severity'] ?? $severity;
                    $category = $bucket['category'] ?? $category;
                    break;
                }
            }



            // Enforce policy minimum
            // $policyMin = $cfg['policy']['any_arrears'] ?? 100;
            // $score = max($score, $policyMin);

            // // Policy override for category
            // if ($score >= $policyMin && $policyMin >= ($cfg['categories']['high'] ?? 70)) {
            //     $category = 'High Risk';
            // }
        }

        $reasonText = "Active group arrears: KES " . number_format($arrears) .
                  " ({$percentStr} of total issued KES " . number_format($totalIssued) .
                  ") — {$severity} severity — new disbursement blocked score: ({$score})";

        return [
            'score'    => (float) $score,
            'category' => $category,
            'reasons'  => [$reasonText],
        ];
    }

    /**
     * Contagion Risk (Multiple external defaulters)
     */
    private function calculateContagionRisk($group, $applicationMembers, $cfg): array
    {
        $score = 0;
        $reasons = [];

        $totalMembers = $applicationMembers && $applicationMembers->isNotEmpty()
            ? count($applicationMembers)
            : ($group->currentLoan?->group_members_count ?? 0);

        if ($totalMembers <= 0) {
            return [
                'score'    => 0,
                'category' => 'No Risk',
                'reasons'  => ['Group size unknown — no contagion assessment possible.'],
            ];
        }


        $defaulterMembers = 0;
        $totalMemberArrears = 0.0;
        $multiGroupDefaulters = 0;

        if ($applicationMembers && $applicationMembers->isNotEmpty()) {
            foreach ($applicationMembers as $gm) {
                if (!($gm->is_defaulter ?? false)) {
                    continue;
                }

                $defaulterMembers++;

                $history = $gm->default_history ?? collect();

                $memberArrears = (float) $history->sum('default_arrears');
                $totalMemberArrears += $memberArrears;

                $uniqueDefaultGroups = $history->pluck('groupLoan.group.name')->filter()->unique()->count();
                if ($uniqueDefaultGroups > 1) {
                    $multiGroupDefaulters++;
                }

                // Detailed per-member reason
                if ($memberArrears > 0) {
                    $reasons[] = "Member {$gm->name} (ID: {$gm->id_number}) has KES " .
                        number_format($memberArrears) . " external arrears across {$uniqueDefaultGroups} group(s).";
                }

            }
        } else {
            $defaulterMembers = $group->currentLoan?->defaulter_members_count ?? 0;
        }

        $ratio = $defaulterMembers / max(1, $totalMembers);
        $pctStr = number_format($ratio * 100, 1) . '%';

        $avgArrearsPerDef = $defaulterMembers > 0 ? $totalMemberArrears / $defaulterMembers : 0;
        $severityThresholds = $cfg['contagion']['severity_thresholds_kes'] ?? [50000, 150000, 400000];
        $severityMult = match (true) {
            $avgArrearsPerDef > $severityThresholds[2] => $cfg['contagion']['severity_multipliers']['extreme'] ?? 2.5,
            $avgArrearsPerDef > $severityThresholds[1] => $cfg['contagion']['severity_multipliers']['high']    ?? 2.0,
            $avgArrearsPerDef > $severityThresholds[0] => $cfg['contagion']['severity_multipliers']['medium'] ?? 1.5,
            default                                    => $cfg['contagion']['severity_multipliers']['low']    ?? 1.0,
        };

        $thresh = $cfg['thresholds']['contagion'];

        // Group size adjustment — small groups more vulnerable
        $sizeFactor = 1.0;

        if ($totalMembers <= ($thresh['small_group_threshold'] ?? 15)) {
            $sizeFactor = $cfg['contagion']['size_penalty_factor'] ?? 1.4;
        }

        if ($defaulterMembers >= ($thresh['critical_members'] ?? 5) ||
            $ratio >= ($thresh['critical_percentage'] ?? 0.30)) {
            $score = 100;
            $reasons[] = "Critical contagion: {$defaulterMembers}/{$totalMembers} ({$pctStr}) external defaulters.";
        } elseif ($defaulterMembers >= $thresh['high_members'] ||
                $ratio >= $thresh['high_percentage']) {
            $base = $cfg['contagion']['base_score_on_trigger'] ?? 40;
            $score = min(100, $base + (60 * $ratio * $sizeFactor));
            $score *= $severityMult;
            $reasons[] = "High contagion risk: {$defaulterMembers}/{$totalMembers} ({$pctStr}), avg KES " .
                number_format($avgArrearsPerDef) . " external arrears each.";
        } elseif ($defaulterMembers >= $thresh['warning_members'] ||
                $ratio >= $thresh['warning_percentage']) {
            $base = $cfg['contagion']['base_score_on_trigger'] ?? 40;
            $score = min(70, $base * $ratio * $sizeFactor * $severityMult); // cap medium at ~70
            $reasons[] = "Contagion warning: {$defaulterMembers}/{$totalMembers} ({$pctStr}) external defaulters.";
        }

        // Multi-group uplift
        if ($multiGroupDefaulters > 0) {
            $multiBonus = min(
                $cfg['contagion']['multi_group_bonus_max'] ?? 30,
                $multiGroupDefaulters * ($cfg['contagion']['multi_group_bonus_per_unique'] ?? 8)
            );
            $score = min(100, $score + $multiBonus);
            $reasons[] = "{$multiGroupDefaulters} members with defaults in multiple groups → +{$multiBonus} risk uplift.";
        }

        $score = min(100, round($score, 2));

        $category = $score >= $cfg['categories']['high'] ? 'High Risk' :
                    ($score > 0 ? 'Medium Risk' : 'No Risk');

        // Add overall summary reason
        if ($defaulterMembers > 0) {
            $reasons[] = "Overall: {$defaulterMembers}/{$totalMembers} ({$pctStr}) members with external defaults — contagion score {$score}.";
        }

        return [
            'score'    => (float) $score,
            'category' => $category,
            'reasons'  => array_unique($reasons),
        ];
    }

    /**
     * Historical Risk (Past defaulted loans in this group)
     */
    private function calculateHistoricalRisk($group, $cfg): array
    {
        $loans = $group->groupLoans ?? collect();

        if ($loans->isEmpty()) {
            return [
                'score'    => 0,
                'category' => 'Low Risk',
                'reasons'  => [],
            ];
        }

        $cfgHist = $cfg['historical'] ?? [];
        $now = now();

        $totalScore = 0.0;
        $defaultedCount = 0;
        $maxMonthsDefaulted = 0;
        $reasons = [];

        foreach ($loans as $loan) {
            $disbursement = $loan->disbursement_date;
            if (!$disbursement) {
                continue; // skip missing date
            }

            $monthsDefaulted = (int) ($loan->months_defaulted ?? 0);
            if ($monthsDefaulted <= 0) {
                continue;
            }

            $defaultedCount++;

            $maxMonthsDefaulted = max($maxMonthsDefaulted, $monthsDefaulted);

            $ageMonths = $now->diffInMonths($disbursement);
            $halfLife = $cfgHist['half_life_months'] ?? 36;
            $recencyFactor = exp(-$ageMonths / $halfLife);
            $recencyFactor = max($cfgHist['recency_floor'] ?? 0.3, $recencyFactor);

            // Severity (capped naturally by max 24 months)
            $monthsPerPoint = $cfgHist['months_per_point'] ?? 4.1667;
            $severity = min(
                $cfgHist['severity_cap_per_loan'] ?? 100,
                $monthsDefaulted * $monthsPerPoint
            );

            $contribution = $severity * $recencyFactor;
            $totalScore += $contribution;

            // Reason (keep concise)
            $cycleLabel = ($loan->is_current ?? false) ? 'current' : 'past';
            $reasons[] = "{$loan->loanPhase->phase} (disbursed ~{$ageMonths} months ago) defaulted {$monthsDefaulted}/24 months ({$cycleLabel}).";
        }

        $totalScore *= ($cfgHist['base_weight'] ?? 1.0);
        $totalScore = min(100, round($totalScore, 1));

        $this->getRiskCategory($totalScore);

        $category = $this->getRiskCategory($totalScore)['label'];

        $summary = [];

        if ($defaultedCount > 0) {
            $summary[] = "{$defaultedCount} defaulted cycles (max default duration: {$maxMonthsDefaulted}/24 months). score: {$totalScore}";
            if ($totalScore >= 60) {
                $summary[] = "Persistent historical default risk present.";
            }
        }

        return [
            'score'    => (float) $totalScore,
            'category' => $category,
            'reasons'  => array_merge($summary, $reasons),
        ];
    }

    /**
     * Member Exposure Risk (Severity of external defaults among defaulter members)
     * Measures average arrears exposure per defaulter member
     * Uses member-specific default_arrears where possible
     */
    private function calculateMemberExposureRisk($applicationMembers, $cfg): array
    {
        if (!$applicationMembers || $applicationMembers->isEmpty()) {
            return ['score' => 0, 'category' => 'No Risk', 'reasons' => []];
        }

        $defaulterMembers = 0;
        $totalMemberSpecificArrears = 0.0;

        foreach ($applicationMembers as $gm) {
            if (!($gm->is_defaulter ?? false)) {
                continue;
            }

            $defaulterMembers++;

            $history = $gm->default_history ?? collect();

            // Prefer member-specific arrears if available (recommended)
            $memberArrears = $history->sum(fn($item) => (float) ($item->default_arrears ?? 0));

            // Fallback: if default_arrears missing, use group total arrears (with warning note)
            if ($memberArrears == 0 && $history->isNotEmpty()) {
                $memberArrears = $history->sum(fn($item) => (float) ($item->groupLoan->total_arrears ?? 0));
            }

            $totalMemberSpecificArrears += $memberArrears;
        }

        if ($defaulterMembers === 0) {
            return ['score' => 0, 'category' => 'No Risk', 'reasons' => []];
        }

        $avgArrears = $totalMemberSpecificArrears / $defaulterMembers;

        // Use config buckets if available
        $buckets = $cfg['member_exposure']['buckets'] ?? [];
        $score = 0;
        $severity = 'Low';
        $category = 'Low Risk';

        if (!empty($buckets)) {
            foreach ($buckets as $bucket) {
                if ($avgArrears >= ($bucket['min_avg_kes'] ?? 0)) {
                    $score    = $bucket['score']    ?? $score;
                    $severity = $bucket['severity'] ?? $severity;
                    $category = $bucket['category'] ?? $category;
                    break;
                }
            }
        } else {
            // Fallback linear scaling if no buckets
            $defaultPer100k = $cfg['member_exposure']['default_score_per_100k'] ?? 25;
            $score = min(100, ($avgArrears / 100000) * $defaultPer100k);
        }

        // Final safety: round and cap
        $score = min(100, round($score, 2));

        $category = $score >= ($cfg['categories']['high'] ?? 70) ? 'High Risk' :
                    ($score >= ($cfg['categories']['medium'] ?? 30) ? 'Medium Risk' : 'Low Risk');

        // Reasons — make informative
        // $reasons = [
        //     "Avg external arrears per defaulter member: KES " . number_format($avgArrears) .
        //     " (across {$defaulterMembers} defaulter(s))"
        // ];

        $reasons = [];

        if ($severity !== 'Low') {
            $reasons[] = "Exposure severity: {$severity} — {$score} points";
        }

        return [
            'score'    => (float) $score,
            'category' => $category,
            'reasons'  => $reasons,
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
    public function getApplicationDefaulterMembers(array $idNumbers, $applicationId, ?int $excludeGroupId = null, ?string $constituencyCode = '', ?int $phaseId = null): Collection
    {
        if (empty($idNumbers)) {
            return collect();
        }

        return $this->appCache->rememberApp(
            $applicationId,
            'default_members',
            function() use ($idNumbers, $excludeGroupId, $constituencyCode, $phaseId) {
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
                        'amount_issued',
                        'disbursement_date',
                    ),
                    'groupMembers.groupLoan.group' => fn($q) => $q->select('id', 'name', 'ward_id', 'slug'),
                    'groupMembers.groupLoan.group.ward' => fn($q) => $q->select('id', 'ward_name', 'constituency_code'),
                    'groupMembers.groupLoan.group.ward.constituency',
                    'groupMembers.groupLoan.loanPhase' => fn($q) => $q->select('id', 'phase')
                    ])
                ->get()
                ->keyBy('member_Id');
            },
            $constituencyCode,
            $phaseId,
            10
        );
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


}
