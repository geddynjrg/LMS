<?php

namespace App\Livewire\Concerns;

use App\Models\LoanPhase;
use App\Services\Loan\PhaseDetailEditService;
use Illuminate\Validation\ValidationException;

trait ManagesPhaseDetailEditing
{
    public ?int $editingPhaseId = null;

    public string $editPhaseName = '';
    public ?string $editReviewDate = null;
    public string $editReviewVenue = '';
    public ?string $editAmountDisbursed = null;
    public ?string $editAmountRepaid = null;
    public ?string $editDisbursedAt = null;
    public ?string $editTrainingStartDate = null;
    public ?string $editTrainingEndDate = null;
    public string $editTrainingVenue = '';
    public string $editTrainingNotes = '';

    public function canEditPhaseDetails(LoanPhase $phase): bool
    {
        return $phase->canEditDetailsBy(auth()->user());
    }

    public function canDeletePhase(LoanPhase $phase): bool
    {
        return $phase->canDeleteBy(auth()->user());
    }

    public function openEditPhaseModal(int $phaseId): void
    {
        $phase = LoanPhase::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->findOrFail($phaseId);

        if (!$this->canEditPhaseDetails($phase)) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot edit this phase.',
                type: 'error'
            );
            return;
        }

        $this->editingPhaseId = $phase->id;
        $this->editPhaseName = $phase->phase ?? '';
        $this->editReviewDate = $phase->review_date?->format('Y-m-d');
        $this->editReviewVenue = $phase->review_venue ?? '';
        $this->editAmountDisbursed = $phase->amount_disbursed !== null ? (string) $phase->amount_disbursed : null;
        $this->editAmountRepaid = $phase->amount_repaid !== null ? (string) $phase->amount_repaid : null;
        $this->editDisbursedAt = $phase->disbursed_at?->format('Y-m-d');
        $this->editTrainingStartDate = $phase->training_start_date?->format('Y-m-d');
        $this->editTrainingEndDate = $phase->training_end_date?->format('Y-m-d');
        $this->editTrainingVenue = $phase->training_venue ?? '';
        $this->editTrainingNotes = $phase->training_notes ?? '';

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'edit-phase-details-modal');
    }

    public function savePhaseDetails(): void
    {
        if (!$this->editingPhaseId) {
            return;
        }

        $phase = LoanPhase::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->findOrFail($this->editingPhaseId);

        try {
            app(PhaseDetailEditService::class)->updateDetails($phase, auth()->user(), [
                'phaseName' => $this->editPhaseName,
                'reviewDate' => $this->editReviewDate,
                'reviewVenue' => $this->editReviewVenue,
                'amountDisbursed' => $this->editAmountDisbursed,
                'amountRepaid' => $this->editAmountRepaid,
                'disbursedAt' => $this->editDisbursedAt,
                'trainingStartDate' => $this->editTrainingStartDate,
                'trainingEndDate' => $this->editTrainingEndDate,
                'trainingVenue' => $this->editTrainingVenue,
                'trainingNotes' => $this->editTrainingNotes,
            ]);

            $this->resetPhaseEditForm();
            $this->dispatch('close-modal', name: 'edit-phase-details-modal');
            $this->dispatch('swal:toast',
                title: 'Phase Updated',
                message: 'Phase details saved successfully.',
                type: 'success'
            );

            if (method_exists($this, 'refreshData')) {
                $this->refreshData();
            } elseif (method_exists($this, 'getLoanPhases')) {
                $this->getLoanPhases();
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            $this->dispatch('swal:toast',
                title: 'Validation Error',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast',
                title: 'Update Failed',
                message: $e->getMessage() ?: 'Could not save phase details.',
                type: 'error'
            );
        }
    }

    public function editingPhase(): ?LoanPhase
    {
        if (!$this->editingPhaseId) {
            return null;
        }

        return LoanPhase::find($this->editingPhaseId);
    }

    public function editingPhaseAllows(string $field): bool
    {
        $phase = $this->editingPhase();

        return $phase
            ? in_array($field, app(PhaseDetailEditService::class)->editableFieldsFor($phase), true)
            : false;
    }

    protected function resetPhaseEditForm(): void
    {
        $this->reset([
            'editingPhaseId',
            'editPhaseName',
            'editReviewDate',
            'editReviewVenue',
            'editAmountDisbursed',
            'editAmountRepaid',
            'editDisbursedAt',
            'editTrainingStartDate',
            'editTrainingEndDate',
            'editTrainingVenue',
            'editTrainingNotes',
        ]);
    }
}
