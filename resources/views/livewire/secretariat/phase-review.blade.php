@php
    use App\Support\PhaseDocumentType;
    use App\Models\SecretariatApproval;

    $docs = $phase->phaseApplicationDocuments->keyBy('document_type');
    $processedDoc = $docs->get(PhaseDocumentType::PROCESSED_CODE_LIST)
        ?? $docs->get(PhaseDocumentType::LEGACY_APPROVED_CODE_LIST);
    $signedCodeListDoc = $docs->get(PhaseDocumentType::SIGNED_APPROVED_CODE_LIST);
    $canPreviewCodeList = (bool) $processedDoc;
    $progressPct = $progress['total'] > 0 ? round(($progress['done'] / $progress['total']) * 100) : 0;

    $documentCards = [
        [
            'doc' => $processedDoc,
            'label' => 'Processed code list',
            'hint' => 'HQ Excel workbook for this batch.',
            'icon' => 'fa-file-excel',
            'color' => 'success',
            'badge' => 'HQ',
            'preview_route' => $canPreviewCodeList ? route('secretariat.phases.processed_code_list.preview', $phase) : null,
            'download_route' => route('secretariat.phases.code-list', $phase),
        ],
        [
            'doc' => $docs->get(PhaseDocumentType::MANAGEMENT_PROPOSAL_REVIEW_MINUTES),
            'label' => 'Management proposal minutes',
            'hint' => 'HQ management committee minutes.',
            'icon' => 'fa-file-signature',
            'color' => 'primary',
            'badge' => 'HQ',
        ],
        [
            'doc' => $docs->get(PhaseDocumentType::MINUTES),
            'label' => 'Constituency committee minutes',
            'hint' => 'Signed minutes from the constituency.',
            'icon' => 'fa-users',
            'color' => 'info',
            'badge' => 'Constituency',
        ],
        [
            'doc' => $signedCodeListDoc,
            'label' => 'Signed approved codelist',
            'hint' => 'PDF generated after your code list signature.',
            'icon' => 'fa-file-pdf',
            'color' => 'danger',
            'badge' => 'Secretariat',
        ],
        [
            'doc' => $docs->get(PhaseDocumentType::REQUEST_FOR_TRANSFER_LETTER),
            'label' => 'Request for transfer letter',
            'hint' => 'Constituency transfer letter for disbursement.',
            'icon' => 'fa-file-contract',
            'color' => 'warning',
            'badge' => 'Constituency',
        ],
    ];
@endphp

