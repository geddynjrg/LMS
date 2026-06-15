<div>
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}" class="btn btn-link px-0">&larr; Back to pending tasks</a>
            <h2 class="h4 mb-0">{{ $phase->phase }}</h2>
            <p class="text-muted mb-0">
                {{ $phase->phase_ref }}
                · {{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}
                · HQ officer: {{ $phase->hqOfficer?->name ?? '—' }}
            </p>
        </div>
        <a href="{{ route('secretariat.phases.code-list', $phase) }}" class="btn btn-outline-primary">
            <i class="fas fa-file-excel mr-1"></i> Download code list
        </a>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Authorization checklist</strong>
                    @if(!$nextType)
                        <span class="badge badge-success">Complete</span>
                    @else
                        <span class="badge badge-warning">{{ $progress['done'] }} / {{ $progress['total'] }}</span>
                    @endif
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($approvalTypes as $type => $label)
                        @php
                            $record = $approvals->get($type);
                            $isCurrent = $type === $nextType;
                            $isDone = (bool) $record?->approved_at;
                        @endphp
                        <li class="list-group-item d-flex justify-content-between align-items-start {{ $isCurrent ? 'border-left border-primary border-3 bg-light' : '' }}">
                            <div>
                                <div class="font-weight-bold">
                                    @if($isDone)
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                    @elseif($isCurrent)
                                        <i class="fas fa-arrow-right text-primary mr-1"></i>
                                    @endif
                                    {{ $label }}
                                </div>
                                @if($isDone)
                                    <small class="text-success">Signed {{ $record->approved_at->format('d M Y H:i') }} by {{ $record->approver?->name }}</small>
                                @elseif($isCurrent)
                                    <small class="text-primary font-weight-bold">Current step</small>
                                @else
                                    <small class="text-muted">Pending</small>
                                @endif
                            </div>
                            @if($isDone)
                                <span class="badge badge-success">Done</span>
                            @elseif($isCurrent)
                                <span class="badge badge-primary">Now</span>
                            @else
                                <span class="badge badge-secondary">Waiting</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-5">
            @if($nextType)
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">
                        <strong>Step {{ $progress['done'] + 1 }} of {{ $progress['total'] }}</strong>
                        <div class="small mt-1 opacity-90">{{ $approvalTypes[$nextType] ?? $nextType }}</div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Upload your digital signature for this step. The next step unlocks automatically after you save.
                        </p>
                        @unless($requiresFundTransfer)
                            <p class="text-muted small mb-3">
                                Letter of authority to disburse funds is not required because the approved amount does not exceed the constituency bank balance.
                            </p>
                        @endunless
                        <form wire:submit.prevent="submitApproval">
                            <div class="mb-3">
                                <label class="form-label">Authorization</label>
                                <input type="text" class="form-control bg-light" value="{{ $approvalTypes[$nextType] ?? $nextType }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Digital signature (PNG/JPG/PDF)</label>
                                <input type="file" wire:model="signatureFile" class="form-control" accept=".png,.jpg,.jpeg,.pdf">
                                @error('signatureFile') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes (optional)</label>
                                <textarea wire:model="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitApproval">
                                    Save &amp; continue
                                </span>
                                <span wire:loading wire:target="submitApproval,signatureFile">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Saving…
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white">
                        <strong><i class="fas fa-check-circle mr-1"></i> All authorisations complete</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>{{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}</strong>
                            · {{ $phase->phase }} ({{ $phase->phase_ref }})
                        </p>
                        <p class="text-muted small mb-4">{{ $completionSummary }}</p>

                        <div class="d-flex flex-column">
                            <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}" class="btn btn-primary mb-2">
                                <i class="fas fa-inbox mr-1"></i> Back to pending tasks
                            </a>
                            @if($nextPendingPhase)
                                <a href="{{ route('secretariat.phases.review', $nextPendingPhase) }}" class="btn btn-outline-primary mb-2">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Review next pending phase
                                    <small class="d-block text-muted">
                                        {{ $nextPendingPhase->constituency?->constituency_name ?? $nextPendingPhase->constituency_code }}
                                        · {{ $nextPendingPhase->phase }}
                                    </small>
                                </a>
                            @endif
                            <a href="{{ route('secretariat.phases.index', ['filter' => 'completed']) }}" class="btn btn-outline-secondary btn-sm">
                                View fully authorized phases
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
