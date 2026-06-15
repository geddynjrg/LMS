<?php

namespace App\Livewire\Constituency\Concerns;

use App\Models\Constituency;
use App\Models\LoanPhase;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Loan\SecretariatApprovalService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait ManagesConstituencyDashboardData
{
    protected LoanAnalysisService $analysisService;
    protected AppCacheService $cacheService;

    public Constituency $constituency;

    public $countyFilter = '';
    public $constituencyFilter = '';
    public $wardFilter = '';
    public $constituencyBankBalance = '';

    public function bootManagesConstituencyDashboardData(
        LoanAnalysisService $analysisService,
        AppCacheService $cacheService
    ): void {
        $this->analysisService = $analysisService;
        $this->cacheService = $cacheService;
    }

    public function mountManagesConstituencyDashboardData(Constituency $constituency): void
    {
        $this->countyFilter = $constituency->county_code;
        $this->constituencyFilter = $constituency->constituency_code;
        $this->constituency = $constituency->load('wards', 'county');
        $this->constituencyBankBalance = $constituency->constituency_bank_balance ?? '';
    }

    public function saveConstituencyBankBalance(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['super-admin', 'constituency_officer']),
            403
        );

        $this->validate([
            'constituencyBankBalance' => 'nullable|numeric|min:0',
        ]);

        $this->constituency->update([
            'constituency_bank_balance' => $this->constituencyBankBalance === ''
                ? null
                : $this->constituencyBankBalance,
        ]);

        $this->constituency->refresh();
        $this->constituencyBankBalance = $this->constituency->constituency_bank_balance ?? '';

        $this->dispatch('swal:toast',
            title: 'Saved',
            message: 'Constituency bank account balance updated.',
            type: 'success'
        );
    }

    #[Computed]
    public function openPhaseRequestedAmount(): float
    {
        $phase = LoanPhase::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->whereIn('status', ['draft', 'in_review'])
            ->orderByDesc('created_at')
            ->first();

        if (! $phase) {
            return 0;
        }

        return app(SecretariatApprovalService::class)->phaseRequestedAmount($phase);
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
                'prog' => 100,
            ],
            [
                'title' => 'Actual Disbursement',
                'val' => $issued,
                'color' => 'success',
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
    public function wardsOptions()
    {
        return $this->constituency->wards->map(fn ($w) => [
            'id' => $w->id,
            'name' => ucwords($w->ward_name),
        ])->toArray();
    }

    #[Computed]
    public function economicSectors()
    {
        $totalIssued = $this->getStats['totalAmountIssued'] ?? 0;
        $breakdown = $this->getStats['sector_breakdown'] ?? collect();

        return $breakdown->map(function ($data, $sectorName) use ($totalIssued) {
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

    public function resetFilters(): void
    {
        $this->reset(['wardFilter']);
    }

    public function refreshDashboardData(): void
    {
        $this->cacheService->flashCache('dashboard', 'constituency_stats', $this->constituency->constituency_code);

        unset(
            $this->getStats,
            $this->kpis,
            $this->performance,
            $this->riskProfile,
            $this->demographics,
            $this->economicSectors,
            $this->openPhaseRequestedAmount
        );
    }

    protected function getFilters(): array
    {
        return [
            'county_code' => $this->countyFilter,
            'constituency_code' => $this->constituencyFilter,
            'ward_id' => $this->wardFilter,
        ];
    }

    protected function getSectorColor($sector): string
    {
        return match (trim($sector)) {
            'Trade' => 'primary',
            'Service' => 'info',
            'Manufacturing' => 'indigo',
            'Agriculture' => 'success',
            default => 'secondary',
        };
    }

    protected function getSectorIcon($sector): string
    {
        return match (trim($sector)) {
            'Trade' => 'fa-store',
            'Service' => 'fa-concierge-bell',
            'Manufacturing' => 'fa-industry',
            'Agriculture' => 'fa-leaf',
            default => 'fa-ellipsis-h',
        };
    }
}
