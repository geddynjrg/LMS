<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConstituencyComponent extends Component
{
    public Constituency $constituency;

    // Properties to be mapped from cache/data
    public $totalgroups, $totalmembers, $totalAmountIssued, $totalAmountRepaid, $totalAmountApproved;
    public $repaymentProgressRate, $repaymentPerformanceRate, $activeLoans, $pendingApplications, $outstandingBalance;
    public $womenGroups, $youthGroups, $pwdGroups, $defaultedLoans;
    public $totalAmountDue, $totalArrears, $averageDefaultMonths, $defaultRate, $recentApplications;

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency->load('county');
        $this->loadData();
    }

    public function loadData()
    {
        $cacheKey = "const_dash_v2_{$this->constituency->constituency_code}";

        $data = Cache::remember($cacheKey, 300, function() {
            // 1. Get Financed Loans for this constituency via Phase
            $financedLoans = GroupLoan::whereHas('loanPhase', function($q) {
                $q->where('constituency_code', $this->constituency->constituency_code);
            })->where('loan_status', 'financed')->get();

            // 2. Run Financial Analysis
            $analysis = $this->runComprehensiveAnalysis($financedLoans);

            // 3. Category Breakdown (Optimized query)
            $categories = DB::table('group_loans')
                ->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
                ->where('loan_phases.constituency_code', $this->constituency->constituency_code)
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category');

            return [
                'totalgroups' => $this->constituency->groups()->count(),
                'totalmembers' => $this->getMembersCount(),
                'totalAmountIssued' => $financedLoans->sum('amount_issued'),
                'totalAmountRepaid' => $financedLoans->sum('amount_repaid'),
                'totalAmountApproved' => $this->getTotalApproved(),

                'activeLoans' => $analysis['active_count'],
                'defaultedLoans' => $analysis['default_count'],
                'totalAmountDue' => $analysis['due_sum'],
                'totalArrears' => $analysis['arrears_sum'],
                'defaultRate' => $analysis['active_count'] > 0 ? round(($analysis['default_count'] / $analysis['active_count']) * 100, 1) : 0,

                'womenGroups' => $categories['Women'] ?? 0,
                'youthGroups' => $categories['Youth'] ?? 0,
                'pwdGroups' => $categories['PWD'] ?? 0,

                'pendingApplications' => GroupLoan::whereHas('loanPhase', fn($q) => $q->where('constituency_code', $this->constituency->constituency_code))
                                        ->where('loan_status', 'pending')->count(),

                'recentApplications' => GroupLoan::whereHas('loanPhase', fn($q) => $q->where('constituency_code', $this->constituency->constituency_code))
                                        ->with('group')->latest()->limit(5)->get(),
                'lastUpdated' => now()->format('h:i A'),
            ];
        });

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        // Post-calculate derived rates
        $this->outstandingBalance = $this->totalAmountIssued - $this->totalAmountRepaid;
        $this->repaymentProgressRate = $this->totalAmountIssued > 0 ? round(($this->totalAmountRepaid / $this->totalAmountIssued) * 100, 2) : 0;
        $this->repaymentPerformanceRate = $this->totalAmountDue > 0
            ? round(($this->totalAmountRepaid / $this->totalAmountDue) * 100, 2) -100
            : 0;

        if ($this->repaymentPerformanceRate > 100) {
            $this->repaymentPerformanceRate = 100;
        }
    }

    private function runComprehensiveAnalysis($loans)
    {
        $res = [
            'active_count' => 0,
            'default_count' => 0,
            'due_sum' => 0,
            'arrears_sum' => 0,
            'avg_default_months' => 0
        ];

        $totalMonthsBehind = 0;

        foreach ($loans as $loan) {
            if (!$loan->disbursement_date) continue;

            $disbursed = Carbon::parse($loan->disbursement_date);
            $graceEnd = $disbursed->copy()->addMonths(6);

            // Only assess loans where the grace period has ended
            if (now()->gt($graceEnd)) {
                $res['active_count']++;

                // Calculate how many months they should have been paying (capped at 24)
                $monthsInRepayment = min(floor($graceEnd->diffInMonths(now())), 24);

                if ($monthsInRepayment > 0) {
                    $monthlyInstallment = $loan->amount_issued / 24;
                    $shouldHavePaid = $monthlyInstallment * $monthsInRepayment;

                    $res['due_sum'] += $shouldHavePaid;

                    $arrears = $shouldHavePaid - $loan->amount_repaid;

                    if ($arrears > 0) {
                        $res['arrears_sum'] += $arrears;
                        $res['default_count']++;

                        // Track how many months they are behind
                        $monthsBehind = ceil($arrears / $monthlyInstallment);
                        $totalMonthsBehind += $monthsBehind;
                    }
                }
            }
        }

        $res['averageDefaultMonths'] = $res['default_count'] > 0
            ? round($totalMonthsBehind / $res['default_count'], 1)
            : 0;

        return $res;
    }

    private function getMembersCount()
    {
        return DB::table('members')
            ->join('group_members', 'members.member_Id', '=', 'group_members.member_Id')
            ->join('group_loans', 'group_members.group_loans_id', '=', 'group_loans.id')
            ->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
            ->where('loan_phases.constituency_code', $this->constituency->constituency_code)
            ->distinct('members.member_Id')
            ->count('members.member_Id');
    }

    private function getTotalApproved() {
        return GroupLoan::whereHas('loanPhase', fn($q) => $q->where('constituency_code', $this->constituency->constituency_code))
            ->whereIn('loan_status', ['financed', 'approved'])->sum('amount_approved');
    }

    public function refreshStats()
    {
        // Clear the specific cache key for this constituency
        $cacheKey = "const_dash_v2_{$this->constituency->constituency_code}";
        Cache::forget($cacheKey);

        // Re-run the data loading logic
        $this->loadData();

        // Optional: Dispatch a browser event for a toast notification
        $this->dispatch('stats-refreshed', message: 'Dashboard data updated successfully!');
    }

    public function render() {
        return view('livewire.constituency-component');
    }
}
