<?php

namespace App\Services\Loan;

use Carbon\Carbon;
use App\Models\GroupLoan;
use App\Models\Group;

class LoanAnalysisService
{
    public function analyzeSingleLoan(GroupLoan $loan)
    {
        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $today = Carbon::now();
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        // Grace Period Check
        if ($today->lte($gracePeriodEnd)) {
            return $this->formatResult(true, false, 0, 0, 0, $loan->amount_issued / 24);
        }

        // Repayment Math
        $repaymentStart = $gracePeriodEnd->copy()->addDay();
        $monthsInRepayment = min($repaymentStart->diffInMonths($today), 24);
        $monthlyInstallment = $loan->amount_issued / 24;

        $amountDue = $monthlyInstallment * $monthsInRepayment;
        $arrears = max($amountDue - $loan->amount_repaid, 0);

        $isDefaulted = $arrears > 0;
        $monthsDefaulted = $isDefaulted ? floor($arrears / $monthlyInstallment) : 0;

        // Active if within 30 months (6 grace + 24 repayment) OR still has arrears
        $isStillActive = $disbursementDate->diffInMonths($today) <= 30 || $arrears > 0;

        return $this->formatResult($isStillActive, $isDefaulted, $amountDue, $arrears, $monthsDefaulted, $monthlyInstallment);
    }

    private function formatResult($isStillActive, $isDefaulted, $due, $arrears, $months, $installment)
    {
        return [
            'is_active' => $isStillActive,
            'is_defaulted' => $isDefaulted,
            'amount_due' => $due,
            'arrears' => $arrears,
            'months_defaulted' => $months,
            'monthly_installment' => $installment,
        ];
    }

    /**
     * Calculate limits and balances for a group.
     */
    public function getEligibilityData(?Group $group): array
    {
        // Default for New Groups
        if (!$group->id) {
            return [
                'min' => 50000,
                'max' => 100000,
                'balance' => 0,
                'can_borrow' => true
            ];
        }

        // Calculate Global Balance (Total Approved - Total Repaid)
        $balance = ($group->total_issued ?? 0) - ($group->total_repaid ?? 0);

        // Calculate Ceiling (2x Last Issued Amount)
        $lastLoan = $group->currentLoan;

        $maxCeiling = $lastLoan ? ($lastLoan->amount_issued * 2) : 100000;

        $maximum = min($maxCeiling, 500000);
        return [
            'min' => 50000,
            'max' => $maximum,
            'balance' => $balance,
            'can_borrow' => $balance <= 0 // Eligibility check
        ];
    }
}
