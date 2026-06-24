<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Services\WorkflowNotificationService;
use App\Support\AccountantReviewStatus;
use App\Support\PhaseDocumentType;
use Illuminate\Validation\ValidationException;

class AccountantReviewService
{
    public function phaseHasProcessedCodeList(LoanPhase $phase): bool
    {
        $phase->loadMissing('phaseApplicationDocuments');

        return $phase->phaseApplicationDocuments
            ->pluck('document_type')
            ->intersect(PhaseDocumentType::processedCodeListTypes())
            ->isNotEmpty();
    }

    public function phaseHasBankReconciliation(LoanPhase $phase): bool
    {
        return app(BankReconciliationService::class)->hasAllRequiredReconciliations($phase);
    }

    public function phaseHasRequiredDocuments(LoanPhase $phase): bool
    {
        return $this->phaseHasProcessedCodeList($phase)
            && $this->phaseHasBankReconciliation($phase);
    }

    public function isEligibleForDashboard(LoanPhase $phase): bool
    {
        return $phase->status === 'submitted_to_hq'
            && $this->phaseHasProcessedCodeList($phase);
    }

    public function queueForReview(LoanPhase $phase): void
    {
        if (! $this->isEligibleForDashboard($phase)) {
            return;
        }

        if ($phase->accountant_review_status === AccountantReviewStatus::APPROVED) {
            return;
        }

        if ($phase->accountant_review_status === AccountantReviewStatus::RETURNED_TO_HQ) {
            return;
        }

        $alreadyPending = $phase->accountant_review_status === AccountantReviewStatus::PENDING;

        $phase->update([
            'accountant_review_status' => AccountantReviewStatus::PENDING,
        ]);

        if ($alreadyPending) {
            return;
        }

        try {
            app(WorkflowNotificationService::class)->notifyAccountantPhaseQueued($phase->fresh());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify accountant of queued phase', [
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resubmitToAccountant(LoanPhase $phase, string $hqNotes): void
    {
        if ($phase->status !== 'submitted_to_hq') {
            throw ValidationException::withMessages([
                'phase' => 'This phase is not with HQ for accounts resubmission.',
            ]);
        }

        if (! $this->phaseAwaitingHqResubmitToAccountant($phase)) {
            throw ValidationException::withMessages([
                'phase' => 'This phase is not awaiting HQ resubmission to accounts.',
            ]);
        }

        if (! $this->phaseHasProcessedCodeList($phase)) {
            throw ValidationException::withMessages([
                'phase' => 'Regenerate the processed code list before resubmitting to accounts.',
            ]);
        }

        if (strlen(trim($hqNotes)) < 20) {
            throw ValidationException::withMessages([
                'hqNotes' => 'Please describe what HQ has corrected before resubmitting to accounts (at least 20 characters).',
            ]);
        }

        $phase->update([
            'accountant_review_status' => AccountantReviewStatus::PENDING,
            'hq_accountant_resubmit_notes' => trim($hqNotes),
            'hq_accountant_resubmitted_at' => now(),
            'hq_accountant_resubmitted_by' => auth()->id(),
        ]);

        try {
            app(WorkflowNotificationService::class)->notifyAccountantPhaseResubmitted($phase->fresh(), trim($hqNotes));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify accountant of HQ resubmission', [
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function phaseAwaitingHqResubmitToAccountant(LoanPhase $phase): bool
    {
        if ($phase->accountant_review_status === AccountantReviewStatus::RETURNED_TO_HQ) {
            return true;
        }

        // Recovery when a return was overwritten back to pending by the old accounts sync job.
        return $phase->accountant_review_status === AccountantReviewStatus::PENDING
            && filled($phase->accountant_notes)
            && $phase->accountant_reviewed_at !== null
            && blank($phase->hq_accountant_resubmit_notes);
    }

    /**
     * Backfill accountant queue for HQ-submitted phases that already have a processed code list.
     */
    public function syncPendingQueues(): void
    {
        LoanPhase::query()
            ->where('status', 'submitted_to_hq')
            ->whereHas('phaseApplicationDocuments', fn ($q) => $q->whereIn(
                'document_type',
                PhaseDocumentType::processedCodeListTypes()
            ))
            ->where(function ($q) {
                $q->whereNull('accountant_review_status');
            })
            ->each(fn (LoanPhase $phase) => $this->queueForReview($phase));
    }

    public function approve(LoanPhase $phase, string $notes): void
    {
        $this->assertCanReview($phase);
        $this->assertCanApprove($phase);

        if (trim($notes) === '') {
            throw ValidationException::withMessages([
                'notes' => 'Please add notes when forwarding to HQ management for approval.',
            ]);
        }

        $phase->update([
            'accountant_review_status' => AccountantReviewStatus::APPROVED,
            'accountant_notes' => $notes,
            'accountant_reviewed_at' => now(),
            'accountant_reviewed_by' => auth()->id(),
        ]);
    }

    public function returnToHq(LoanPhase $phase, string $comments): void
    {
        $this->assertCanReview($phase);

        if (strlen(trim($comments)) < 20) {
            throw ValidationException::withMessages([
                'comments' => 'Please provide clarification notes for HQ (at least 20 characters).',
            ]);
        }

        $phase->update([
            'accountant_review_status' => AccountantReviewStatus::RETURNED_TO_HQ,
            'accountant_notes' => $comments,
            'accountant_reviewed_at' => now(),
            'accountant_reviewed_by' => auth()->id(),
        ]);
    }

    public function isApprovedForHqManagement(LoanPhase $phase): bool
    {
        return $phase->accountant_review_status === AccountantReviewStatus::APPROVED;
    }

    public function hasManagementProposalMinutes(LoanPhase $phase): bool
    {
        return $phase->phaseApplicationDocuments()
            ->where('document_type', PhaseDocumentType::MANAGEMENT_PROPOSAL_REVIEW_MINUTES)
            ->exists();
    }

    protected function assertCanReview(LoanPhase $phase): void
    {
        if ($phase->status !== 'submitted_to_hq') {
            throw ValidationException::withMessages([
                'phase' => 'This phase is not awaiting accounts review.',
            ]);
        }

        if (! $this->phaseHasProcessedCodeList($phase)) {
            throw ValidationException::withMessages([
                'phase' => 'The processed code list must be on file before accounts review.',
            ]);
        }
    }

    protected function assertCanApprove(LoanPhase $phase): void
    {
        if (! $this->phaseHasBankReconciliation($phase)) {
            throw ValidationException::withMessages([
                'phase' => 'Both current bank reconciliations (main account and loan repayment) must be on file before accounts can approve and forward to HQ management.',
            ]);
        }
    }
}
