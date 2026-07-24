<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class LoanSubmissionFormCalculator
{
    public const LOAN_TYPE_ORDER = ['New', 'Resubmission', 'Refinancing', 'Reallocation'];

    public function analyzeLoan($loan): array
    {
        if (!$loan->disbursement_date || $loan->loan_status === 'cancelled') {
            return [
                'amount_due' => 0,
                'arrears' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => 0,
            ];
        }

        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $today = Carbon::now();
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        if ($today->lte($gracePeriodEnd)) {
            return [
                'amount_due' => 0,
                'arrears' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => ($loan->amount_issued ?: 0) / 24,
            ];
        }

        $repaymentStart = $gracePeriodEnd->copy()->addDay();
        $monthsInRepayment = $repaymentStart->diffInMonths($today);
        $monthsInRepaymentForDue = min($monthsInRepayment, 24);
        $monthlyInstallment = ($loan->amount_issued ?: 0) / 24;
        $amountDue = $monthlyInstallment * $monthsInRepaymentForDue;
        $amountRepaid = $loan->amount_repaid ?: 0;
        $arrears = max($amountDue - $amountRepaid, 0);

        return [
            'amount_due' => $amountDue,
            'arrears' => $arrears,
            'months_in_repayment' => $monthsInRepaymentForDue,
            'monthly_installment' => $monthlyInstallment,
        ];
    }

    public function repaymentCompletionDate($loan): ?string
    {
        if (!$loan->disbursement_date || $loan->loan_status === 'cancelled') {
            return null;
        }

        return Carbon::parse($loan->disbursement_date)->addMonths(30)->format('Y-m-d');
    }

    public function outstandingBalance($loan): float
    {
        return ($loan->amount_issued ?: 0) - ($loan->amount_repaid ?: 0);
    }

    public function variance($loan): float
    {
        return ($loan->amount_approved ?: 0) - ($loan->amount_issued ?: 0);
    }

    public function resolveLoanType($loan): ?string
    {
        $loanType = trim((string) ($loan->loan_type ?? ''));

        if ($loanType !== '') {
            return $loanType;
        }

        $legacyLevel = trim((string) ($loan->level ?? ''));

        return $legacyLevel !== '' ? $legacyLevel : null;
    }

    public function decorateLoan($loan): mixed
    {
        $analysis = $this->analyzeLoan($loan);
        $loan->resolved_loan_type = $this->resolveLoanType($loan);
        $loan->variance = $this->variance($loan);
        $loan->amount_due = $analysis['amount_due'];
        $loan->arrears = $analysis['arrears'];
        $loan->outstanding_balance = $this->outstandingBalance($loan);
        $loan->repayment_completion_date = $this->repaymentCompletionDate($loan);
        $loan->analysis = $analysis;

        return $loan;
    }

    public function sortPhases($phases)
    {
        return $phases->sortBy(fn ($phase) => $this->phaseSortKey($phase->phase))->values();
    }

    public function preparePhases($phases)
    {
        return $this->sortPhases($phases)->map(function ($phase) {
            $phase->grouploans = $phase->grouploans
                ->whereIn('loan_status', ['financed', 'cancelled'])
                ->sortBy(function ($grouploan) {
                    $loanType = $this->resolveLoanType($grouploan);
                    $index = array_search($loanType, self::LOAN_TYPE_ORDER, true);

                    return $index === false ? count(self::LOAN_TYPE_ORDER) : $index;
                })
                ->values()
                ->map(fn ($grouploan) => $this->decorateLoan($grouploan));

            return $phase;
        });
    }

    private function phaseSortKey(string $phaseName): string
    {
        if (preg_match('/(\d+)/', $phaseName, $matches)) {
            return str_pad($matches[1], 4, '0', STR_PAD_LEFT) . strtolower($phaseName);
        }

        return '9999' . strtolower($phaseName);
    }
}
