<?php

namespace App\Services\Loan;

use Carbon\Carbon;
use App\Models\GroupLoan;

class LoanAnalysisService
{
    public function analyzeSingleLoan(GroupLoan $loan)
    {
        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $today = Carbon::now();
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        // 1. Grace Period Check
        if ($today->lte($gracePeriodEnd)) {
            return $this->formatResult(true, false, 0, 0, 0, $loan->amount_issued / 24);
        }

        // 2. Repayment Math
        $repaymentStart = $gracePeriodEnd->copy()->addDay();
        $monthsInRepayment = min($repaymentStart->diffInMonths($today), 24);
        $monthlyInstallment = $loan->amount_issued / 24;

        $amountDue = $monthlyInstallment * $monthsInRepayment;
        $arrears = max($amountDue - $loan->amount_repaid, 0);

        $isDefaulted = $arrears > 0;
        $monthsDefaulted = $isDefaulted ? floor($arrears / $monthlyInstallment) : 0;

        // Active if within 30 months (6 grace + 24 repayment) OR still has arrears
        $isActive = $disbursementDate->diffInMonths($today) <= 30 || $arrears > 0;

        return $this->formatResult($isActive, $isDefaulted, $amountDue, $arrears, $monthsDefaulted, $monthlyInstallment);
    }

    private function formatResult($isActive, $isDefaulted, $due, $arrears, $months, $installment)
    {
        return [
            'is_active' => $isActive,
            'is_defaulted' => $isDefaulted,
            'amount_due' => $due,
            'arrears' => $arrears,
            'months_defaulted' => $months,
            'monthly_installment' => $installment,
        ];
    }
}
