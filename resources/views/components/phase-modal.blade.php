<div class="modal-container">
    <div
        wire:ignore.self
        class="modal fade"
        id="create-phase-modal"
        data-backdrop="false"
        tabindex="-1"
        role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">

                <div class="modal-header bg-primary text-white pt-4 px-4 border-bottom-0"
                     style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-layer-group mr-2"></i> Create New Phase
                    </h5>
                    <button type="button" class="close text-white opacity-10" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <form wire:submit.prevent="createPhase">
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-4 py-2 px-3" style="border-radius: 12px; background-color: #f8f9fa;">
                            <small class="text-muted"><i class="fas fa-info-circle mr-1 text-primary"></i>
                                Phases help organize applications into specific review cycles.
                            </small>
                        </div>

                        <div class="form-group mb-4">
                            <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Phase Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0" style="border-radius: 10px 0 0 10px;"><i class="fas fa-tag text-primary"></i></span>
                                </div>
                                <input type="text" wire:model="phaseName"
                                       class="form-control form-control-lg border-left-0 font-weight-bold"
                                       style="border-radius: 0 10px 10px 0; font-size: 1.1rem;"
                                       placeholder="{{ $suggestedPhaseName ?? 'Enter phase name...' }}" required>
                            </div>
                            @error('phaseName') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-4">
                                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Scheduled Review Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0" style="border-radius: 10px 0 0 10px;"><i class="fas fa-calendar-alt text-primary"></i></span>
                                        </div>
                                        <input type="date" wire:model="reviewDate"
                                               class="form-control form-control-lg border-left-0"
                                               style="border-radius: 0 10px 10px 0; font-size: 1rem;" required>
                                    </div>
                                    @error('reviewDate') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Review Venue (Optional)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0" style="border-radius: 10px 0 0 10px;"><i class="fas fa-map-marker-alt text-primary"></i></span>
                                        </div>
                                        <input type="text" wire:model="reviewVenue"
                                               class="form-control form-control-lg border-left-0"
                                               style="border-radius: 0 10px 10px 0; font-size: 1rem;"
                                               placeholder="e.g., Constituency Office Hall">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light border-0 px-4 py-3">
                        <button type="button" class="btn btn-outline-secondary px-4 font-weight-bold rounded-pill mr-2" data-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn btn-primary px-4 shadow-sm font-weight-bold rounded-pill {{ $errors->any() ? 'animate__animated animate__headShake' : '' }}">
                            <span wire:loading.remove wire:target="createPhase"><i class="fas fa-plus-circle mr-1"></i> Create Phase</span>
                            <span wire:loading wire:target="createPhase"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .font-weight-black {
            font-weight: 900;
        }
        .tracking-wider {
            letter-spacing: 0.08em;
        }
        /* Ensure the date icon looks good */
        .input-group-text {
            border-color: #ced4da;
            color: #495057;
        }

        /* Target only this specific modal or all modals with .animate__animated */
        .modal.fade .modal-dialog {
            transition: none; /* Disable standard Bootstrap sliding */
        }

        /* When the modal is opening */
        .modal.show .modal-dialog {
            animation: zoomIn 0.3s forwards;
        }

        /* When the modal is closing */
        /* Bootstrap keeps the 'modal' class but removes 'show' */
        .modal:not(.show) .modal-dialog {
            animation: zoomOut 0.3s forwards;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes zoomOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.7); }
        }
    </style>
@endpush