<div class="secretariat-review-page">
    <div class="mb-4">
        <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}" class="btn btn-link px-0 font-weight-bold text-indigo" style="color:#4f46e5 !important;">
            <i class="fas fa-arrow-left mr-1"></i> Back to secretariat dashboard
        </a>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-2">
            <div>
                <h2 class="h4 font-weight-bold text-navy mb-1">{{ $phase->phase }}</h2>
                <p class="text-muted mb-0">
                    <span class="mr-3"><i class="fas fa-hashtag mr-1"></i>{{ $phase->phase_ref }}</span>
                    <span class="mr-3"><i class="fas fa-map-marker-alt mr-1"></i>{{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}</span>
                    @if($phase->hqOfficer)
                        <span><i class="fas fa-user-tie mr-1"></i>HQ: {{ $phase->hqOfficer->name }}</span>
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center" style="gap:0.5rem;">
                @if($nextType)
                    <span class="badge badge-pill badge-warning px-3 py-2">
                        Step {{ $progress['done'] + 1 }} of {{ $progress['total'] }}
                    </span>
                @else
                    <span class="badge badge-pill badge-success px-3 py-2">
                        <i class="fas fa-check mr-1"></i> Fully authorized
                    </span>
                @endif
                @if($this->canReturnToHq())
                    <button type="button" wire:click="returnToHq" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-undo mr-1"></i> Return to HQ
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card hq-kpi-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="hq-sidebar-label d-block">Total approved</span>
                    <h4 class="font-weight-bold text-navy mb-0">KES {{ number_format($totalApproved) }}</h4>
                    <small class="text-muted">{{ $applicationCount }} group(s) in batch</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card hq-kpi-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="hq-sidebar-label d-block">Authorization progress</span>
                    <h4 class="font-weight-bold text-navy mb-0">{{ $progress['done'] }} / {{ $progress['total'] }}</h4>
                    <div class="progress mt-2" style="height:6px;border-radius:999px;">
                        <div class="progress-bar bg-indigo" style="width:{{ $progressPct }}%;background:#4f46e5 !important;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card hq-kpi-card h-100 shadow-sm border-0">
                <div class="card-body">
                    <span class="hq-sidebar-label d-block">Fund transfer letter</span>
                    <h4 class="font-weight-bold text-navy mb-0">{{ $requiresFundTransfer ? 'Required' : 'Not required' }}</h4>
                    <small class="text-muted">
                        @if($requiresFundTransfer && $bankBalance !== null)
                            Balance KES {{ number_format((float) $bankBalance) }} vs batch
                        @else
                            Batch within constituency bank balance
                        @endif
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card hq-kpi-card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column justify-content-center">
                    <span class="hq-sidebar-label d-block mb-2">Quick actions</span>
                    <div class="d-flex flex-wrap" style="gap:0.35rem;">
                        @if($canPreviewCodeList)
                            <a href="{{ route('secretariat.phases.processed_code_list.preview', $phase) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye mr-1"></i> Preview list
                            </a>
                        @endif
                        <a href="{{ route('secretariat.phases.code-list', $phase) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download mr-1"></i> Excel
                        </a>
                        @if(app(\App\Services\Exports\SignedCodeListPdfService::class)->canDownload($phase))
                            <a href="{{ route('secretariat.phases.approved_code_list', $phase) }}" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf mr-1"></i> Signed PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="secretariat-stepper">
        @foreach($approvalTypes as $type => $label)
            @php
                $record = $approvals->get($type);
                $isCurrent = $type === $nextType;
                $isDone = (bool) $record?->approved_at;
                $stepClass = $isDone ? 'is-done' : ($isCurrent ? 'is-current' : 'is-waiting');
            @endphp
            <div class="secretariat-step {{ $stepClass }}">
                <div class="secretariat-step-index">
                    @if($isDone)
                        <i class="fas fa-check"></i>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </div>
                <div class="small font-weight-bold text-navy">{{ $label }}</div>
                @if($isDone)
                    <small class="text-success d-block mt-1">{{ $record->approved_at->format('d M Y') }}</small>
                @elseif($isCurrent)
                    <small class="text-primary font-weight-bold d-block mt-1">Your current step</small>
                @else
                    <small class="text-muted d-block mt-1">Waiting</small>
                @endif
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card hq-panel-card mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="font-weight-bold text-navy text-uppercase small mb-0">
                        <i class="fas fa-folder-open mr-2" style="color:#4f46e5;"></i>Documents for review
                    </h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        @foreach($documentCards as $card)
                            @php
                                $doc = $card['doc'] ?? null;
                                $hasFile = (bool) $doc;
                                $borderClass = $hasFile ? 'border-' . $card['color'] . ' bg-light' : 'border-secondary';
                            @endphp
                            <div class="col-md-6 mb-3">
                                <div class="border rounded-lg p-3 h-100 {{ $borderClass }}" style="border-radius:12px;">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div class="rounded bg-{{ $card['color'] }} text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="fas {{ $card['icon'] }}"></i>
                                        </div>
                                        <span class="badge badge-{{ $card['color'] }}">{{ $card['badge'] }}</span>
                                    </div>
                                    <div class="font-weight-bold text-navy">{{ $card['label'] }}</div>
                                    <small class="text-muted d-block mb-2">{{ $card['hint'] }}</small>
                                    <div class="small text-truncate text-muted mb-2" title="{{ $doc?->original_filename }}">
                                        {{ $doc?->original_filename ?? 'Not yet available' }}
                                    </div>
                                    <div class="d-flex flex-wrap" style="gap:0.35rem;">
                                        @if(!empty($card['preview_route']))
                                            <a href="{{ $card['preview_route'] }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye mr-1"></i> Preview
                                            </a>
                                        @endif
                                        @if(!empty($card['download_route']))
                                            <a href="{{ $card['download_route'] }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download mr-1"></i> Download
                                            </a>
                                        @elseif($doc)
                                            @include('partials.phase-document-actions', ['doc' => $doc, 'labeled' => true])
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if($nextType)
                <div class="card hq-panel-card border-0 mb-4" style="border-left:4px solid #4f46e5 !important;">
                    <div class="card-header bg-white border-0 pb-0">
                        <span class="hq-sidebar-label d-block">Current authorization</span>
                        <h5 class="font-weight-bold text-navy mb-0">{{ $approvalTypes[$nextType] ?? $nextType }}</h5>
                    </div>
                    <div class="card-body pt-3">
                        <p class="text-muted small mb-3">
                            @if($nextType === SecretariatApproval::TYPE_POST_HQ_REVIEW)
                                Upload your digital signature to sign the approved code list. A signed PDF will be generated automatically.
                            @elseif($nextType === SecretariatApproval::TYPE_FUND_TRANSFER)
                                Confirm authorization for the letter of authority to disburse funds because the approved amount exceeds the constituency bank balance.
                            @elseif($nextType === SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)
                                Authorize the constituency to schedule mandatory training for approved groups.
                            @else
                                Authorize final disbursement after training requirements are met.
                            @endif
                        </p>

                        @unless($requiresFundTransfer)
                            <div class="alert alert-light border small py-2 mb-3">
                                <i class="fas fa-info-circle text-muted mr-1"></i>
                                Letter of authority is not required — the approved amount is within the constituency bank balance.
                            </div>
                        @endunless

                        <form wire:submit.prevent="submitApproval">
                            @if($nextType === SecretariatApproval::TYPE_POST_HQ_REVIEW)
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-uppercase text-muted">Digital signature (PNG, JPG, or PDF)</label>
                                    <x-file-upload model="signatureFile" height="48px" accept=".png,.jpg,.jpeg,.pdf" />
                                    @error('signatureFile') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-uppercase text-muted">Notes (optional)</label>
                                <textarea wire:model.blur="notes" class="form-control" rows="3" placeholder="Add any notes for this authorization step…" style="border-radius:10px;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-indigo btn-block shadow-sm font-weight-bold" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitApproval,signatureFile">
                                    <i class="fas fa-check mr-1"></i> Save &amp; continue
                                </span>
                                <span wire:loading wire:target="submitApproval,signatureFile">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Saving…
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card hq-panel-card border-0 mb-4" style="border-left:4px solid #059669 !important;">
                    <div class="card-header bg-white border-0">
                        <h5 class="font-weight-bold text-success mb-0">
                            <i class="fas fa-check-circle mr-1"></i> All authorisations complete
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        <p class="text-muted small mb-3">{{ $completionSummary }}</p>

                        <div class="d-flex flex-column" style="gap:0.5rem;">
                            <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}" class="btn btn-success shadow-sm font-weight-bold">
                                <i class="fas fa-inbox mr-1"></i> Back to pending tasks
                            </a>
                            @if($nextPendingPhase)
                                <a href="{{ route('secretariat.phases.review', $nextPendingPhase) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Review next: {{ $nextPendingPhase->constituency?->constituency_name ?? $nextPendingPhase->constituency_code }}
                                    · {{ $nextPendingPhase->phase }}
                                </a>
                            @endif
                            <a href="{{ route('secretariat.phases.index', ['filter' => 'completed']) }}" class="btn btn-link btn-sm text-muted">
                                View fully authorized phases
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if($approvals->isNotEmpty())
                <div class="card hq-panel-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="font-weight-bold text-navy text-uppercase small mb-0">Authorization history</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($approvalTypes as $type => $label)
                            @php $record = $approvals->get($type); @endphp
                            @if($record?->approved_at)
                                <li class="list-group-item py-3">
                                    <div class="font-weight-bold text-navy">{{ $label }}</div>
                                    <small class="text-muted">
                                        {{ $record->approved_at->format('d M Y H:i') }}
                                        @if($record->approver)
                                            · {{ $record->approver->name }}
                                        @endif
                                    </small>
                                    @if($record->notes)
                                        <p class="text-xs text-gray-600 mb-0 mt-1">{{ $record->notes }}</p>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <x-action-modal
        id="secretariat-return-hq-modal"
        title="Return Phase to HQ"
        type="warning"
        icon="fas fa-undo"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'secretariat-return-hq-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'secretariat-return-hq-modal') $($el).modal('hide')"
    >
        <div class="alert alert-warning border-left-warning shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
                <div>
                    <strong>Return for HQ review</strong><br>
                    <span class="small">The assigned HQ officer will be notified by email and SMS to review this batch again.</span>
                </div>
            </div>
        </div>

        <p class="mb-2 text-muted">Phase: <strong>{{ $phase->phase }}</strong> · {{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}</p>

        <div class="form-group" wire:key="secretariat-return-remarks">
            <label class="text-xs font-weight-bold text-uppercase">Reason for return <span class="text-danger">*</span></label>
            <textarea class="form-control" wire:model.blur="returnRemarks" rows="4" placeholder="Explain what HQ must review or correct…"></textarea>
            @error('returnRemarks') <small class="text-danger d-block mt-1 font-weight-bold">{{ $message }}</small> @enderror
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Cancel"
                    variant="outline"
                    color="secondary"
                    data-dismiss="modal"
                    size="sm"
                    class="mx-1"
                />
                <x-pill-button
                    label="Confirm Return to HQ"
                    target="executeReturnToHq"
                    color="warning"
                    icon="fas fa-undo"
                    size="sm"
                    class="shadow-sm mx-1"
                />
            </div>
        </x-slot>
    </x-action-modal>
</div>
