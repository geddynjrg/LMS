<?php

namespace App\Livewire\Secretariat;

use App\Livewire\Concerns\ManagesApplicationVolumeRanking;
use App\Models\LoanPhase;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Loan\SecretariatApprovalService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class PhaseReviewList extends Component
{
    use ManagesApplicationVolumeRanking, WithPagination;

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

    public function mount(): void
    {
        $requested = request()->query('filter') ?? request()->query('category');
        if (is_string($requested) && in_array($requested, $this->allowedFilters(), true)) {
            $this->filter = $requested;
        }
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, $this->allowedFilters(), true)) {
            return;
        }

        $this->filter = $filter;
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function updatedApplicationVolumeSearch(): void
    {
        unset($this->filteredApplicationVolumeRows);
    }

    protected function allowedFilters(): array
    {
        return [
            'pending',
            'pending_training',
            'training_authorized',
            'disbursement_authorized',
            'pending_disbursement',
            'all',
            'completed',
        ];
    }

    public function render()
    {
        $basePhases = LoanPhase::query()
            ->whereIn('status', ['approved', 'in_training', 'pending_disbursement'])
            ->with([
                'constituency:constituency_code,constituency_name',
                'secretariatApprovals',
                'hqOfficer:id,name',
            ])
            ->orderByDesc('hq_approved_at')
            ->get()
            ->filter(fn (LoanPhase $phase) => $this->secretariatService->phaseMatchesCategory($phase, $this->filter))
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;

        $phases = new LengthAwarePaginator(
            $basePhases->forPage($page, $perPage)->values(),
            $basePhases->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );

        return view('livewire.secretariat.phase-review-list', [
            'phases' => $phases,
            'stats' => $this->secretariatService->dashboardStats(),
            'actionInbox' => $this->secretariatService->buildActionInbox(),
            'filterLabel' => $this->secretariatService->categoryLabel($this->filter),
        ]);
    }
}
