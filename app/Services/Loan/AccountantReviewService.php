<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
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
        $phase->loadMissing('phaseApplicationDocuments');

        return $phase->phaseApplicationDocuments
            ->contains('document_type', PhaseDocumentType::BANK_RECONCILIATION);
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

        $phase->update([
            'accountant_review_status' => AccountantReviewStatus::PENDING,
        ]);
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
                $q->whereNull('accountant_review_status')
                    ->orWhere('accountant_review_status', AccountantReviewStatus::RETURNED_TO_HQ);
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
                'phase' => 'The HQ processed code list must be uploaded before accounts review.',
            ]);
        }
    }

    protected function assertCanApprove(LoanPhase $phase): void
    {
        if (! $this->phaseHasBankReconciliation($phase)) {
            throw ValidationException::withMessages([
                'phase' => 'Bank reconciliation must be on file before accounts can approve and forward to HQ management.',
            ]);
        }
    }
}
