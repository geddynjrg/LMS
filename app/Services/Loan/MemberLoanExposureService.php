<?php

namespace App\Services\Loan;

use App\Models\GroupMember;
use App\Services\Cache\ApplicationCacheService;
use Illuminate\Support\Collection;

class MemberLoanExposureService
{
    public function __construct(
        protected LoanDebtCheckService $debtCheckService,
        protected ApplicationCacheService $appCache,
    ) {
    }

    /**
     * Find financed loans in other groups for the given national IDs (all constituencies).
     *
     * @return Collection<string, array{has_other_group_loan: bool, exposures: Collection, external_fund_checks: array}>
     */
    public function getExposuresForMembers(
        array $nationalIds,
        ?int $excludeGroupId = null,
        ?string $applicationId = null,
        ?string $constituencyCode = null,
        ?int $phaseId = null,
    ): Collection {
        $nationalIds = array_values(array_filter(array_unique($nationalIds)));

        if ($nationalIds === []) {
            return collect();
        }

        $loader = fn () => $this->loadExposures($nationalIds, $excludeGroupId);

        if ($applicationId) {
            return $this->appCache->rememberApp(
                $applicationId,
                'member_loan_exposure',
                $loader,
                $constituencyCode,
                $phaseId,
                10,
            );
        }

        return $loader();
    }

    /**
     * @return Collection<string, array{has_other_group_loan: bool, exposures: Collection, external_fund_checks: array}>
     */
    private function loadExposures(array $nationalIds, ?int $excludeGroupId): Collection
    {
        $memberships = GroupMember::query()
            ->whereIn('member_Id', $nationalIds)
            ->whereHas('groupLoan', function ($query) {
                $query->where('loan_status', 'financed')
                    ->where('amount_issued', '>', 0);
            })
            ->with([
                'groupLoan' => fn ($q) => $q->select(
                    'id',
                    'group_id',
                    'loan_phase_id',
                    'loan_status',
                    'amount_issued',
                    'amount_repaid',
                    'total_arrears',
                    'months_defaulted',
                    'is_defaulted',
                    'disbursement_date',
                ),
                'groupLoan.group' => fn ($q) => $q->select('id', 'name', 'reg_number', 'ward_id'),
                'groupLoan.group.ward' => fn ($q) => $q->select('id', 'ward_name', 'constituency_code'),
                'groupLoan.group.ward.constituency' => fn ($q) => $q->select('constituency_code', 'constituency_name', 'county_name'),
                'groupLoan.loanPhase' => fn ($q) => $q->select('id', 'phase'),
            ])
            ->get()
            ->groupBy('member_Id');

        return collect($nationalIds)->mapWithKeys(function (string $nationalId) use ($memberships, $excludeGroupId) {
            $rows = $memberships->get($nationalId, collect());

            $exposures = $rows
                ->filter(function (GroupMember $membership) use ($excludeGroupId) {
                    $groupId = $membership->groupLoan?->group_id;

                    return $groupId && ($excludeGroupId === null || (int) $groupId !== (int) $excludeGroupId);
                })
                ->map(fn (GroupMember $membership) => $this->formatExposure($membership))
                ->values();

            return [
                $nationalId => [
                    'has_other_group_loan' => $exposures->isNotEmpty(),
                    'exposures' => $exposures,
                    'external_fund_checks' => $this->placeholderExternalFundChecks(),
                ],
            ];
        });
    }

    private function formatExposure(GroupMember $membership): array
    {
        $loan = $membership->groupLoan;
        $group = $loan?->group;
        $constituency = $group?->ward?->constituency;
        $debtStatus = $loan ? $this->debtCheckService->getDebtStatusForGroupLoan($loan) : [];
        $isPerforming = $loan
            && !$loan->is_defaulted
            && (
                ($debtStatus['outstanding_balance'] ?? 0) <= 0
                || !($debtStatus['blocks_proceed'] ?? false)
            );

        return [
            'group_member_id' => $membership->id,
            'group_id' => $group?->id,
            'group_name' => $group?->name,
            'group_reg_number' => $group?->reg_number,
            'constituency_code' => $constituency?->constituency_code,
            'constituency_name' => $constituency?->constituency_name,
            'county_name' => $constituency?->county_name,
            'ward_name' => $group?->ward?->ward_name,
            'role' => $membership->role,
            'loan_id' => $loan?->id,
            'loan_phase' => $loan?->loanPhase?->phase,
            'amount_issued' => (float) ($loan?->amount_issued ?? 0),
            'amount_repaid' => (float) ($loan?->amount_repaid ?? 0),
            'outstanding_balance' => (float) ($debtStatus['outstanding_balance'] ?? 0),
            'repayment_percentage' => (float) ($debtStatus['repayment_percentage'] ?? 0),
            'total_arrears' => (float) ($loan?->total_arrears ?? 0),
            'is_defaulted' => (bool) ($loan?->is_defaulted ?? false),
            'is_performing' => $isPerforming,
        ];
    }

    /**
     * Placeholder structure for future integrations (Hustler Fund, WEF, Youth Fund).
     */
    private function placeholderExternalFundChecks(): array
    {
        return [
            'hustler_fund' => null,
            'women_enterprise_fund' => null,
            'youth_fund' => null,
        ];
    }
}
