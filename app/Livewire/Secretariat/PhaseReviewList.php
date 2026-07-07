<?php

namespace App\Livewire\Secretariat;

use App\Models\LoanPhase;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Loan\SecretariatApprovalService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PhaseReviewList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $filter = 'pending';

    protected SecretariatApprovalService $secretariatService;
    protected LoanAnalysisService $analysisService;

    public function boot(
        SecretariatApprovalService $secretariatService,
        LoanAnalysisService $analysisService,
    ): void {
        $this->secretariatService = $secretariatService;
        $this->analysisService = $analysisService;
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function applicationsByConstituency(): array
    {
        return $this->analysisService->getApplicationsPerConstituency([], 20);
    }

    #[Computed]
    public function applicationVolumeStats(): array
    {
        $rows = collect($this->applicationsByConstituency);

        return [
            'total' => (int) $rows->sum('application_count'),
            'constituency_count' => $rows->count(),
            'top_name' => $rows->first()?->constituency_name,
            'top_count' => (int) ($rows->first()?->application_count ?? 0),
        ];
    }

    public function mount(): void
    {
        $this->dispatchApplicationVolumeChart();
    }

    protected function applicationVolumeChartPayload(): array
    {
        $rows = collect($this->applicationsByConstituency);

        return [
            'labels' => $rows->pluck('constituency_name')->all(),
            'values' => $rows->pluck('application_count')->map(fn ($v) => (int) $v)->all(),
            'total' => (int) $rows->sum('application_count'),
        ];
    }

    protected function dispatchApplicationVolumeChart(): void
    {
        $this->dispatch('secretariat-application-volume-updated', chart: $this->applicationVolumeChartPayload());
    }

    public function render()
    {
        $phasesQuery = LoanPhase::query()
            ->whereIn('status', ['approved', 'in_training', 'pending_disbursement'])
            ->with([
                'constituency:constituency_code,constituency_name',
                'secretariatApprovals',
                'hqOfficer:id,name',
            ])
            ->orderByDesc('hq_approved_at');

        if ($this->filter === 'pending') {
            $phasesQuery->where(function ($query) {
                $query->whereDoesntHave('secretariatApprovals', fn ($q) => $q
                    ->where('approval_type', 'post_hq_review')
                    ->whereNotNull('approved_at'))
                    ->orWhereDoesntHave('secretariatApprovals', fn ($q) => $q
                        ->where('approval_type', 'fund_transfer')
                        ->whereNotNull('approved_at'))
                    ->orWhereDoesntHave('secretariatApprovals', fn ($q) => $q
                        ->where('approval_type', 'training_authorization')
                        ->whereNotNull('approved_at'))
                    ->orWhereDoesntHave('secretariatApprovals', fn ($q) => $q
                        ->where('approval_type', 'final_disbursement')
                        ->whereNotNull('approved_at'));
            });
        } elseif ($this->filter === 'completed') {
            $phasesQuery->whereHas('secretariatApprovals', fn ($q) => $q
                ->where('approval_type', 'final_disbursement')
                ->whereNotNull('approved_at'));
        }

        return view('livewire.secretariat.phase-review-list', [
            'phases' => $phasesQuery->paginate(15),
            'stats' => $this->secretariatService->dashboardStats(),
        ]);
    }
}
