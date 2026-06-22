<div>
    <div class="mb-3">
        <a href="{{ route('accountant.phases.index') }}" class="btn btn-link px-0">&larr; Back to accounts dashboard</a>
        <h2 class="h4 mb-0">{{ $phase->phase }}</h2>
        <p class="text-muted mb-0">
            {{ $phase->phase_ref }}
            · {{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}
        </p>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header font-weight-bold">Documents from loans / HQ</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold">Approved code list (HQ)</div>
                            <small class="text-muted">{{ $codeListDoc?->original_filename ?? 'Not uploaded' }}</small>
                        </div>
                        @if($codeListDoc)
                            <a href="{{ route('phase.doc.serve', ['id' => $codeListDoc->id, 'action' => 'download']) }}" class="btn btn-sm btn-outline-primary">Download</a>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold">Bank reconciliation</div>
                            <small class="text-muted">{{ $bankReconDoc?->original_filename ?? 'Not uploaded' }}</small>
                        </div>
                        @if($bankReconDoc)
                            <a href="{{ route('phase.doc.serve', ['id' => $bankReconDoc->id, 'action' => 'download']) }}" class="btn btn-sm btn-outline-primary">Download</a>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold">Management committee minutes (constituency)</div>
                            <small class="text-muted">{{ $minutesDoc?->original_filename ?? 'Not uploaded' }}</small>
                        </div>
                        @if($minutesDoc)
                            <a href="{{ route('phase.doc.serve', ['id' => $minutesDoc->id, 'action' => 'download']) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-success mb-3">
                <div class="card-header bg-success text-white font-weight-bold">Forward to HQ management</div>
                <div class="card-body">
                    <p class="text-muted small">If the code list and bank reconciliation are correct, add notes and send to HQ for management committee approval.</p>
                    <div class="form-group">
                        <label>Notes for HQ</label>
                        <textarea wire:model="notes" class="form-control" rows="4" placeholder="Accounts review notes…"></textarea>
                    </div>
                    <button type="button" wire:click="approveForHq" class="btn btn-success btn-block" wire:loading.attr="disabled">
                        Approve &amp; send to HQ management
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning font-weight-bold">Return with comments</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Return to</label>
                        <select wire:model="returnTarget" class="form-control">
                            <option value="hq">HQ officer (corrections on code list / documents)</option>
                            <option value="constituency">Constituency officer (clarification required)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Comments</label>
                        <textarea wire:model="returnComments" class="form-control" rows="4" placeholder="What must be corrected…"></textarea>
                    </div>
                    <button type="button" wire:click="returnWithComments" class="btn btn-warning btn-block" wire:loading.attr="disabled">
                        Return with comments
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
