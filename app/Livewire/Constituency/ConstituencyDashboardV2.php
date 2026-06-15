<?php

namespace App\Livewire\Constituency;

use App\Livewire\Constituency\Concerns\ManagesConstituencyDashboardData;
use App\Models\Constituency;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanAnalysisService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConstituencyDashboardV2 extends Component
{
    use ManagesConstituencyDashboardData;

    /** @var 'overview'|'data'|'analyze'|'tasks' */
    public string $workspace = 'overview';

    /** @var 'demographics'|'sectors'|'recovery' */
    public string $analysisDimension = 'demographics';

    public function boot(
        LoanAnalysisService $analysisService,
        AppCacheService $cacheService
    ) {
        $this->bootManagesConstituencyDashboardData($analysisService, $cacheService);
    }

    public function mount(Constituency $constituency)
    {
        $this->mountManagesConstituencyDashboardData($constituency);
        $this->dispatchChartUpdate();
    }

    public function setWorkspace(string $workspace): void
    {
        if (! in_array($workspace, ['overview', 'data', 'analyze', 'tasks'], true)) {
            return;
        }

        $this->workspace = $workspace;
        $this->dispatchChartUpdate();
    }

    public function setAnalysisDimension(string $dimension): void
    {
        if (! in_array($dimension, ['demographics', 'sectors', 'recovery'], true)) {
            return;
        }

        $this->analysisDimension = $dimension;
        $this->dispatchChartUpdate();
    }

    public function refreshData(): void
    {
        $this->refreshDashboardData();
        $this->invalidateV2Computed();
        $this->dispatchChartUpdate();
    }

    public function updated($property): void
    {
        if ($property !== 'wardFilter') {
            return;
        }

        $this->invalidateV2Computed();
        $this->dispatchChartUpdate();
    }

    protected function invalidateV2Computed(): void
    {
        unset($this->actionInbox, $this->dataRows, $this->chartPayload, $this->analysisTable, $this->wardRanking);
    }

    protected function dispatchChartUpdate(): void
    {
        $payload = $this->chartPayload;
        $encoded = json_encode($payload);

        $this->dispatch('const-dashboard-charts-updated', charts: $payload);
        $this->js('window.__constDashboardChartPayload = ' . $encoded . '; window.renderConstDashboardCharts && window.renderConstDashboardCharts(' . $encoded . ');');
    }

    #[Computed]
    public function wardRanking()
    {
        return DB::table('group_loans as gl')
            ->join('groups as g', 'gl.group_id', '=', 'g.id')
            ->join('wards as w', 'g.ward_id', '=', 'w.id')
            ->where('w.constituency_code', $this->constituency->constituency_code)
            ->where('gl.loan_status', 'financed')
            ->select(
                'w.id as ward_id',
                'w.ward_name',
                DB::raw('COUNT(DISTINCT g.id) as group_count'),
                DB::raw('SUM(gl.amount_issued) as total_issued'),
                DB::raw('SUM(gl.amount_repaid) as total_repaid'),
                DB::raw('SUM(gl.amount_due) as total_due'),
                DB::raw('CASE WHEN SUM(gl.amount_due) > 0 THEN ROUND((SUM(gl.amount_repaid) / SUM(gl.amount_due)) * 100, 1) ELSE 0 END as recovery_rate')
            )
            ->groupBy('w.id', 'w.ward_name')
            ->orderByDesc('recovery_rate')
            ->get();
    }

    #[Computed]
    public function dataRows(): array
    {
        return $this->wardRanking->map(fn ($row) => [
            'name' => ucwords(strtolower($row->ward_name)),
            'metric' => 'Recovery rate',
            'value' => number_format($row->recovery_rate, 1) . '%',
            'secondary' => number_format($row->group_count) . ' groups · KES ' . number_format($row->total_repaid ?? 0) . ' repaid',
            'tone' => $row->recovery_rate >= 70 ? 'success' : ($row->recovery_rate >= 40 ? 'info' : 'danger'),
        ])->all();
    }

    #[Computed]
    public function actionInbox(): array
    {
        $countySlug = $this->constituency->county->slug;
        $constSlug = $this->constituency->slug;

        return [
            'new_application_count' => count($this->getStats['newApplications'] ?? []),
            'new_applications' => collect($this->getStats['newApplications'] ?? [])->take(8)->map(fn ($app) => [
                'application_id' => $app->application_id,
                'group_name' => $app->group_name,
                'loan_amount' => $app->loan_amount,
                'created_at' => $app->created_at,
                'url' => route('const.app.application-view', [$countySlug, $constSlug, $app->application_id]),
            ])->all(),
            'approved_phases' => collect($this->getStats['approvedPhases'] ?? [])->take(8)->map(fn ($phase) => [
                'label' => ucwords($phase->phase ?? ''),
                'ref' => $phase->phase_ref,
                'amount' => $phase->total_amount_approved,
                'url' => route('const.app.app_phase_details', [$countySlug, $constSlug, $phase->slug]),
            ])->all(),
            'portal_url' => route('const.app.app_dashboard', [$countySlug, $constSlug]),
            'phases_url' => route('const.app.app_phases', [$countySlug, $constSlug]),
        ];
    }

    #[Computed]
    public function analysisTable(): array
    {
        return match ($this->analysisDimension) {
            'sectors' => $this->economicSectors->map(fn ($row) => [
                'label' => $row['label'],
                'count' => number_format($row['count']),
                'amount' => 'KES ' . number_format($row['amount']),
                'share' => $row['percentage'] . '%',
            ])->all(),
            'recovery' => [[
                'label' => 'Expected payments',
                'count' => '—',
                'amount' => 'KES ' . number_format($this->performance['due']),
                'share' => '100%',
            ], [
                'label' => 'Collected',
                'count' => '—',
                'amount' => 'KES ' . number_format($this->performance['repaid']),
                'share' => $this->performance['recovery_rate'] . '%',
            ], [
                'label' => 'Portfolio at risk',
                'count' => number_format($this->riskProfile['count']) . ' groups',
                'amount' => 'KES ' . number_format($this->performance['deficit']),
                'share' => number_format($this->performance['financial_rate'], 1) . '%',
            ]],
            default => collect($this->demographics)
                ->filter(fn ($row) => ! empty($row['cat']))
                ->map(fn ($row) => [
                    'label' => $row['label'],
                    'count' => number_format($row['val']),
                    'amount' => 'KES ' . number_format($row['amount']),
                    'share' => ($this->getStats['totalAmountIssued'] ?? 0) > 0
                        ? round(($row['amount'] / $this->getStats['totalAmountIssued']) * 100, 1) . '%'
                        : '0%',
                ])->all(),
        };
    }

    #[Computed]
    public function chartPayload(): array
    {
        $demographicRows = collect($this->demographics)->filter(fn ($row) => ! empty($row['cat']));

        return [
            'portfolio' => [
                'labels' => ['Approved', 'Disbursed', 'Repaid', 'Outstanding'],
                'values' => [
                    (float) ($this->getStats['totalAmountApproved'] ?? 0),
                    (float) ($this->getStats['totalAmountIssued'] ?? 0),
                    (float) ($this->getStats['totalAmountRepaid'] ?? 0),
                    (float) ($this->getStats['outstandingBalance'] ?? 0),
                ],
            ],
            'demographics' => [
                'labels' => $demographicRows->pluck('label')->all(),
                'values' => $demographicRows->pluck('amount')->map(fn ($v) => (float) $v)->all(),
            ],
            'sectors' => [
                'labels' => $this->economicSectors->pluck('label')->all(),
                'values' => $this->economicSectors->pluck('amount')->map(fn ($v) => (float) $v)->all(),
            ],
            'recovery' => [
                'labels' => ['Collected', 'Outstanding'],
                'values' => [
                    (float) ($this->performance['repaid'] ?? 0),
                    (float) max(0, ($this->performance['due'] ?? 0) - ($this->performance['repaid'] ?? 0)),
                ],
            ],
            'wards' => [
                'labels' => $this->wardRanking->pluck('ward_name')->map(fn ($n) => ucwords(strtolower($n)))->all(),
                'values' => $this->wardRanking->pluck('recovery_rate')->map(fn ($v) => (float) $v)->all(),
            ],
            'activeDimension' => $this->analysisDimension,
        ];
    }

    public function render()
    {
        return view('livewire.constituency.constituency-dashboard-v2');
    }
}
