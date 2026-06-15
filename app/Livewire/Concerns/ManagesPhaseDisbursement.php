<?php

namespace App\Livewire\Concerns;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;

trait ManagesPhaseDisbursement
{
    public $disbursementFailureReason = '';
    public $disbursementFailureNotes = '';

    protected function phaseAllowsDisbursementRecording(LoanPhase $phase): bool
    {
        return in_array($phase->status, ['in_training', 'approved', 'disbursed'], true);
    }

    public function canMarkDisbursed(PhaseApplication $application, LoanPhase $phase): bool
    {
        if (!$phase->canManageDisbursementBy(auth()->user())) {
            return false;
        }

        if (!$this->phaseAllowsDisbursementRecording($phase)) {
            return false;
        }

        if (!$application->is_trained) {
            return false;
        }

        if (!in_array($application->status, ['in_training', 'pending_disbursement'], true)) {
            return false;
        }

        return true;
    }

    public function eligibleForDisbursementQuery(LoanPhase $phase)
    {
        return $phase->phaseApplications()
            ->where('is_trained', true)
            ->whereIn('status', ['in_training', 'pending_disbursement']);
    }

    public function eligibleForDisbursementCount(LoanPhase $phase): int
    {
        return $this->eligibleForDisbursementQuery($phase)->count();
    }

    public function canBatchDisbursePhase(LoanPhase $phase): bool
    {
        if (!$phase->canManageDisbursementBy(auth()->user())) {
            return false;
        }

        if (!$this->phaseAllowsDisbursementRecording($phase)) {
            return false;
        }

        return $this->eligibleForDisbursementCount($phase) > 0;
    }

    public function canMarkNotDisbursed(PhaseApplication $application, LoanPhase $phase): bool
    {
        return $this->canMarkDisbursed($application, $phase);
    }

    public function disbursementFailureReasonOptions(): array
    {
        return [
            ['id' => 'group_no_show', 'name' => 'Group did not collect cheque / no show'],
            ['id' => 'insufficient_collateral', 'name' => 'Incomplete disbursement requirements'],
            ['id' => 'eligibility_criteria', 'name' => 'Eligibility issue at disbursement'],
            ['id' => 'other', 'name' => 'Other (specify below)'],
        ];
    }
}
