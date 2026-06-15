<?php

namespace App\Livewire\Hq\Concerns;

use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\LocationService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

trait ManagesHqDashboardData
{
    protected LoanAnalysisService $analysisService;
    protected AppCacheService $cacheService;
    protected LocationService $locationService;

    public $countyFilter = '';
    public $constituencyFilter = '';
    public $wardFilter = '';

    public $countyOptions = [];
    public $constituencyOptions = [];
    public $wardsOptions = [];

    public function bootManagesHqDashboardData(
        LoanAnalysisService $analysisService,
        AppCacheService $cacheService,
        LocationService $locationService
    ): void {
        $this->analysisService = $analysisService;
        $this->cacheService = $cacheService;
        $this->locationService = $locationService;
    }

    public function mountManagesHqDashboardData(): void
    {
        $this->loadCountyOptions();
    }

    #[Computed]
    public function getStats()
    {
        $filters = $this->getFilters();

        $cacheKey = md5(json_encode($filters));

        $data = $this->cacheService->rememberDashboardStats(
            $cacheKey,
            fn () => $this->analysisService->getDashboardStats($filters)
        );

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
                'color' => 'primary',
                'icon' => 'fa-landmark',
                'desc' => 'Total lifecycle value',
                'prog' => 100,
            ],
            [
                'title' => 'Actual Disbursement',
                'val' => $issued,
                'color' => 'info',
                'icon' => 'fa-hand-holding-usd',
                'desc' => 'Issued vs Approved',
                'prog' => ($approved > 0 ? ($issued / $approved) * 100 : 0),
            ],
            [
                'title' => 'Total Repaid',
                'val' => $repaid,
                'color' => 'danger',
                'icon' => 'fa-history',
                'desc' => $progress . '% recovery rate',
                'prog' => $progress,
            ],
            [
                'title' => 'Outstanding',
                'val' => $this->getStats['outstandingBalance'] ?? 0,
                'color' => 'dark',
                'icon' => 'fa-balance-scale',
                'desc' => 'Current liability',
                'prog' => 100,
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
        return [
            'count' => $this->getStats['defaultedLoansCount'] ?? 0,
            'avg_lag' => $this->getStats['averageDefaultMonths'] ?? 0,
            'rate' => $this->performance['financial_rate'],
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
                'cat' => 'Women',
            ],
            [
                'label' => 'Youth Groups',
                'val' => $this->getStats['youthGroups'] ?? 0,
                'amount' => $this->getStats['youthAmount'],
                'color' => 'orange',
                'icon' => 'fa-child',
                'cat' => 'Youth',
            ],
            [
                'label' => 'PWD Groups',
                'val' => $this->getStats['pwdGroups'] ?? 0,
                'amount' => $this->getStats['pwdAmount'],
                'color' => 'purple',
                'icon' => 'fa-wheelchair',
                'cat' => 'PWD',
            ],
            [
                'label' => 'Unique Groups',
                'val' => $totalGroups,
                'amount' => $totalIssued,
                'color' => 'primary',
                'icon' => 'fa-users',
                'cat' => null,
            ],
        ];
    }

    #[Computed]
    public function economicSectors()
    {
        $totalIssued = $this->getStats['totalAmountIssued'] ?? 0;
        $breakdown = $this->getStats['sector_breakdown'] ?? collect();

        return $breakdown->map(function ($data, $sectorName) use ($totalIssued) {
            return [
                'label' => ucfirst($sectorName),
                'amount' => $data->total_issued,
                'count' => $data->loan_count,
                'percentage' => $totalIssued > 0 ? round(($data->total_issued / $totalIssued) * 100) : 0,
                'color' => $this->getSectorColor($sectorName),
                'icon' => $this->getSectorIcon($sectorName),
            ];
        })->values();
    }

    protected function loadCountyOptions(): void
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn ($c) => [
                'id' => $c->county_code,
                'name' => $c->county_name,
            ])
            ->toArray();
    }

    public function updatedCountyFilter($value): void
    {
        $this->constituencyFilter = '';
        $this->wardFilter = '';
        $this->reset('constituencyOptions');
        $this->reset('wardsOptions');

        if (! empty($value)) {
            $this->loadConstituencies($value);
        }
    }

    public function updatedConstituencyFilter($value): void
    {
        $this->wardFilter = '';
        $this->reset('wardsOptions');

        if (! empty($value)) {
            $this->loadWards($value);
        }
    }

    protected function loadConstituencies($countyCode = null): void
    {
        if (! $countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $this->constituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->map(fn ($c) => [
                'id' => $c->constituency_code,
                'name' => Str::ucfirst(Str::lower($c->constituency_name)),
            ])
            ->values()
            ->toArray();
    }

    protected function loadWards($constituencyCode = null): void
    {
        if (! $constituencyCode) {
            $this->wardsOptions = [];
            return;
        }

        $this->wardsOptions = $this->locationService->getAllWards()
            ->where('constituency_code', $constituencyCode)
            ->map(fn ($w) => [
                'id' => $w->id,
                'name' => Str::ucfirst(Str::lower($w->ward_name)),
            ])
            ->values()
            ->toArray();
    }

    public function resetFilters(): void
    {
        $this->reset(['wardFilter', 'constituencyFilter', 'countyFilter']);
    }

    public function refreshDashboardData(): void
    {
        $this->cacheService->flashCache('dashboard', 'national_stats');

        unset(
            $this->getStats,
            $this->kpis,
            $this->performance,
            $this->riskProfile,
            $this->demographics,
            $this->economicSectors
        );
    }

    protected function getFilters(): array
    {
        $filters = [
            'county_code' => $this->countyFilter,
            'constituency_code' => $this->constituencyFilter,
            'ward_id' => $this->wardFilter,
        ];

        $user = auth()->user();
        if ($user && $user->hasRole('hq_officer') && ! $user->hasRole('super-admin')) {
            $filters['assigned_hq_officer_id'] = $user->id;
        }

        return $filters;
    }

    protected function getSectorColor($sector): string
    {
        return match (trim(ucfirst(strtolower($sector)))) {
            'Trade' => 'primary',
            'Service' => 'info',
            'Manufacturing' => 'pink',
            'Agriculture' => 'success',
            default => 'secondary',
        };
    }

    protected function getSectorIcon($sector): string
    {
        return match (trim(ucfirst(strtolower($sector)))) {
            'Trade' => 'fa-store',
            'Service' => 'fa-concierge-bell',
            'Manufacturing' => 'fa-industry',
            'Agriculture' => 'fa-leaf',
            default => 'fa-ellipsis-h',
        };
    }
}
