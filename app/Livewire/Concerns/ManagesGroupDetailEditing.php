<?php

namespace App\Livewire\Concerns;

use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Services\Group\GroupDetailEditService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

trait ManagesGroupDetailEditing
{
    public ?int $editingGroupLoanId = null;
    public ?int $deletingGroupLoanId = null;

    public string $editGroupName = '';
    public ?int $editGroupWardId = null;
    public ?string $editGfsCode = null;
    public ?string $editAmountApplied = null;
    public ?string $editAmountApproved = null;
    public ?string $editAmountIssued = null;
    public ?string $editAmountRepaid = null;
    public ?string $editDisbursementDate = null;

    #[Computed]
    public function editablePhaseIds(): array
    {
        return LoanPhase::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->get()
            ->filter(fn (LoanPhase $phase) => $phase->canEditDetailsBy(auth()->user()))
            ->pluck('id')
            ->all();
    }

    public function canEditGroupInPhase(?int $phaseId): bool
    {
        return $phaseId !== null
            && in_array($phaseId, $this->editablePhaseIds, true);
    }

    public function openEditGroupModal(int $groupLoanId): void
    {
        $groupLoan = GroupLoan::query()
            ->with(['group.ward', 'loanPhase'])
            ->whereHas('loanPhase', fn ($q) => $q->where('constituency_code', $this->constituency->constituency_code))
            ->findOrFail($groupLoanId);

        if (!app(GroupDetailEditService::class)->canEditGroupLoan($groupLoan, auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot edit this group.',
                type: 'error'
            );
            return;
        }

        $this->editingGroupLoanId = $groupLoan->id;
        $this->editGroupName = $groupLoan->group?->name ?? '';
        $this->editGroupWardId = $groupLoan->group?->ward_id;
        $this->editGfsCode = $groupLoan->gfs_code ?? $groupLoan->group?->gfs_code;
        $this->editAmountApplied = $groupLoan->amount_applied !== null ? (string) $groupLoan->amount_applied : null;
        $this->editAmountApproved = $groupLoan->amount_approved !== null ? (string) $groupLoan->amount_approved : null;
        $this->editAmountIssued = $groupLoan->amount_issued !== null ? (string) $groupLoan->amount_issued : null;
        $this->editAmountRepaid = $groupLoan->amount_repaid !== null ? (string) $groupLoan->amount_repaid : null;
        $this->editDisbursementDate = $groupLoan->disbursement_date?->format('Y-m-d');

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'edit-group-details-modal');
    }

    public function saveGroupDetails(): void
    {
        if (!$this->editingGroupLoanId) {
            return;
        }

        $groupLoan = GroupLoan::query()
            ->with(['group', 'loanPhase'])
            ->whereHas('loanPhase', fn ($q) => $q->where('constituency_code', $this->constituency->constituency_code))
            ->findOrFail($this->editingGroupLoanId);

        try {
            app(GroupDetailEditService::class)->updateDetails($groupLoan, auth()->user(), [
                'groupName' => $this->editGroupName,
                'wardId' => $this->editGroupWardId,
                'gfsCode' => $this->editGfsCode,
                'amountApplied' => $this->editAmountApplied,
                'amountApproved' => $this->editAmountApproved,
                'amountIssued' => $this->editAmountIssued,
                'amountRepaid' => $this->editAmountRepaid,
                'disbursementDate' => $this->editDisbursementDate,
            ]);

            $this->resetGroupEditForm();
            $this->dispatch('close-modal', name: 'edit-group-details-modal');
            $this->dispatch('swal:toast',
                title: 'Group Updated',
                message: 'Group details saved successfully.',
                type: 'success'
            );

            if (method_exists($this, 'refreshData')) {
                $this->refreshData();
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
                message: $e->getMessage() ?: 'Could not save group details.',
                type: 'error'
            );
        }
    }

    public function deleteGroup(int $groupLoanId): void
    {
        $groupLoan = GroupLoan::query()
            ->with('loanPhase')
            ->whereHas('loanPhase', fn ($q) => $q->where('constituency_code', $this->constituency->constituency_code))
            ->find($groupLoanId);

        if (!$groupLoan || !app(GroupDetailEditService::class)->canEditGroupLoan($groupLoan, auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot delete this group.',
                type: 'error'
            );
            return;
        }

        $this->deletingGroupLoanId = $groupLoanId;
        $this->dispatch('open-modal', name: 'confirm-delete-group-modal');
    }

    public function confirmDeleteGroup(): void
    {
        if (!$this->deletingGroupLoanId) {
            return;
        }

        $groupLoan = GroupLoan::query()
            ->with(['group', 'loanPhase'])
            ->whereHas('loanPhase', fn ($q) => $q->where('constituency_code', $this->constituency->constituency_code))
            ->find($this->deletingGroupLoanId);

        if (!$groupLoan) {
            $this->dispatch('swal:toast',
                title: 'Delete Failed',
                message: 'Group record could not be found.',
                type: 'error'
            );
            $this->reset('deletingGroupLoanId');
            $this->dispatch('close-modal', name: 'confirm-delete-group-modal');
            return;
        }

        try {
            app(GroupDetailEditService::class)->deleteWithAudit($groupLoan, auth()->user());

            $this->reset('deletingGroupLoanId');
            $this->dispatch('close-modal', name: 'confirm-delete-group-modal');
            $this->dispatch('swal:toast',
                title: 'Group Deleted',
                message: 'Group removed from phase successfully.',
                type: 'success'
            );

            if (method_exists($this, 'refreshData')) {
                $this->refreshData();
            }
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Delete Blocked',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
            $this->reset('deletingGroupLoanId');
            $this->dispatch('close-modal', name: 'confirm-delete-group-modal');
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast',
                title: 'Delete Failed',
                message: $e->getMessage() ?: 'Could not delete this group.',
                type: 'error'
            );
            $this->reset('deletingGroupLoanId');
            $this->dispatch('close-modal', name: 'confirm-delete-group-modal');
        }
    }

    public function editingGroupLoan(): ?GroupLoan
    {
        if (!$this->editingGroupLoanId) {
            return null;
        }

        return GroupLoan::with('loanPhase')->find($this->editingGroupLoanId);
    }

    public function editingGroupAllows(string $field): bool
    {
        $groupLoan = $this->editingGroupLoan();

        return $groupLoan
            ? in_array($field, app(GroupDetailEditService::class)->editableFieldsFor($groupLoan), true)
            : false;
    }

    protected function resetGroupEditForm(): void
    {
        $this->reset([
            'editingGroupLoanId',
            'editGroupName',
            'editGroupWardId',
            'editGfsCode',
            'editAmountApplied',
            'editAmountApproved',
            'editAmountIssued',
            'editAmountRepaid',
            'editDisbursementDate',
        ]);
    }
}
