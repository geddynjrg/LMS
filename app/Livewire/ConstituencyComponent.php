<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\Loan\LoanAnalysisService;
use Livewire\Attributes\Computed;

class ConstituencyComponent extends Component
{
    protected LoanAnalysisService $analysisService;

    public Constituency $constituency;
    public $stats = [];

    // Properties to be mapped from cache/data
    public $totalgroups, $totalmembers, $totalAmountIssued, $totalAmountRepaid, $totalAmountApproved;
    public $repaymentProgressRate, $repaymentPerformanceRate, $activeLoans, $pendingApplications, $outstandingBalance;
    public $womenGroups, $youthGroups, $pwdGroups, $defaultedLoans;
    public $totalAmountDue, $totalArrears, $averageDefaultMonths, $defaultRate, $recentApplications;

    public function boot(LoanAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency->load('county');
        $this->loadData();
    }

    public function loadData()
    {
        $data = $this->analysisService->getConstituencyStats($this->constituency);

        // Calculate derived rates that don't need caching
        $data['outstandingBalance'] = $data['totalAmountIssued'] - $data['totalAmountRepaid'];
        $data['repaymentProgressRate'] = $data['totalAmountIssued'] > 0
            ? round(($data['totalAmountRepaid'] / $data['totalAmountIssued']) * 100, 2) : 0;

        $this->stats = $data;
    }

    #[Computed]
    public function kpis()
    {
        $approved = $this->stats['totalAmountApproved'] ?? 0;
        $issued = $this->stats['totalAmountIssued'] ?? 0;
        $repaid = $this->stats['totalAmountRepaid'] ?? 0;
        $progress = $this->stats['repaymentProgressRate'] ?? 0;

        return [
            [
                'title' => 'Approved Portfolio',
                'val' => $approved,
                'color' => 'info',
                'icon' => 'fa-landmark',
                'desc' => 'Total lifecycle value',
                'prog' => 100
            ],
            [
                'title' => 'Actual Disbursement',
                'val' => $issued,
                'color' => 'success',
                'icon' => 'fa-hand-holding-usd',
                'desc' => 'Issued vs Approved',
                'prog' => ($approved > 0 ? ($issued / $approved) * 100 : 0)
            ],
            [
                'title' => 'Total Repaid',
                'val' => $repaid,
                'color' => 'danger',
                'icon' => 'fa-history',
                'desc' => $progress . '% recovery rate',
                'prog' => $progress
            ],
            [
                'title' => 'Outstanding',
                'val' => $this->stats['outstandingBalance'] ?? 0,
                'color' => 'dark',
                'icon' => 'fa-balance-scale',
                'desc' => 'Current liability',
                'prog' => 100
            ],
        ];
    }

    #[Computed]
    public function performance()
    {
        $rate = $this->stats['repaymentPerformanceRate'] ?? 0;
        $due = $this->stats['totalAmountDue'] ?? 0;
        $repaid = $this->stats['totalAmountRepaid'] ?? 0;

        return [
            'rate' => $rate,
            'due' => $due,
            'repaid' => $repaid,
            'deficit' => max(0, $due - $repaid),
            'gap_percentage' => 100 - min($rate, 100),
            'arrears_ratio' => $due > 0 ? round((($due - $repaid) / $due) * 100, 1) : 0,
            'progress_rate' => $this->stats['repaymentProgressRate'] ?? 0,
        ];
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
        Cache::forget("const_stats_v3_{$this->constituency->constituency_code}");
        $this->loadData($this->analysisService);
        $this->dispatch('stats-refreshed', message: 'Data updated!');
    }

    public function render() {
        dd($this->performance, $this->stats);
        return view('livewire.constituency-component', [
            'performance' => $this->performance,
        ]);
    }
}
