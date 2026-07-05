<x-action-modal
    id="edit-phase-details-modal"
    title="Edit Phase Details"
    type="info"
    icon="fa-edit"
    size="modal-lg"
    x-on:open-modal.window="if($event.detail.name === 'edit-phase-details-modal') $($el).modal('show')"
    x-on:close-modal.window="if($event.detail.name === 'edit-phase-details-modal') $($el).modal('hide')"
>
    <form wire:submit.prevent="savePhaseDetails">
        @if($editingPhaseId)
            <div class="alert alert-light border mb-3 py-2 px-3" style="border-radius: 12px;">
                <span class="text-xs text-gray-600">
                    <i class="fas fa-shield-alt mr-1 text-info"></i>
                    Changes are recorded in the audit trail with your user account and IP address.
                </span>
            </div>

            @if($this->editingPhaseAllows('phase'))
                <div class="form-group mb-3">
                    <label class="text-uppercase text-gray-600 font-weight-bold small">Phase Name <span class="text-danger">*</span></label>
                    <input type="text" wire:model="editPhaseName" class="form-control" required>
                    @error('phaseName') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="row">
                @if($this->editingPhaseAllows('review_date'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Review Date</label>
                            <input type="date" wire:model="editReviewDate" class="form-control">
                            @error('reviewDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('review_venue'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Review Venue</label>
                            <input type="text" wire:model="editReviewVenue" class="form-control" placeholder="e.g. Constituency Office">
                            @error('reviewVenue') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('amount_disbursed'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Disbursed (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountDisbursed" class="form-control">
                            @error('amountDisbursed') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('amount_repaid'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Amount Repaid (KES)</label>
                            <input type="number" step="0.01" min="0" wire:model="editAmountRepaid" class="form-control">
                            @error('amountRepaid') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('disbursed_at'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Disbursement Date</label>
                            <input type="date" wire:model="editDisbursedAt" class="form-control">
                            @error('disbursedAt') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('training_start_date'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Training Start</label>
                            <input type="date" wire:model="editTrainingStartDate" class="form-control">
                            @error('trainingStartDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('training_end_date'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Training End</label>
                            <input type="date" wire:model="editTrainingEndDate" class="form-control">
                            @error('trainingEndDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                @if($this->editingPhaseAllows('training_venue'))
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="text-uppercase text-gray-600 font-weight-bold small">Training Venue</label>
                            <input type="text" wire:model="editTrainingVenue" class="form-control">
                            @error('trainingVenue') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>

            @if($this->editingPhaseAllows('training_notes'))
                <div class="form-group mb-0">
                    <label class="text-uppercase text-gray-600 font-weight-bold small">Training Notes</label>
                    <textarea wire:model="editTrainingNotes" class="form-control" rows="3"></textarea>
                    @error('trainingNotes') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            @endif
        @endif

        <x-slot name="footer">
            <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
            <x-pill-button type="submit" label="Save Changes" color="primary" target="savePhaseDetails" icon="fas fa-save" class="mx-1 shadow-sm btn-refresh-premium" />
        </x-slot>
    </form>
</x-action-modal>
