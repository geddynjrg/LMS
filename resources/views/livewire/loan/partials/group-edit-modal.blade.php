<x-action-modal
    id="edit-group-details-modal"
    title="Edit Group Details"
    type="info"
    icon="fa-users"
    size="modal-lg"
    x-on:open-modal.window="if($event.detail.name === 'edit-group-details-modal') $($el).modal('show')"
    x-on:close-modal.window="if($event.detail.name === 'edit-group-details-modal') $($el).modal('hide')"
>
    <form wire:submit.prevent="saveGroupDetails">
        @if($editingGroupLoanId)
            <div class="alert alert-light border mb-3 py-2 px-3" style="border-radius: 12px;">
                <span class="text-xs text-gray-600">
                    <i class="fas fa-shield-alt mr-1 text-info"></i>
                    Changes are recorded in the group audit trail with your user account and IP address.
                </span>
            </div>

            @if($this->editingGroupAllows('group_name'))
                <div class="form-group mb-3">
                    <label class="text-uppercase text-gray-600 font-weight-bold small">Group Name <span class="text-danger">*</span></label>
                    <input type="text" wire:model="editGroupName" class="form-control" required>
                    @error('groupName') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="row">
                @if($this->editingGroupAllows('ward_id'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Ward <span class="text-danger">*</span></label>
                            <select wire:model="editGroupWardId" class="form-control" required>
                                <option value="">Select ward</option>
                                @foreach($this->wardsOptions as $ward)
                                    <option value="{{ $ward['id'] }}">{{ $ward['name'] }}</option>
                                @endforeach
                            </select>
                            @error('wardId') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingGroupAllows('gfs_code'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">GFS Code</label>
                            <input type="text" wire:model="editGfsCode" class="form-control" placeholder="e.g. 4-01-001-4110201-10-12345">
                            @error('gfsCode') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif


                @if($this->editingGroupAllows('amount_approved'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Approved (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountApproved" class="form-control">
                            @error('amountApproved') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingGroupAllows('amount_issued'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Issued (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountIssued" class="form-control">
                            @error('amountIssued') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingGroupAllows('amount_repaid'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Repaid (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountRepaid" class="form-control">
                            @error('amountRepaid') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingGroupAllows('disbursement_date'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Date issued / disbursement date</label>
                            <input type="date" wire:model="editDisbursementDate" class="form-control">
                            @error('disbursementDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingGroupAllows('cheque_no'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Cheque number</label>
                            <input type="text" wire:model="editChequeNo" class="form-control">
                            @error('chequeNo') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot name="footer">
            <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
            <x-pill-button type="submit" label="Save Changes" color="primary" target="saveGroupDetails" icon="fas fa-save" class="mx-1 shadow-sm btn-refresh-premium" />
        </x-slot>
    </form>
</x-action-modal>

<x-action-modal
    id="confirm-delete-group-modal"
    title="Delete Group from Phase"
    type="danger"
    icon="fa-trash"
    size="modal-md"
    x-on:open-modal.window="if($event.detail.name === 'confirm-delete-group-modal') $($el).modal('show')"
    x-on:close-modal.window="if($event.detail.name === 'confirm-delete-group-modal') $($el).modal('hide')"
>
    <div class="alert alert-warning text-left mb-0" role="alert">
        <p class="mb-2 small">
            Deleting this group will remove the group loan and all members linked to it from this phase.
        </p>
        <p class="mb-2 small">
            A backup file is saved automatically before deletion. The action is recorded in the audit trail.
        </p>
        <p class="mb-0 small font-weight-bold">
            This cannot be undone.
        </p>
    </div>

    <x-slot name="footer">
        <x-pill-button label="Cancel" variant="outline" color="secondary" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
        <x-pill-button label="Delete Group" color="danger" target="confirmDeleteGroup" icon="fas fa-trash" class="mx-1 shadow-sm btn-refresh-premium" />
    </x-slot>
</x-action-modal>
