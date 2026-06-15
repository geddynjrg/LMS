<?php

namespace App\Livewire\Hq;

use App\Livewire\Hq\Concerns\ManagesHqDashboardData;
use App\Models\LoanPhase;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\LocationService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class HqDashboardV2 extends Component
{
    use ManagesHqDashboardData;

    /** @var 'overview'|'data'|'analyze'|'tasks' */
    public string $workspace = 'overview';

    /** @var 'demographics'|'sectors'|'recovery' */
    public string $analysisDimension = 'demographics';

    public function boot(
        LoanAnalysisService $analysisService,
        AppCacheService $cacheService,
        LocationService $locationService
    ) {
        $this->bootManagesHqDashboardData($analysisService, $cacheService, $locationService);
    }

    public function mount()
    {
        $this->mountManagesHqDashboardData();
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

    public function resetFilters(): void
    {
        $this->reset(['wardFilter', 'constituencyFilter', 'countyFilter']);
        $this->invalidateV2Computed();
        $this->dispatchChartUpdate();
    }

    public function updated($property): void
    {
        if (! in_array($property, ['countyFilter', 'constituencyFilter', 'wardFilter'], true)) {
            return;
        }

        $this->invalidateV2Computed();
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

    protected function invalidateV2Computed(): void
    {
        unset($this->actionInbox, $this->dataRows, $this->chartPayload, $this->analysisTable);
    }

    protected function dispatchChartUpdate(): void
    {
        $payload = $this->chartPayload;
        $encoded = json_encode($payload);

        $this->dispatch('hq-dashboard-charts-updated', charts: $payload);
        $this->js('window.__hqDashboardChartPayload = ' . $encoded . '; window.renderHqDashboardCharts && window.renderHqDashboardCharts(' . $encoded . ');');
    }

    #[Computed]
    public function actionInbox(): array
    {
        $phaseQuery = LoanPhase::query()
            ->with('constituency')
            ->where('status', 'submitted_to_hq')
            ->orderByDesc('submitted_to_hq_at');

        $pendingPhases = $phaseQuery->limit(8)->get();

        return [
            'pending_phase_count' => (clone $phaseQuery)->count(),
            'pending_phases' => $pendingPhases->map(fn ($phase) => [
                'label' => $phase->phase_ref ?? ('Phase ' . $phase->phase),
                'constituency' => $phase->constituency?->constituency_name ?? 'Unknown',
                'submitted_at' => $phase->submitted_to_hq_at?->format('M d, Y') ?? 'N/A',
                'url' => route('hq.submissions.phase.review', $phase->slug),
            ])->all(),
            'new_applications' => collect($this->getStats['newApplications'] ?? [])->take(5)->values()->all(),
        ];
    }

    #[Computed]
    public function dataRows(): array
    {
        $top = collect($this->getStats['topPerformingConstituencies'] ?? []);
        $bottom = collect($this->getStats['bottomPerformingConstituencies'] ?? []);

        if ($top->isNotEmpty()) {
            return $top->map(fn ($row) => [
                'name' => $row->constituency_name,
                'metric' => 'Recovery rate',
                'value' => number_format($row->recovery_rate, 1) . '%',
                'secondary' => 'Repaid KES ' . number_format($row->total_repaid ?? 0),
                'tone' => 'success',
            ])->all();
        }

        return $bottom->map(fn ($row) => [
            'name' => $row->constituency_name,
            'metric' => 'Recovery rate',
            'value' => number_format($row->recovery_rate, 1) . '%',
            'secondary' => 'Arrears KES ' . number_format($row->total_arrears ?? 0),
            'tone' => 'danger',
        ])->all();
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
            'constituencies' => [
                'labels' => collect($this->getStats['topPerformingConstituencies'] ?? [])->pluck('constituency_name')->all(),
                'values' => collect($this->getStats['topPerformingConstituencies'] ?? [])->pluck('recovery_rate')->map(fn ($v) => (float) $v)->all(),
            ],
            'activeDimension' => $this->analysisDimension,
        ];
    }

    public function render()
    {
        return view('livewire.hq.hq-dashboard-v2');
    }
}
