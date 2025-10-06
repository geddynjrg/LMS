<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ConstituencyComponent extends Component
{
    // Basic Stats
    public $totalgroups;
    public $totalmembers;
    public $totalAmountIssued;
    public $totalAmountRepaid;
    public $totalAmountApproved;

    // Performance Metrics
    public $repaymentProgressRate;
    public $repaymentPerformanceRate;
    public $activeLoans;
    public $pendingApplications;
    public $outstandingBalance;

    // Category Breakdown
    public $womenGroups;
    public $youthGroups;
    public $pwdGroups;
    public $defaultedLoans;

    // Default Analysis
    public $totalAmountDue;
    public $totalArrears;
    public $averageDefaultMonths;
    public $defaultRate;

    // Recent Activity
    public $recentApplications;

    public Constituency $constituency;

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency->load('county');
        $this->loadCachedData();
    }

    private function loadCachedData()
    {
        $cacheKey = "constituency_dashboard_{$this->constituency->constituency_code}";

        $data = Cache::remember($cacheKey, 300, function() {
            // Load all basic stats in one go to avoid duplicate queries
            $basicStats = $this->loadBasicStats();
            $loanAnalysis = $this->analyzeLoans();

            return array_merge($basicStats, [
                // Performance metrics
                'repaymentProgressRate' => $this->calculateRepaymentProgressRate($basicStats),
                'repaymentPerformanceRate' => $this->calculateRepaymentPerformanceRate($basicStats, $loanAnalysis),
                'activeLoans' => $loanAnalysis['active_loans_count'],
                'pendingApplications' => $this->loadPendingApplicationsCount(),
                'outstandingBalance' => $basicStats['totalAmountIssued'] - $basicStats['totalAmountRepaid'],

                // Category breakdown
                'womenGroups' => $this->loadCategoryCount('Women'),
                'youthGroups' => $this->loadCategoryCount('Youth'),
                'pwdGroups' => $this->loadCategoryCount('PWD'),
                'defaultedLoans' => $loanAnalysis['defaulted_loans_count'],

                // Default analysis
                'totalAmountDue' => $loanAnalysis['total_amount_due'],
                'totalArrears' => $loanAnalysis['total_arrears'],
                'averageDefaultMonths' => $loanAnalysis['average_default_months'],
                'defaultRate' => $loanAnalysis['default_rate'],

                // Recent activity
                'recentApplications' => $this->loadRecentApplications(),
            ]);
        });

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    // NEW: Load all basic stats in one method to avoid duplicate queries
    private function loadBasicStats()
    {
        return [
            'totalgroups' => $this->constituency->groups()->count(),
            'totalmembers' => $this->loadMembersCount(),
            'totalAmountIssued' => $this->loadTotalAmountIssued(),
            'totalAmountRepaid' => $this->loadTotalAmountRepaid(),
            'totalAmountApproved' => $this->loadTotalAmountApproved(),
        ];
    }

    private function analyzeLoans()
    {
        $loans = \App\Models\GroupLoan::whereHas('loanPhase',
            fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
        )->where('loan_status', 'financed')
         ->whereNotNull('disbursement_date')
         ->get();

        $activeLoansCount = 0;
        $defaultedLoansCount = 0;
        $totalAmountDue = 0;
        $totalArrears = 0;
        $totalDefaultMonths = 0;
        $defaultedLoansCountForAverage = 0;

        foreach ($loans as $loan) {
            $analysis = $this->analyzeSingleLoan($loan);

            // Always calculate amount due for ALL loans
            $totalAmountDue += $analysis['amount_due'];

            // Calculate cumulative arrears
            $loanArrears = max($analysis['amount_due'] - $loan->amount_repaid, 0);
            $totalArrears += $loanArrears;

            if ($analysis['is_active']) {
                $activeLoansCount++;
            }

            if ($analysis['is_defaulted']) {
                $defaultedLoansCount++;
                $totalDefaultMonths += $analysis['months_defaulted'];
                $defaultedLoansCountForAverage++;
            }
        }

        return [
            'active_loans_count' => $activeLoansCount,
            'defaulted_loans_count' => $defaultedLoansCount,
            'total_amount_due' => $totalAmountDue,
            'total_arrears' => $totalArrears,
            'average_default_months' => $defaultedLoansCountForAverage > 0
                ? round($totalDefaultMonths / $defaultedLoansCountForAverage, 1)
                : 0,
            'default_rate' => $activeLoansCount > 0
                ? round(($defaultedLoansCount / $activeLoansCount) * 100, 1)
                : 0,
        ];
    }

    private function analyzeSingleLoan($loan)
    {
        $disbursementDate = Carbon::parse($loan->disbursement_date);
        $today = Carbon::now();

        // Calculate total months since disbursement
        $totalMonthsSinceDisbursement = $disbursementDate->diffInMonths($today);

        // Grace period: 6 months
        $gracePeriodEnd = $disbursementDate->copy()->addMonths(6);

        // Check if still in grace period
        if ($today->lte($gracePeriodEnd)) {
            return [
                'is_active' => true,
                'is_defaulted' => false,
                'amount_due' => 0,
                'arrears' => 0,
                'months_defaulted' => 0,
                'months_in_repayment' => 0,
                'monthly_installment' => $loan->amount_issued / 24,
            ];
        }

        // Repayment period calculation
        $repaymentStart = $gracePeriodEnd->copy()->addDay();
        $monthsInRepayment = $repaymentStart->diffInMonths($today);

        // Cap at 24 months for amount due calculation
        $monthsInRepaymentForDue = min($monthsInRepayment, 24);

        // Monthly installment
        $monthlyInstallment = $loan->amount_issued / 24;

        // Amount due (what should have been paid by now)
        $amountDue = $monthlyInstallment * $monthsInRepaymentForDue;

        // Arrears (amount due minus amount repaid)
        $arrears = max($amountDue - $loan->amount_repaid, 0);

        // Check if defaulted (arrears > 0)
        $isDefaulted = $arrears > 0;

        // Calculate months defaulted
        $monthsDefaulted = 0;
        if ($isDefaulted && $monthlyInstallment > 0) {
            $monthsDefaulted = floor($arrears / $monthlyInstallment);
        }

        // Loan is active if still within repayment period or has arrears
        $isActive = $totalMonthsSinceDisbursement <= 30 || $arrears > 0;

        return [
            'is_active' => $isActive,
            'is_defaulted' => $isDefaulted,
            'amount_due' => $amountDue,
            'arrears' => $arrears,
            'months_defaulted' => $monthsDefaulted,
            'months_in_repayment' => $monthsInRepaymentForDue,
            'monthly_installment' => $monthlyInstallment,
        ];
    }

    private function loadMembersCount()
    {
        $cacheKey = "constituency_members_{$this->constituency->constituency_code}";

        return Cache::remember($cacheKey, 3600, function() {
            return \App\Models\GroupMember::whereHas('groupLoan.loanPhase',
                fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
            )->distinct()->count('member_Id');
        });
    }

    private function loadTotalAmountIssued()
    {
        return \App\Models\GroupLoan::whereHas('loanPhase',
            fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
        )->where('loan_status', 'financed')->sum('amount_issued');
    }

    private function loadTotalAmountRepaid()
    {
        return \App\Models\GroupLoan::whereHas('loanPhase',
            fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
        )->where('loan_status', 'financed')->sum('amount_repaid');
    }

    private function loadTotalAmountApproved()
    {
        return \App\Models\GroupLoan::whereHas('loanPhase',
            fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
        )->whereIn('loan_status', ['financed', 'cancelled'])->sum('amount_approved');
    }

    // UPDATED: Use pre-loaded stats instead of querying again
    private function calculateRepaymentProgressRate($basicStats)
    {
        if ($basicStats['totalAmountIssued'] == 0) return 0;

        return round(($basicStats['totalAmountRepaid'] / $basicStats['totalAmountIssued']) * 100, 2);
    }

    // UPDATED: Use pre-loaded stats instead of querying again
    private function calculateRepaymentPerformanceRate($basicStats, $loanAnalysis)
    {
        if ($loanAnalysis['total_amount_due'] == 0) return 0;

        $performanceRate = ($basicStats['totalAmountRepaid'] / $loanAnalysis['total_amount_due']) * 100;
        return round($performanceRate - 100, 1);
    }

    private function loadPendingApplicationsCount()
    {
        return \App\Models\GroupLoan::whereHas('loanPhase',
            fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
        )->where('loan_status', 'pending')->count();
    }

    private function loadCategoryCount($category)
    {
        return $this->constituency->groups()
            ->where('category', $category)
            ->count();
    }

    private function loadRecentApplications()
    {
        return \App\Models\GroupLoan::whereHas('loanPhase',
            fn($q) => $q->where('constituency_code', $this->constituency->constituency_code)
        )->with('group')
         ->latest()
         ->limit(5)
         ->get();
    }

    public function refreshStats()
    {
        Cache::forget("constituency_dashboard_{$this->constituency->constituency_code}");
        $this->loadCachedData();
        $this->dispatch('stats-refreshed');
    }

    public function render()
    {
        return view('livewire.constituency-component');
    }
}
