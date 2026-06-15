<?php

namespace App\Livewire\Secretariat;

use App\Models\LoanPhase;
use App\Models\SecretariatApproval;
use App\Services\Loan\SecretariatApprovalService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class PhaseReview extends Component
{
    use WithFileUploads;

    public LoanPhase $phase;
    public string $approvalType = SecretariatApproval::TYPE_POST_HQ_REVIEW;
    public string $notes = '';
    public $signatureFile;

    protected SecretariatApprovalService $secretariatService;

    public function boot(SecretariatApprovalService $secretariatService): void
    {
        $this->secretariatService = $secretariatService;
    }

    public function mount(LoanPhase $phase): void
    {
        $this->phase = $phase->load([
            'secretariatApprovals.approver:id,name',
            'hqOfficer:id,name',
            'assignedHqOfficer:id,name',
            'phaseApplicationDocuments',
            'constituency:constituency_code,constituency_name',
        ]);

        $next = $this->secretariatService->nextPendingApprovalType($this->phase);
        if ($next) {
            $this->approvalType = $next;
        }
    }

    public function submitApproval(): void
    {
        $next = $this->secretariatService->nextPendingApprovalType($this->phase);
        if (!$next) {
            $this->redirect(route('secretariat.phases.index', ['filter' => 'pending']), navigate: false);

            return;
        }

        $this->approvalType = $next;

        $approvalTypes = $this->secretariatService->approvalTypesForPhase($this->phase);
        $allowedTypes = array_keys($approvalTypes);

        $this->validate([
            'approvalType' => 'required|in:' . implode(',', $allowedTypes),
            'signatureFile' => 'required|file|mimes:png,jpg,jpeg,pdf|max:2048',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->secretariatService->recordApproval(
                $this->phase,
                $this->approvalType,
                $this->signatureFile,
                $this->notes ?: null,
            );

            $this->phase->refresh()->load(['secretariatApprovals.approver:id,name']);
            $this->reset('signatureFile', 'notes');

            $next = $this->secretariatService->nextPendingApprovalType($this->phase);
            if ($next) {
                $this->approvalType = $next;

                $this->dispatch('swal:toast',
                    title: 'Approval recorded',
                    message: 'Saved. Next: ' . $this->secretariatService->approvalTypeLabel($next) . '.',
                    type: 'success'
                );

                return;
            }

            $nextPhase = $this->secretariatService->nextPendingPhase($this->phase->id);

            session()->flash('secretariat_completed', [
                'phase_label' => $this->phase->phase,
                'phase_ref' => $this->phase->phase_ref,
                'constituency' => $this->phase->constituency?->constituency_name ?? $this->phase->constituency_code,
                'next_phase_url' => $nextPhase
                    ? route('secretariat.phases.review', $nextPhase)
                    : null,
                'next_phase_label' => $nextPhase
                    ? trim(($nextPhase->constituency?->constituency_name ?? $nextPhase->constituency_code) . ' · ' . $nextPhase->phase)
                    : null,
            ]);

            $this->redirect(route('secretariat.phases.index', ['filter' => 'pending']), navigate: false);

            return;
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            $this->dispatch('swal:toast', title: 'Cannot approve', message: $message, type: 'error');
        } catch (\Throwable $e) {
            logger()->error('Secretariat approval failed: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Error',
                message: 'Could not save approval: ' . $e->getMessage(),
                type: 'error'
            );
        }
    }

    public function render()
    {
        $approvalTypes = $this->secretariatService->approvalTypesForPhase($this->phase);
        $nextType = $this->secretariatService->nextPendingApprovalType($this->phase);
        $progress = $this->secretariatService->approvalProgress($this->phase);
        $nextPendingPhase = $this->secretariatService->nextPendingPhase(
            $progress['is_complete'] ? null : $this->phase->id
        );

        return view('livewire.secretariat.phase-review', [
            'approvals' => $this->phase->secretariatApprovals->keyBy('approval_type'),
            'approvalTypes' => $approvalTypes,
            'nextType' => $nextType,
            'progress' => $progress,
            'requiresFundTransfer' => $this->secretariatService->requiresFundTransferAuthorization($this->phase),
            'completionSummary' => $this->secretariatService->completionSummary($this->phase),
            'nextPendingPhase' => $nextPendingPhase,
        ]);
    }
}
