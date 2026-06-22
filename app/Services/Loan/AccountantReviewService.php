<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Support\AccountantReviewStatus;
use App\Support\PhaseDocumentType;
use Illuminate\Validation\ValidationException;

class AccountantReviewService
{
    public function phaseHasRequiredDocuments(LoanPhase $phase): bool
    {
        $phase->loadMissing('phaseApplicationDocuments');

        return $phase->phaseApplicationDocuments
            ->pluck('document_type')
            ->intersect(PhaseDocumentType::processedCodeListTypes())
            ->isNotEmpty()
            && $phase->phaseApplicationDocuments->contains('document_type', PhaseDocumentType::BANK_RECONCILIATION);
    }

    public function queueForReview(LoanPhase $phase): void
    {
        if (! $this->phaseHasRequiredDocuments($phase)) {
            return;
        }

        if ($phase->accountant_review_status === AccountantReviewStatus::APPROVED) {
            return;
        }

        $phase->update([
            'accountant_review_status' => AccountantReviewStatus::PENDING,
        ]);
    }

    public function approve(LoanPhase $phase, string $notes): void
    {
        $this->assertCanReview($phase);

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

        if (! $this->phaseHasRequiredDocuments($phase)) {
            throw ValidationException::withMessages([
                'phase' => 'Processed code list and bank reconciliation must be uploaded before accounts review.',
            ]);
        }
    }
}
