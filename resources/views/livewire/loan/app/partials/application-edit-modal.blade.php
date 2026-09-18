<x-action-modal
    id="edit-application-details-modal"
    title="Edit Application Details"
    type="info"
    icon="fa-edit"
    size="modal-lg"
    x-on:open-modal.window="if($event.detail.name === 'edit-application-details-modal') $($el).modal('show')"
    x-on:close-modal.window="if($event.detail.name === 'edit-application-details-modal') $($el).modal('hide')"
>
    <form wire:submit.prevent="saveApplicationDetails">
        @if($editingApplicationId)
            <div class="alert alert-light border mb-3 py-2 px-3" style="border-radius: 12px;">
                <span class="text-xs text-gray-600">
                    <i class="fas fa-shield-alt mr-1 text-info"></i>
                    Changes are recorded in the application audit trail with your user account and IP address.
                </span>
            </div>

            @if($this->editingApplicationAllows('group_name'))
                <div class="form-group mb-3">
                    <label class="text-uppercase text-gray-600 font-weight-bold small">Group Name <span class="text-danger">*</span></label>
                    <input type="text" wire:model="editGroupName" class="form-control" required>
                    @error('groupName') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="row">
                @if($this->editingApplicationAllows('ward_id'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Ward</label>
                            <select wire:model="editGroupWardId" class="form-control">
                                <option value="">Select ward</option>
                                @foreach($this->applicationEditWardsOptions as $ward)
                                    <option value="{{ $ward['id'] }}">{{ $ward['name'] }}</option>
                                @endforeach
                            </select>
                            @error('wardId') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('gfs_code'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">GFS Code</label>
                            <input type="text" wire:model="editGfsCode" class="form-control" placeholder="e.g. 4-01-001-4110201-10-12345">
                            @error('gfsCode') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('amount_applied'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Applied (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountApplied" class="form-control">
                            @error('amountApplied') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('amount_approved'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Approved (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountApproved" class="form-control">
                            @error('amountApproved') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('amount_issued'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Issued (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountIssued" class="form-control">
                            @error('amountIssued') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('amount_repaid'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Repaid (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountRepaid" class="form-control">
                            @error('amountRepaid') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('date_registered'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Date Registered</label>
                            <input type="date" wire:model="editDateRegistered" class="form-control">
                            @error('dateRegistered') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingApplicationAllows('disbursement_date'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Disbursement Date</label>
                            <input type="date" wire:model="editDisbursementDate" class="form-control">
                            @error('disbursementDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <x-slot name="footer">
            <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
            <x-pill-button type="submit" label="Save Changes" color="primary" target="saveApplicationDetails" icon="fas fa-save" class="mx-1 shadow-sm btn-refresh-premium" />
        </x-slot>
    </form>
</x-action-modal>
