<?php

namespace App\Livewire\Accountant;

use App\Models\LoanPhase;
use App\Support\AccountantReviewStatus;
use App\Support\PhaseDocumentType;
use Livewire\Component;
use Livewire\WithPagination;

class PhaseReviewList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $filter = 'pending';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = LoanPhase::query()
            ->where('status', 'submitted_to_hq')
            ->with([
                'constituency:constituency_code,constituency_name',
                'phaseApplicationDocuments',
                'hqOfficer:id,name',
            ])
            ->whereHas('phaseApplicationDocuments', fn ($q) => $q->where('document_type', PhaseDocumentType::APPROVED_CODE_LIST))
            ->whereHas('phaseApplicationDocuments', fn ($q) => $q->where('document_type', PhaseDocumentType::BANK_RECONCILIATION))
            ->orderByDesc('submitted_to_hq_at');

        if ($this->filter === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('accountant_review_status')
                    ->orWhere('accountant_review_status', AccountantReviewStatus::PENDING)
                    ->orWhere('accountant_review_status', AccountantReviewStatus::RETURNED_TO_HQ);
            });
        } elseif ($this->filter === 'approved') {
            $query->where('accountant_review_status', AccountantReviewStatus::APPROVED);
        }

        return view('livewire.accountant.phase-review-list', [
            'phases' => $query->paginate(15),
        ]);
    }
}
