<?php

namespace App\Services\Loan;

use Carbon\Carbon;
use App\Models\GroupLoan;
use App\Models\Group;
use App\Models\Constituency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

    public function analyzeGroupLoans(Collection $groupLoans): void
    {
        $runningOverpayment = 0.0;
        $today = Carbon::now();

        foreach ($groupLoans as $loan) {
            $result = $this->calculateLoanMetrics($loan, $today);

            $rawArrears = max($result['amount_due'] - $loan->amount_repaid, 0);

            // Apply running overpayment to reduce arrears
            $adjustedArrears = max($rawArrears - $runningOverpayment, 0);

            $isDefaulted = $adjustedArrears > 0;
            $monthsDefaulted = $isDefaulted ? floor($adjustedArrears / $result['monthly_installment']) : 0;

            $excessPayment = max($loan->amount_repaid - $result['amount_due'], 0);

            // Update running overpayment for next loans
            $runningOverpayment = max($runningOverpayment - $rawArrears, 0) + $excessPayment;

            // Update loan
            $newStatus = $loan->status;
            if ($adjustedArrears <= 0 && $loan->amount_repaid >= $loan->amount_issued) {
                $newStatus = 'completed';
            } elseif ($loan->status === 'Pending' && $adjustedArrears > 0) {
                $newStatus = 'active';
            }

            $loan->update([
                'is_defaulted' => $isDefaulted,
                'months_defaulted' => $monthsDefaulted,
                'total_arrears' => $adjustedArrears,
                'amount_due' => $result['amount_due'],
                'default_status_updated_at' => now(),
                'status' => $newStatus,
            ]);
        }
    }

    private function calculateLoanMetrics(GroupLoan $loan, Carbon $today): array
    {
        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        $monthlyInstallment = $loan->amount_issued > 0 ? $loan->amount_issued / 24 : 0;

        if ($today->lte($gracePeriodEnd)) {
            // During grace period: no repayment due yet
            $amountDue = 0.0;
            $isStillActive = true;
            $isDefaulted = false;
            $monthsDefaulted = 0;
            $arrears = 0.0;
        } else {
            // After grace period
            $repaymentStart = $gracePeriodEnd->copy()->addDay();
            $monthsInRepayment = min($repaymentStart->diffInMonths($today), 24);
            $amountDue = $monthlyInstallment * $monthsInRepayment;

            $isStillActive = $disbursementDate->diffInMonths($today) <= 30 || $amountDue > 0;
            $isDefaulted = false;
            $monthsDefaulted = 0;
            $arrears = 0.0;
        }

        Log::info("calculateLoanMetrics: {$amountDue} at " . now()->toDateTimeString());

        return $this->formatResult(
            $isStillActive,
            $isDefaulted,
            $amountDue,
            $arrears,
            $monthsDefaulted,
            $monthlyInstallment
        );
    }

    /**
     * Calculate limits and balances for a group.
     */
    public function getEligibilityData(?Group $group, int $requeted = 0): array
    {
        // Default for New Groups
        if (!$group?->id) {
            return [
                'min' => 50000,
                'max' => $requeted > 0 ? min($requeted, 100000) : 100000,
                'balance' => 0,
                'can_borrow' => true
            ];
        }

        // Calculate Global Balance (Total Approved - Total Repaid)
        $balance = ($group->total_issued ?? 0) - ($group->total_repaid ?? 0);

        // Calculate Ceiling (2x Last Issued Amount)
        $lastLoan = $group->currentLoan;

        $maxCeiling = $lastLoan ? ($lastLoan->amount_issued * 2) : 100000;

        //

        $maximum = $requeted > 0 ? min($requeted, $maxCeiling) : min($maxCeiling, 500000);
        return [
            'min' => 50000,
            'max' => $maximum,
            'balance' => $balance,
            'can_borrow' => $balance <= 0 // Eligibility check
        ];
    }

    public function getConstituencyStats(Constituency $constituency)
    {
        $cacheKey = "const_stats_v3_{$constituency->constituency_code}";

        return Cache::remember($cacheKey, 300, function () use ($constituency) {
            $code = $constituency->constituency_code;

            // Base query for this constituency's loans
            $baseQuery = GroupLoan::whereHas('loanPhase', function ($q) use ($code) {
                $q->where('constituency_code', $code);
            });

            // Aggregate Financial Totals (Summing the columns directly in SQL)
            $totals = (clone $baseQuery)->where('loan_status', 'financed')
                ->selectRaw('
                    SUM(amount_approved) as approved,
                    SUM(amount_issued) as issued,
                    SUM(amount_repaid) as repaid,
                    SUM(amount_due) as due,
                    SUM(total_arrears) as arrears,
                    COUNT(CASE WHEN is_defaulted = 1 THEN 1 END) as default_count,
                    COUNT(*) as active_count,
                    AVG(months_defaulted) as avg_months_defaulted
                ')->first();

            // Category Breakdown using DB for speed
            $categories = DB::table('group_loans')
                ->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
                ->where('loan_phases.constituency_code', $code)
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category');

            $repaid = $totals->repaid ?? 0;
            $due = $totals->due ?? 0;
            $issued = $totals->issued ?? 0;

            return [
                'totalGroups' => $constituency->groups()->count(),
                'totalAmountApproved' => $totals->approved ?? 0,
                'totalAmountIssued' => $totals->issued ?? 0,
                'totalAmountRepaid' => $totals->repaid ?? 0,
                'totalAmountDue' => $totals->due ?? 0,
                'totalArrears' => $totals->arrears ?? 0,
                'activeLoansCount' => $totals->active_count ?? 0,
                'defaultedLoansCount' => $totals->default_count ?? 0,
                'averageDefaultMonths' => round($totals->avg_months_defaulted ?? 0, 1),

                'repaymentPerformanceRate' => $due > 0 ? round(($repaid / $due) * 100, 1) : 0,

                'repaymentProgressRate' => $issued > 0 ? round(($repaid / $issued) * 100, 1) : 0,

                'defaultRate' => ($totals->active_count ?? 0) > 0
                    ? round(($totals->default_count / $totals->active_count) * 100, 1)
                    : 0,

                'womenGroups' => $categories['Women'] ?? 0,
                'youthGroups' => $categories['Youth'] ?? 0,
                'pwdGroups' => $categories['PWD'] ?? 0,

                'pendingApplications' => (clone $baseQuery)->where('loan_status', 'pending')->count(),
                'recentApplications' => (clone $baseQuery)->with('group')->latest()->limit(5)->get(),
            ];
        });
    }
}
