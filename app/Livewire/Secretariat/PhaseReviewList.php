<?php

namespace App\Livewire\Secretariat;

use App\Models\LoanPhase;
use App\Services\Loan\SecretariatApprovalService;
use Livewire\Component;
use Livewire\WithPagination;

class PhaseReviewList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $filter = 'pending';

    protected SecretariatApprovalService $secretariatService;

    public function boot(SecretariatApprovalService $secretariatService): void
    {
        $this->secretariatService = $secretariatService;
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
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
