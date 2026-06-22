<?php

namespace App\Livewire\Accountant;

use App\Models\LoanPhase;
use App\Services\Loan\AccountantReviewService;
use App\Support\PhaseDocumentType;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PhaseReview extends Component
{
    public LoanPhase $phase;

    public string $notes = '';

    public string $returnComments = '';

    protected AccountantReviewService $accountantService;

    public function boot(AccountantReviewService $accountantService): void
    {
        $this->accountantService = $accountantService;
    }

    public function mount(LoanPhase $phase): void
    {
        $this->phase = $phase->load([
            'constituency:constituency_code,constituency_name',
            'phaseApplicationDocuments',
            'hqOfficer:id,name',
            'accountantReviewer:id,name',
        ]);
    }

    public function approveForHq(): void
    {
        try {
            $this->accountantService->approve($this->phase, $this->notes);
            $this->redirect(route('accountant.phases.index', ['filter' => 'approved']), navigate: false);
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Cannot approve',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        }
    }

    public function returnToHqForClarification(): void
    {
        try {
            $this->accountantService->returnToHq($this->phase, $this->returnComments);
            $this->redirect(route('accountant.phases.index'), navigate: false);
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Cannot return to HQ',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        }
    }

    public function documentOfType(string $type)
    {
        return $this->phase->phaseApplicationDocuments->firstWhere('document_type', $type);
    }

    public function processedCodeListDocument()
    {
        return $this->phase->phaseApplicationDocuments
            ->first(fn ($doc) => in_array($doc->document_type, PhaseDocumentType::processedCodeListTypes(), true));
    }

    public function render()
    {
        return view('livewire.accountant.phase-review', [
            'codeListDoc' => $this->processedCodeListDocument(),
            'bankReconDoc' => $this->documentOfType(PhaseDocumentType::BANK_RECONCILIATION),
            'transferLetterDoc' => $this->documentOfType(PhaseDocumentType::REQUEST_FOR_TRANSFER_LETTER),
        ]);
    }
}
