<?php

namespace App\Livewire\Hq;

use App\Livewire\Hq\Concerns\ManagesHqDashboardData;
use App\Models\LoanPhase;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Loan\SecretariatApprovalService;
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

    public function isSecretariatDashboard(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('head_of_secretariat')
            && !$user->hasAnyRole(['hq_officer', 'admin', 'super-admin']);
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
        if ($this->isSecretariatDashboard()) {
            return $this->buildSecretariatActionInbox();
        }

        $phaseQuery = LoanPhase::query()
            ->with('constituency')
            ->where('status', 'submitted_to_hq')
            ->orderByDesc('submitted_to_hq_at');

        $pendingPhases = $phaseQuery->limit(8)->get();

        return [
            'mode' => 'hq',
            'inbox_title' => 'Action inbox',
            'inbox_badge' => 'HQ review',
            'inbox_empty' => 'No phases awaiting HQ review.',
            'tasks_title' => 'Phases awaiting review',
            'tasks_queue_url' => route('hq.submissions.tasks.phases'),
            'tasks_queue_label' => 'Open task queue',
            'pending_phase_count' => (clone $phaseQuery)->count(),
            'pending_phases' => $pendingPhases->map(fn ($phase) => [
                'label' => $phase->phase_ref ?? ('Phase ' . $phase->phase),
                'constituency' => $phase->constituency?->constituency_name ?? 'Unknown',
                'submitted_at' => $phase->submitted_to_hq_at?->format('M d, Y') ?? 'N/A',
                'meta' => null,
                'url' => route('hq.submissions.phase.review', $phase->slug),
                'action_label' => 'Review',
                'secondary_url' => null,
                'secondary_label' => null,
            ])->all(),
            'new_applications' => collect($this->getStats['newApplications'] ?? [])->take(5)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSecretariatActionInbox(): array
    {
        $service = app(SecretariatApprovalService::class);

        $pending = LoanPhase::query()
            ->with(['constituency', 'secretariatApprovals'])
            ->whereIn('status', ['approved', 'in_training', 'pending_disbursement'])
            ->orderByDesc('hq_approved_at')
            ->get()
            ->filter(fn (LoanPhase $phase) => $service->nextPendingApprovalType($phase) !== null)
            ->values();

        return [
            'mode' => 'secretariat',
            'inbox_title' => 'Authorization inbox',
            'inbox_badge' => 'Secretariat',
            'inbox_empty' => 'No phases awaiting secretariat authorization.',
            'tasks_title' => 'Phases awaiting authorization',
            'tasks_queue_url' => route('secretariat.phases.index', ['filter' => 'pending']),
            'tasks_queue_label' => 'Open secretariat queue',
            'pending_phase_count' => $pending->count(),
            'pending_phases' => $pending->take(8)->map(function (LoanPhase $phase) use ($service) {
                $next = $service->nextPendingApprovalType($phase);
                $progress = $service->approvalProgress($phase);

                return [
                    'label' => $phase->phase_ref ?? $phase->phase,
                    'constituency' => $phase->constituency?->constituency_name ?? 'Unknown',
                    'submitted_at' => $phase->hq_approved_at?->format('M d, Y') ?? 'N/A',
                    'meta' => $service->approvalTypeLabel($next) . ' · ' . $progress['done'] . '/' . $progress['total'],
                    'url' => route('secretariat.phases.review', $phase),
                    'action_label' => $progress['done'] === 0 ? 'Authorize' : 'Continue',
                    'secondary_url' => route('secretariat.phases.code-list', $phase),
                    'secondary_label' => 'Code list',
                ];
            })->all(),
            'new_applications' => [],
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
            'applicationVolume' => [
                'labels' => collect($this->getStats['applicationsPerConstituency'] ?? [])->pluck('constituency_name')->all(),
                'values' => collect($this->getStats['applicationsPerConstituency'] ?? [])->pluck('application_count')->map(fn ($v) => (int) $v)->all(),
            ],
            'activeDimension' => $this->analysisDimension,
        ];
    }

    public function render()
    {
        return view('livewire.hq.hq-dashboard-v2');
    }
}
