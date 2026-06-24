<?php

namespace App\Services\Loan;

use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use Illuminate\Support\Collection;

class LoanDebtCheckService
{
    public const REPAYMENT_THRESHOLD_PERCENT = 80;

    public const ENDELEZA_MIN_AMOUNT = 75000;

    public const WEZESHA_MIN_AMOUNT = 50000;

    public const WEZESHA_MAX_AMOUNT = 100000;

    public const ENDELEZA_MAX_CEILING = 500000;

    public function checkMemberByNationalId(string $nationalId): array
    {
        $normalizedId = $this->normalizeNationalId($nationalId);
        if ($normalizedId === '') {
            return $this->emptyResult();
        }

        $groupLoanIds = GroupMember::query()
            ->where('member_Id', $normalizedId)
            ->pluck('group_loans_id');

        if ($groupLoanIds->isEmpty()) {
            return $this->emptyResult();
        }

        $loan = $this->pickBlockingLoan(
            GroupLoan::query()
                ->whereIn('id', $groupLoanIds)
                ->where('loan_status', 'financed')
                ->with(['group.ward.constituency', 'groupMembers.member'])
                ->orderByDesc('disbursement_date')
                ->get(),
        );

        if (!$loan) {
            return $this->emptyResult();
        }

        return $this->formatDebtResult($loan, 'member', $normalizedId);
    }

    public function checkGroupByRegistrationNumber(string $regNumber): array
    {
        return $this->getGroupEligibilityByRegistrationNumber($regNumber);
    }

    public function getGroupEligibilityByRegistrationNumber(string $regNumber): array
    {
        $normalized = $this->normalizeRegNumber($regNumber);
        if ($normalized === '') {
            return $this->emptyEligibilityResult();
        }

        $groups = Group::query()
            ->whereRaw('UPPER(TRIM(reg_number)) = ?', [$normalized])
            ->get();

        if ($groups->isEmpty()) {
            return $this->emptyEligibilityResult($normalized);
        }

        $groupIds = $groups->pluck('id');
        $primaryGroup = $groups->first();

        $latestLoan = GroupLoan::query()
            ->whereIn('group_id', $groupIds)
            ->where('loan_status', 'financed')
            ->with(['group.ward.constituency', 'groupMembers.member'])
            ->orderByDesc('disbursement_date')
            ->orderByDesc('id')
            ->first();

        if (!$latestLoan) {
            return array_merge($this->emptyEligibilityResult($normalized), [
                'group_found' => true,
                'group_id' => $primaryGroup->id,
                'group_name' => $primaryGroup->name,
                'group_registration_number' => $primaryGroup->reg_number ?? $normalized,
                'eligible_for_wezesha' => true,
            ]);
        }

        $status = $this->getDebtStatusForGroupLoan($latestLoan);
        $loanCleared = $status['outstanding_balance'] <= 0;
        $hasBlockingDebt = $status['blocks_proceed'];
        $lastIssued = (float) $latestLoan->amount_issued;
        $endelezaMax = (int) min(max($lastIssued * 2, self::ENDELEZA_MIN_AMOUNT), self::ENDELEZA_MAX_CEILING);

        $base = $this->formatDebtResult($latestLoan, 'group', $normalized);

        return array_merge($base, [
            'group_found' => true,
            'has_prior_financed_loan' => true,
            'loan_cleared' => $loanCleared,
            'eligible_for_endeleza' => $loanCleared && !$hasBlockingDebt,
            'eligible_for_wezesha' => !$hasBlockingDebt && !$loanCleared,
            'endeleza_min_amount' => self::ENDELEZA_MIN_AMOUNT,
            'endeleza_max_amount' => $endelezaMax,
            'wezesha_min_amount' => self::WEZESHA_MIN_AMOUNT,
            'wezesha_max_amount' => self::WEZESHA_MAX_AMOUNT,
        ]);
    }

    public function getDebtStatusForGroupLoan(GroupLoan $loan): array
    {
        $issued = (float) $loan->amount_issued;
        $repaid = (float) $loan->amount_repaid;
        $outstanding = max($issued - $repaid, 0);
        $repaymentPercentage = $issued > 0 ? round(($repaid / $issued) * 100, 2) : 100;
        $minimumPaymentRequired = max(0, round(($issued * (self::REPAYMENT_THRESHOLD_PERCENT / 100)) - $repaid, 2));
        $blocksProceed = $outstanding > 0 && $repaymentPercentage < self::REPAYMENT_THRESHOLD_PERCENT;

        return [
            'amount_issued' => $issued,
            'amount_repaid' => $repaid,
            'outstanding_balance' => $outstanding,
            'repayment_percentage' => $repaymentPercentage,
            'minimum_payment_required' => $minimumPaymentRequired,
            'blocks_proceed' => $blocksProceed,
            'can_proceed' => !$blocksProceed,
        ];
    }

