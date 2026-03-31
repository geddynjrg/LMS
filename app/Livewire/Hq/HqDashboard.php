<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Cache\AppCacheService;
use App\Services\LocationService;

class HqDashboard extends Component
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

    public function boot(
        LoanAnalysisService $analysisService,
        AppCacheService $cacheService,
        LocationService $locationService
    ) {
        $this->analysisService = $analysisService;
        $this->cacheService = $cacheService;
        $this->locationService = $locationService;
    }

    public function mount ()
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
                'color' => 'primary',
                'icon' => 'fa-landmark',
                'desc' => 'Total lifecycle value',
                'prog' => 100
            ],
            [
                'title' => 'Actual Disbursement',
                'val' => $issued,
                'color' => 'info',
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

    #[Computed]
    private function loadCountyOptions()
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn($c) => [
                'id' => $c->county_code,
                'name' => $c->county_name
            ])
            ->toArray();
    }

    public function updatedCountyFilter($value)
    {
        $this->constituencyFilter = '';
        $this->wardFilter = '';

        $this->reset('constituencyOptions');
        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }

    }

    public function updatedConstituencyFilter($value)
    {
        $this->wardFilter = '';

        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadWards($value);
        }
    }

    private function loadConstituencies($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $this->constituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => Str::ucfirst(Str::lower($c->constituency_name))
            ])
            ->values()
            ->toArray();
    }

    private function loadWards($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->wardsOptions = [];
            return;
        }

        $this->wardsOptions = $this->locationService->getAllWards()
            ->where('constituency_code', $constituencyCode)
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => Str::ucfirst(Str::lower($w->ward_name)),
            ])
            ->values()
            ->toArray();
    }

    public function resetFilters()
    {
        $this->reset(['wardFilter', 'constituencyFilter', 'countyFilter']);
    }

    public function refreshData()
    {
        $this->cacheService->flashCache('dashboard', 'national_stats');

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
            'Manufacturing' => 'pink',
            'Agriculture'   => 'success',
            default         => 'primary',
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

    public function render()
    {
        return view('livewire.hq.hq-dashboard');
    }

    // public function render()
    // {
    //     // 1. High-Level Financial Metrics
    //     $stats = [
    //         'total_applications' => PhaseApplication::count(),
    //         'total_groups_loaned' => GroupLoan::where('loan_status', 'financed')->distinct('group_id')->count(),
    //         'total_member_loans' => DB::table('group_members')->count(), // Based on group_members table
    //         'pending_review' => LoanPhase::where('status', 'submitted_to_hq')->count(),
    //         'youth_groups' => GroupLoan::where('category', 'like', '%Youth%')->count(),
    //         'women_groups' => GroupLoan::where('category', 'like', '%Women%')->count(),
    //         'pwd_groups' => GroupLoan::where('category', 'like', '%PWD%')->count(),
    //         'defaulters' => GroupLoan::where('is_defaulted', 1)->count(),
    //     ];

    //     $activities = GroupLoan::select('sector as name', DB::raw('count(*) as count'))
    //         ->groupBy('sector')
    //         ->orderByDesc('count')
    //         ->limit(3)
    //         ->get();

    //     $countyStats = DB::table('counties')
    //         ->join('loan_phases', 'counties.county_code', '=', 'loan_phases.constituency_code')
    //         ->select('counties.county_name', DB::raw('count(*) as total'))
    //         ->groupBy('counties.county_name')
    //         ->orderByDesc('total')
    //         ->limit(3)
    //         ->get();

    //     // 2. Demographic Distribution
    //     $demographics = [
    //         'youth' => [
    //             'count' => GroupLoan::where('category', 'like', '%Youth%')->count(),
    //             'color' => 'info'
    //         ],
    //         'women' => [
    //             'count' => GroupLoan::where('category', 'like', '%Women%')->count(),
    //             'color' => 'maroon'
    //         ],
    //         'pwd' => [
    //             'count' => GroupLoan::where('category', 'like', '%PWD%')->count(),
    //             'color' => 'teal'
    //         ],
    //     ];

    //     // 3. Actionable Queue: Recent Submissions from Constituencies
    //     // This allows HQ to jump straight into the review process
    //     $recentSubmissions = LoanPhase::with('constituency')
    //         ->where('status', 'submitted_to_hq')
    //         ->orderBy('submitted_to_hq_at', 'desc')
    //         ->limit(5)
    //         ->get();

    //     $countyApps = DB::table('counties')
    //         ->join('constituencies', 'counties.county_code', '=', 'constituencies.county_code')
    //         ->join('loan_phases', 'constituencies.constituency_code', '=', 'loan_phases.constituency_code')
    //         ->select('counties.county_name', DB::raw('count(loan_phases.id) as total'))
    //         ->groupBy('counties.county_name')
    //         ->orderByDesc('total')
    //         ->limit(5)
    //         ->get();

    //     $recentPhases = LoanPhase::with(['constituency', 'constituencyOfficer']) // Assuming 'officer' relation exists
    //         ->whereIn('status', ['submitted_to_hq', 'in_review'])
    //         ->orderBy('submitted_to_hq_at', 'desc')
    //         ->limit(3)
    //         ->get();

    //     $financials = [
    //         'total_disbursed' => GroupLoan::where('loan_status', 'financed')->sum('amount_approved'),
    //         'total_approved_pending' => GroupLoan::where('loan_status', 'approved')->sum('amount_approved'),
    //         'total_defaulted_amt' => GroupLoan::where('is_defaulted', 1)->sum('total_arrears'), // or current balance
    //         'total_repaid' => DB::table('loan_receipts')->sum('amount'), // If you have a receipts table
    //     ];

    //     return view('livewire.hq.hq-dashboard', [
    //         'stats' => $stats,
    //         'demographics' => $demographics,
    //         'recentSubmissions' => $recentSubmissions,
    //         'activities' => $activities,
    //         'countyStats' => $countyStats,
    //         'countyApps' => $countyApps,
    //         'recentPhases' => $recentPhases,
    //         'financials' => $financials,
    //     ]);
    // }
}
