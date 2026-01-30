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
    public $repaymentProgressRate, $recoveryRate, $activeLoans, $pendingApplications, $outstandingBalance;
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
        $recovery_rate = $this->stats['recoveryRate'] ?? 0;
        $due = $this->stats['totalAmountDue'] ?? 0;
        $repaid = $this->stats['totalAmountRepaid'] ?? 0;

        return [
            'recovery_rate' => $recovery_rate,
            'arrears' => $this->stats['totalArrears'],
            'due' => $due,
            'repaid' => $repaid,
            'deficit' => max(0, $due - $repaid),
            'gap_percentage' => 100 - min($recovery_rate, 100),
            'arrears_ratio' => $due > 0 ? round((($due - $repaid) / $due) * 100, 1) : 0,
            'progress_rate' => $this->stats['repaymentProgressRate'] ?? 0,
            'financial_rate' => $this->stats['financialDefaultRate'],
        ];
    }

    #[Computed]
    public function riskProfile2()
    {
        $rate = $this->stats['defaultRate'] ?? 0;
        $isHighRisk = $rate > 10;

        return [
            'count' => $this->stats['defaultedLoansCount'] ?? 0,
            'avg_lag' => $this->stats['averageDefaultMonths'] ?? 0,
            'rate' => $rate,
            'is_high_risk' => $isHighRisk,
            'bg_class' => $isHighRisk ? 'bg-light-danger' : 'bg-light-info',
            'icon' => $isHighRisk ? 'fa-exclamation-circle text-danger' : 'fa-check-circle text-info',
            'message' => $isHighRisk
                ? 'Immediate intervention required for delinquent groups.'
                : 'Default rate is within acceptable institutional limits.'
        ];
    }

    #[Computed]
    public function riskProfile()
    {
        $rate = $this->stats['defaultRate'] ?? 0;
        $isHighRisk = $rate > 10;
        return [
            'count' => $this->stats['defaultedLoansCount'] ?? 0,
                'avg_lag' => $this->stats['averageDefaultMonths'] ?? 0,
                'rate' => $rate,
                'is_high_risk' => $isHighRisk,
            'rate' => $this->performance['financial_rate'],  // main number
            'bg_class' => $this->performance['financial_rate'] > 40 ? 'bg-danger-soft'
                        : ($this->performance['financial_rate'] > 20 ? 'bg-warning-soft' : 'bg-success-soft'),
            'text_color' => $this->performance['financial_rate'] > 40 ? 'text-danger'
                            : ($this->performance['financial_rate'] > 20 ? 'text-info' : 'text-pink'),
            'icon' => $this->performance['financial_rate'] > 40 ? 'fa-exclamation-triangle'
                    : ($this->performance['financial_rate'] > 20 ? 'fa-exclamation-circle' : 'fa-check-circle'),
            'message' => $this->performance['financial_rate'] > 40 ? 'High Risk – Urgent Action Required'
                        : ($this->performance['financial_rate'] > 20 ? 'Moderate Risk – Monitor Closely'
                            : 'Low Risk – Portfolio Healthy'),
        ];
    }

    #[Computed]
    public function demographics()
    {
        $totalGroups = $this->stats['totalGroups'] ?? 0;
        $totalIssued = $this->stats['totalAmountIssued'] ?? 0;

        return [
            [
                'label' => 'Women Groups',
                'val' => $this->stats['womenGroups'] ?? 0,
                'amount' => $this->stats['womenAmount'],
                'color' => 'pink',
                'icon' => 'fa-female',
                'cat' => 'Women'
            ],
            [
                'label' => 'Youth Groups',
                'val' => $this->stats['youthGroups'] ?? 0,
                'amount' => $this->stats['youthAmount'],
                'color' => 'orange',
                'icon' => 'fa-child',
                'cat' => 'Youth'
            ],
            [
                'label' => 'PWD Groups',
                'val' => $this->stats['pwdGroups'] ?? 0,
                'amount' => $this->stats['pwdAmount'],
                'color' => 'purple',
                'icon' => 'fa-wheelchair',
                'cat' => 'PWD'
            ],
            [
                'label' => 'Registry Size',
                'val' => $totalGroups,
                'amount' => $totalIssued,
                'color' => 'primary',
                'icon' => 'fa-users',
                'cat' => null
            ]
        ];
    }

    /**
 * Maps sector names to Bootstrap colors
 */
private function getSectorColor($sector)
{
    return match (trim($sector)) {
        'Trade'         => 'primary', // Blue
        'Service'       => 'info',    // Light Blue
        'Manufacturing' => 'indigo',  // Purple/Dark Blue
        'Agriculture'   => 'success', // Green
        default         => 'secondary', // Gray for "Others"
    };
}

/**
 * Maps sector names to FontAwesome icons
 */
private function getSectorIcon($sector)
{
    return match (trim($sector)) {
        'Trade'         => 'fa-store',
        'Service'       => 'fa-concierge-bell',
        'Manufacturing' => 'fa-industry',
        'Agriculture'   => 'fa-leaf',
        default         => 'fa-ellipsis-h', // Generic icon for "Others"
    };
}

    #[Computed]
    public function economicSectors()
    {
        $totalIssued = $this->stats['totalAmountIssued'] ?? 0;
        $breakdown = $this->stats['sector_breakdown'] ?? collect();

        // Dynamically build the list from whatever sectors exist in the DB
        return $breakdown->map(function($data, $sectorName) use ($totalIssued) {
            return [
                'label' => $sectorName,
                'amount' => $data->total_issued,
                'count' => $data->loan_count,
                'percentage' => $totalIssued > 0 ? round(($data->total_issued / $totalIssued) * 100) : 0,
                'color' => $this->getSectorColor($sectorName),
                'icon' => $this->getSectorIcon($sectorName),
            ];
        });
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
        return view('livewire.constituency-component', [
            'performance' => $this->performance,
            'riskProfile' => $this->riskProfile,
        ]);
    }
}