    public function recordPortalRepayment(
        int $groupLoanId,
        float $amount,
        string $referenceNumber,
        string $billRefNumber,
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Repayment amount must be greater than zero');
        }

        $existingReceipt = \App\Models\LoanReceipts::query()
            ->where('reference_number', $referenceNumber)
            ->orWhere('receipt_number', $billRefNumber)
            ->first();

        if ($existingReceipt) {
            $loan = GroupLoan::with('group')->findOrFail($groupLoanId);

            return $this->formatDebtResult($loan, 'repayment', $referenceNumber);
        }

        $groupLoan = GroupLoan::query()
            ->lockForUpdate()
            ->with(['group.ward.constituency', 'groupMembers.member'])
            ->findOrFail($groupLoanId);

        $oldAmount = (float) $groupLoan->amount_repaid;
        $newAmount = $oldAmount + $amount;

        $groupLoan->update([
            'amount_repaid' => $newAmount,
        ]);

        \App\Models\LoanReceipts::create([
            'group_loans_id' => $groupLoan->id,
            'receipt_number' => $billRefNumber,
            'transaction_date' => now()->toDateString(),
            'amount' => $amount,
            'payment' => 'Pesaflow',
            'reference_number' => $referenceNumber,
            'status' => 'active',
        ]);

        if ($groupLoan->group_id) {
            \App\Jobs\UpdateLoanDefaultStatus::dispatch($groupLoan->group_id)
                ->onQueue('financial-updates')
                ->afterCommit();
        }

        $groupLoan->refresh();

        return $this->formatDebtResult($groupLoan, 'repayment', $referenceNumber);
    }

    private function normalizeNationalId(string $nationalId): string
    {
        return preg_replace('/\s+/', '', trim($nationalId)) ?? '';
    }

    private function normalizeRegNumber(string $regNumber): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $regNumber) ?? '');

        return strtoupper($value);
    }

    private function pickBlockingLoan(Collection $loans): ?GroupLoan
    {
        foreach ($loans as $loan) {
            $status = $this->getDebtStatusForGroupLoan($loan);
            if ($status['blocks_proceed']) {
                return $loan;
            }
        }

        return null;
    }

    private function formatDebtResult(GroupLoan $loan, string $checkType, string $matchedValue): array
    {
        $status = $this->getDebtStatusForGroupLoan($loan);
        $group = $loan->group;

        $members = $loan->groupMembers
            ->map(function (GroupMember $groupMember) {
                $member = $groupMember->member;

                return [
                    'member_id' => $groupMember->member_Id,
                    'name' => $member?->name,
                    'phone' => $member?->number,
                    'role' => $groupMember->role,
                ];
            })
            ->values()
            ->all();

        return [
            'has_debt' => $status['blocks_proceed'],
            'can_proceed' => $status['can_proceed'],
            'check_type' => $checkType,
            'matched_value' => $matchedValue,
            'group_loan_id' => $loan->id,
            'group_id' => $loan->group_id,
            'group_name' => $group?->name,
            'group_registration_number' => $group?->reg_number,
            'constituency_name' => $group?->ward?->constituency?->constituency_name,
            'loan_type' => $loan->loan_type,
            'amount_issued' => $status['amount_issued'],
            'amount_repaid' => $status['amount_repaid'],
            'outstanding_balance' => $status['outstanding_balance'],
            'repayment_percentage' => $status['repayment_percentage'],
            'minimum_payment_required' => $status['minimum_payment_required'],
            'repayment_threshold_percent' => self::REPAYMENT_THRESHOLD_PERCENT,
            'members' => $members,
        ];
    }

    private function emptyResult(): array
    {
        return $this->emptyEligibilityResult();
    }

    private function emptyEligibilityResult(?string $matchedValue = null): array
    {
        return [
            'has_debt' => false,
            'can_proceed' => true,
            'check_type' => null,
            'matched_value' => $matchedValue,
            'group_loan_id' => null,
            'group_id' => null,
            'group_name' => null,
            'group_registration_number' => $matchedValue,
            'constituency_name' => null,
            'loan_type' => null,
            'amount_issued' => 0,
            'amount_repaid' => 0,
            'outstanding_balance' => 0,
            'repayment_percentage' => 100,
            'minimum_payment_required' => 0,
            'repayment_threshold_percent' => self::REPAYMENT_THRESHOLD_PERCENT,
            'members' => [],
            'group_found' => false,
            'has_prior_financed_loan' => false,
            'loan_cleared' => false,
            'eligible_for_endeleza' => false,
            'eligible_for_wezesha' => true,
            'endeleza_min_amount' => self::ENDELEZA_MIN_AMOUNT,
            'endeleza_max_amount' => self::ENDELEZA_MAX_CEILING,
            'wezesha_min_amount' => self::WEZESHA_MIN_AMOUNT,
            'wezesha_max_amount' => self::WEZESHA_MAX_AMOUNT,
        ];
    }
}
