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
use App\Services\Cache\AppCacheService;
use Livewire\Attributes\Computed;

class ConstituencyComponent extends Component
{
    protected LoanAnalysisService $analysisService;
    protected AppCacheService $cacheService;

    public Constituency $constituency;
    public $stats = [];

    // Properties to be mapped from cache/data
    public $totalgroups, $totalmembers, $totalAmountIssued, $totalAmountRepaid, $totalAmountApproved;
    public $repaymentProgressRate, $recoveryRate, $activeLoans, $pendingApplications, $outstandingBalance;
    public $womenGroups, $youthGroups, $pwdGroups, $defaultedLoans;
    public $totalAmountDue, $totalArrears, $averageDefaultMonths, $defaultRate, $recentApplications;

    public $countyFilter = '';
    public $constituencyFilter = '';
    public $wardFilter = '';

    public function boot(
        LoanAnalysisService $analysisService,
        AppCacheService $cacheService
    )
    {
        $this->analysisService = $analysisService;
        $this->cacheService = $cacheService;
    }

    public function mount(Constituency $constituency)
    {
        $this->countyFilter = $constituency->county_code;
        $this->constituencyFilter = $constituency->constituency_code;

        $this->constituency = $constituency->load('wards');
    }

    #[Computed]
    public function getStats()
    {
        $filters = $this->getFilters();

        $cacheKey = md5(json_encode($filters));

        $data = $this->cacheService->rememberDashboardStats(
            $cacheKey,
            fn () => $this->analysisService->getDashboardStats($filters),
            $this->constituency->constituency_code
        );

        // Calculate derived rates that don't need caching
        $data['outstandingBalance'] = $data['totalAmountIssued'] - $data['totalAmountRepaid'];
        $data['repaymentProgressRate'] = $data['totalAmountIssued'] > 0
            ? round(($data['totalAmountRepaid'] / $data['totalAmountIssued']) * 100, 2) : 0;

        return $data;
    }

    #[Computed]
    public function kpis()
    {
        $approved = $this->getStats['totalAmountApproved'] ?? 0;
        $issued = $this->getStats['totalAmountIssued'] ?? 0;
        $repaid = $this->getStats['totalAmountRepaid'] ?? 0;
        $progress = $this->getStats['repaymentProgressRate'] ?? 0;

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
                'val' => $this->getStats['outstandingBalance'] ?? 0,
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
        $recovery_rate = $this->getStats['recoveryRate'] ?? 0;
        $due = $this->getStats['totalAmountDue'] ?? 0;
        $repaid = $this->getStats['totalAmountRepaid'] ?? 0;

        return [
            'recovery_rate' => $recovery_rate,
            'arrears' => $this->getStats['totalArrears'],
            'due' => $due,
            'repaid' => $repaid,
            'deficit' => max(0, $due - $repaid),
            'gap_percentage' => $recovery_rate <= 0 ? 0 : (100 - min($recovery_rate, 100)),
            'arrears_ratio' => $due > 0 ? round((($due - $repaid) / $due) * 100, 1) : 0,
            'progress_rate' => $this->getStats['repaymentProgressRate'] ?? 0,
            'financial_rate' => $this->getStats['financialDefaultRate'],
        ];
    }

    #[Computed]
    public function riskProfile()
    {
        $rate = $this->getStats['defaultRate'] ?? 0;
        $isHighRisk = $rate > 10;
        return [
            'count' => $this->getStats['defaultedLoansCount'] ?? 0,
                'avg_lag' => $this->getStats['averageDefaultMonths'] ?? 0,
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
        $totalGroups = $this->getStats['totalGroups'] ?? 0;
        $totalIssued = $this->getStats['totalAmountIssued'] ?? 0;

        return [
            [
                'label' => 'Women Groups',
                'val' => $this->getStats['womenGroups'] ?? 0,
                'amount' => $this->getStats['womenAmount'],
                'color' => 'pink',
                'icon' => 'fa-female',
                'cat' => 'Women'
            ],
            [
                'label' => 'Youth Groups',
                'val' => $this->getStats['youthGroups'] ?? 0,
                'amount' => $this->getStats['youthAmount'],
                'color' => 'orange',
                'icon' => 'fa-child',
                'cat' => 'Youth'
            ],
            [
                'label' => 'PWD Groups',
                'val' => $this->getStats['pwdGroups'] ?? 0,
                'amount' => $this->getStats['pwdAmount'],
                'color' => 'purple',
                'icon' => 'fa-wheelchair',
                'cat' => 'PWD'
            ],
            [
                'label' => 'Unique Groups',
                'val' => $totalGroups,
                'amount' => $totalIssued,
                'color' => 'primary',
                'icon' => 'fa-users',
                'cat' => null
            ]
        ];
    }

    #[Computed]
    public function wardsOptions()
    {
        return $this->constituency->wards->map(fn($w) => [
                'id' => $w->id,
                'name' => ucwords($w->ward_name),
            ])
            ->toArray();
    }

    public function resetFilters()
    {
        $this->reset(['wardFilter']);
    }

    public function refreshData()
    {
        $this->cacheService->flashCache('dashboard', 'constituency_stats',  $this->constituency->constituency_code);

        unset($this->getStats, $this->kpis, $this->performance, $this->riskProfile, $this->demographics, $this->economicSectors);

        unset($this->getStats, $this->kpis, $this->performance, $this->riskProfile, $this->demographics, $this->economicSectors);
    }


    private function getFilters(): array
    {
        return [
            'county_code' => $this->countyFilter,
            'constituency_code' => $this->constituencyFilter,
            'ward_id' => $this->wardFilter,
        ];
    }

    private function getSectorColor($sector)
    {
        return match (trim($sector)) {
            'Trade'         => 'primary',
            'Service'       => 'info',
            'Manufacturing' => 'indigo',
            'Agriculture'   => 'success',
            default         => 'secondary',
        };
    }


    private function getSectorIcon($sector)
    {
        return match (trim($sector)) {
            'Trade'         => 'fa-store',
            'Service'       => 'fa-concierge-bell',
            'Manufacturing' => 'fa-industry',
            'Agriculture'   => 'fa-leaf',
            default         => 'fa-ellipsis-h',
        };
    }

    #[Computed]
    public function economicSectors()
    {
        $totalIssued = $this->getStats['totalAmountIssued'] ?? 0;
        $breakdown = $this->getStats['sector_breakdown'] ?? collect();

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
