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
        $groupId = $groupLoans->first()?->group_id ?? 'unknown';
        Log::info("Starting group analysis for group {$groupId} - {$groupLoans->count()} financed loans");

        $runningOverpayment = 0.0;
        $today = Carbon::now();

        // Filter out any financed loans that still have NULL disbursement_date
        $validLoans = $groupLoans->filter(function ($loan) {
            if ($loan->disbursement_date === null) {
                Log::warning("Financed loan {$loan->id} has NULL disbursement_date - treating as latest");
                return true; // keep it, but it will go last
            }
            return true;
        });

        // Sort: valid dates first (oldest to newest), NULL dates at the very end
        $sortedLoans = $validLoans->sortBy(function ($loan) {
            return $loan->disbursement_date
                ? Carbon::parse($loan->disbursement_date)->timestamp
                : PHP_INT_MAX;
        });

        $processed = 0;

        foreach ($sortedLoans as $loan) {
            $processed++;

            $result = $this->calculateLoanMetrics($loan, $today);

            $rawArrears = max($result['amount_due'] - $loan->amount_repaid, 0);

            // Apply cumulative overpayment (only if we have a valid date)
            $adjustedArrears = $loan->disbursement_date === null
                ? $rawArrears  // No offset for NULL-date loans
                : max($rawArrears - $runningOverpayment, 0);

            $isDefaulted = $adjustedArrears > 0;
            $monthsDefaulted = $isDefaulted ? floor($adjustedArrears / max($result['monthly_installment'], 1)) : 0;

            $excessPayment = max($loan->amount_repaid - $result['amount_due'], 0);

            // Carry forward overpayment even from NULL-date loans
            $runningOverpayment = max($runningOverpayment - $rawArrears, 0) + $excessPayment;

            // Force clean slate if fully repaid
            if ($loan->amount_repaid >= $loan->amount_issued) {
                $adjustedArrears = 0;
                $isDefaulted = false;
                $monthsDefaulted = 0;
            }

            $finalAmountDue = $result['amount_due'];

            $newStatus = $loan->status;
            if ($adjustedArrears <= 0 && $loan->amount_repaid >= $loan->amount_issued) {
                $newStatus = 'completed';
            } elseif ($adjustedArrears > 0) {
                $newStatus = 'defaulted';
            } elseif ($loan->status === 'Pending') {
                $newStatus = 'active';
            }

            $loan->update([
                'is_defaulted' => $isDefaulted,
                'months_defaulted' => $monthsDefaulted,
                'total_arrears' => $adjustedArrears,
                'amount_due' => $finalAmountDue,
                'default_status_updated_at' => now(),
                'status' => $newStatus,
            ]);

            Log::info("Loan {$loan->id} updated: due={$finalAmountDue}, arrears={$adjustedArrears}, status={$newStatus}, running_overpay={$runningOverpayment}" . ($loan->disbursement_date === null ? ' (NULL date)' : ''));
        }

        Log::info("Finished group {$groupId} analysis - {$processed} loans processed");
    }

    private function calculateLoanMetrics(GroupLoan $loan, Carbon $today): array
    {
        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        $monthlyInstallment = $loan->amount_issued > 0 ? $loan->amount_issued / 24 : 0;

        if ($today->lte($gracePeriodEnd)) {
            // Grace period: no repayment due
            $amountDue = 0.0;
            $isStillActive = true;
            $isDefaulted = false;
            $monthsDefaulted = 0;
            $arrears = 0.0;
        } else {
            // Repayment period
            $repaymentStart = $gracePeriodEnd->copy()->addDay();
            $monthsElapsed = $repaymentStart->diffInMonths($today);
            $theoreticalDue = $monthlyInstallment * min($monthsElapsed, 24);

            // No cap on theoretical due
            $amountDue = $theoreticalDue;

            $isStillActive = $disbursementDate->diffInMonths($today) <= 30 || $theoreticalDue > $loan->amount_repaid;
            $isDefaulted = false; // Calculated later with overpayment
            $monthsDefaulted = 0;
            $arrears = 0.0;
        }

        return $this->formatResult(
            $isStillActive,
            $isDefaulted,
            $amountDue,
            0.0, // arrears calculated later
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

            $categoryData = DB::table('group_loans')
                ->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
                ->where('loan_phases.constituency_code', $code)
                ->where('group_loans.loan_status', 'financed')
                ->select(
                    'category',
                    DB::raw('count(*) as loan_count'),
                    DB::raw('SUM(amount_issued) as total_issued')
                )
                ->groupBy('category')
                ->get()
                ->keyBy('category');

            $sectorData = DB::table('group_loans')
                ->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
                ->where('loan_phases.constituency_code', $code)
                ->where('group_loans.loan_status', 'financed')
                ->select(
                    'sector',
                    DB::raw('count(*) as loan_count'),
                    DB::raw('SUM(amount_issued) as total_issued'))
                ->groupBy('sector')
                ->get()
                ->keyBy('sector');

            $repaid = $totals->repaid ?? 0;
            $due = $totals->due ?? 0;
            $issued = $totals->issued ?? 0;

            $newApplications = DB::table('temp_applications')
                ->where('constituency_code', $code)
                ->whereIn('status', ['submitted'])
                ->select(
                    'application_id',
                    'group_name',
                    'loan_amount',
                    'created_at',
                    'nature_of_group',
                    'business_type',
                    'application_id',
                    'key_activities'
                )
                ->latest()
                ->limit(5)
                ->get();

            $approvedPhases = DB::table('loan_phases')
                ->where('constituency_code', $code)
                ->where('status', 'approved')
                ->select(
                    'phase_ref',
                    'slug',
                    'constituency_code',
                    'phase',
                    'total_amount_approved'
                )
                ->latest('hq_approved_at')
                ->limit(5)
                ->get();

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

                'recoveryRate' => $due > 0 ? round(($repaid / $due) * 100, 1) : 0,

                'repaymentProgressRate' => $issued > 0 ? round(($repaid / $issued) * 100, 1) : 0,

                'defaultRate' => ($totals->active_count ?? 0) > 0
                    ? round(($totals->default_count / $totals->active_count) * 100, 1)
                    : 0,

                'financialDefaultRate' => $totals->due > 0
                    ? round((max($totals->due - $totals->repaid, 0) / $totals->due) * 100, 1)
                    : 0,

                'averageArrearsPerDefaultedLoan' => $totals->default_count > 0
                    ? round($totals->arrears / $totals->default_count, 0)
                    : 0,

                'sector_breakdown' => $sectorData,

                'womenGroups' => $categoryData['Women']->loan_count ?? 0,
                'womenAmount' => $categoryData['Women']->total_issued ?? 0,

                'youthGroups' => $categoryData['Youth']->loan_count ?? 0,
                'youthAmount' => $categoryData['Youth']->total_issued ?? 0,

                'pwdGroups'   => $categoryData['PWD']->loan_count ?? 0,
                'pwdAmount'   => $categoryData['PWD']->total_issued ?? 0,

                'newApplications' => $newApplications,

                'approvedPhases' => $approvedPhases,
            ];
        });
    }
}
