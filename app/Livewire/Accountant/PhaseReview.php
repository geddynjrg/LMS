<?php

namespace App\Livewire\Accountant;

use App\Models\LoanPhase;
use App\Services\Loan\AccountantReviewService;
use App\Services\Loan\LoanPhaseService;
use App\Support\PhaseDocumentType;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PhaseReview extends Component
{
    public LoanPhase $phase;

    public string $notes = '';

    public string $returnComments = '';

    public string $returnTarget = 'hq';

    protected AccountantReviewService $accountantService;

    protected LoanPhaseService $phaseService;

    public function boot(AccountantReviewService $accountantService, LoanPhaseService $phaseService): void
    {
        $this->accountantService = $accountantService;
        $this->phaseService = $phaseService;
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

    public function returnWithComments(): void
    {
        try {
            if ($this->returnTarget === 'constituency') {
                $this->accountantService->returnToConstituency(
                    $this->phase,
                    $this->returnComments,
                    $this->phaseService
                );
            } else {
                $this->accountantService->returnToHq($this->phase, $this->returnComments);
            }

            $this->redirect(route('accountant.phases.index'), navigate: false);
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Cannot return',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        }
    }

    public function documentOfType(string $type)
    {
        return $this->phase->phaseApplicationDocuments->firstWhere('document_type', $type);
    }

    public function render()
    {
        return view('livewire.accountant.phase-review', [
            'codeListDoc' => $this->documentOfType(PhaseDocumentType::APPROVED_CODE_LIST),
            'bankReconDoc' => $this->documentOfType(PhaseDocumentType::BANK_RECONCILIATION),
            'minutesDoc' => $this->documentOfType(PhaseDocumentType::MINUTES),
        ]);
    }
}
