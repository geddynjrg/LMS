<div>
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" wire:click.self="dismiss">
        <div class="modal-dialog modal-lg modal-dialog-scrollable shadow-lg">
            <div class="modal-content border-0">
                <div class="modal-header text-white" style="background-color: #003366;">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Constituency Officer Guide
                    </h5>
                    <button type="button" class="close text-white" wire:click="dismiss" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body bg-light">
                    <p class="text-muted mb-4">
                        Welcome back. Here is a quick reference for managing phases and groups in your constituency.
                    </p>

                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="font-weight-bold text-uwezo mb-3">
                                <i class="fas fa-layer-group mr-2"></i>
                                Edit or delete a phase
                            </h6>
                            <p class="small text-muted mb-2">You can manage phases from either location:</p>
                            <ul class="mb-3 pl-3">
                                <li class="mb-2">
                                    <strong>Applications &rarr; Tasks</strong> — open the <em>Pending</em>, <em>Processing</em>, or <em>Completed</em> tab for the phase list.
                                </li>
                                <li class="mb-2">
                                    <strong>Loans &rarr; Loan Phase</strong> — view all group loan phases for your constituency.
                                </li>
                            </ul>
                            <ol class="mb-0 pl-3">
                                <li class="mb-2">Find the phase in the table.</li>
                                <li class="mb-2">Open the <strong>Actions</strong> dropdown (three dots) on that row.</li>
                                <li class="mb-2">Choose <strong>Edit Details</strong> to update the phase name or settings.</li>
                                <li class="mb-0">
                                    Choose <strong>Remove Phase</strong> to delete it. Deletion is only allowed when the phase has <strong>no applications or group loans</strong> assigned — remove or reassign them first if needed.
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-0">
                        <div class="card-body">
                            <h6 class="font-weight-bold text-uwezo mb-3">
                                <i class="fas fa-users mr-2"></i>
                                Edit or delete a group
                            </h6>
                            <p class="small text-muted mb-2">Groups can be managed from:</p>
                            <ul class="mb-3 pl-3">
                                <li class="mb-2">
                                    <strong>Loans &rarr; Group Loans</strong> — full list of groups in your constituency.
                                </li>
                                <li class="mb-0">
                                    Or open a phase and use <strong>View</strong> to see groups within that phase.
                                </li>
                            </ul>
                            <ol class="mb-0 pl-3">
                                <li class="mb-2">Locate the group in the table.</li>
                                <li class="mb-2">In the <strong>Action</strong> column, click the <strong>Edit</strong> button (pencil icon) to update group details.</li>
                                <li class="mb-0">
                                    Click the <strong>Delete</strong> button (trash icon) to remove the group from the phase. Confirm when prompted.
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white d-flex justify-content-between align-items-center">
                    <div class="custom-control custom-checkbox">
                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="dont-show-officer-guide"
                            wire:model="dontShowAgain"
                        >
                        <label class="custom-control-label text-muted" for="dont-show-officer-guide">
                            Do not show this again
                        </label>
                    </div>
                    <button type="button" class="btn btn-uwezo px-4" wire:click="dismiss">
                        Got it
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
