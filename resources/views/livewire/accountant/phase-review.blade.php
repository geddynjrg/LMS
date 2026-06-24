<div class="accounts-review-page">
    <div class="mb-4">
        <a href="{{ route('accountant.phases.index') }}" class="btn btn-link px-0 text-teal font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Back to accounts dashboard
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
            @php
                $status = $phase->accountant_review_status;
                $statusLabel = \App\Support\AccountantReviewStatus::labels()[$status] ?? 'Awaiting review';
            @endphp
            <span class="badge badge-pill badge-light border px-3 py-2">{{ $statusLabel }}</span>
        </div>
    </div>

    @if($phase->hq_accountant_resubmit_notes)
        <div class="alert alert-info border-left-info shadow-sm mb-4" style="border-left: 4px solid #17a2b8 !important;">
            <div class="d-flex align-items-start">
                <i class="fas fa-reply fa-lg text-info mr-3 mt-1"></i>
                <div>
                    <div class="font-weight-bold text-navy mb-1">HQ resubmission notes</div>
                    <p class="mb-2">{{ $phase->hq_accountant_resubmit_notes }}</p>
                    <small class="text-muted">
                        @if($phase->hqAccountantResubmittedBy)
                            Submitted by {{ $phase->hqAccountantResubmittedBy->name }}
                        @endif
                        @if($phase->hq_accountant_resubmitted_at)
                            · {{ $phase->hq_accountant_resubmitted_at->format('d M Y H:i') }}
                        @endif
                    </small>
                </div>
            </div>
        </div>
    @endif

    @if($phase->accountant_notes && $phase->accountant_review_status === \App\Support\AccountantReviewStatus::PENDING && $phase->hq_accountant_resubmit_notes)
        <div class="alert alert-warning border-left-warning shadow-sm mb-4" style="border-left: 4px solid #f59e0b !important;">
            <div class="font-weight-bold text-navy mb-1">Previous accounts return notes</div>
            <p class="mb-0">{{ $phase->accountant_notes }}</p>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card hq-panel-card mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="font-weight-bold text-navy text-uppercase small mb-0">
                        <i class="fas fa-folder-open text-teal mr-2"></i>Documents for review
                    </h6>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="border rounded-lg p-3 h-100 {{ $codeListDoc ? 'border-success bg-light' : 'border-secondary' }}" style="border-radius:12px;">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="rounded bg-success text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fas fa-file-excel"></i>
                                    </div>
                                    <span class="badge badge-success">HQ upload</span>
                                </div>
                                <div class="font-weight-bold text-navy">Processed code list</div>
                                <small class="text-muted d-block mb-2">Excel workbook uploaded by HQ after processing loans for this phase.</small>
                                <div class="small text-truncate text-muted mb-2" title="{{ $codeListDoc?->original_filename }}">
                                    {{ $codeListDoc?->original_filename ?? 'Not uploaded yet' }}
                                </div>
                                @if($codeListDoc)
                                    <a href="{{ route('accountant.phases.processed_code_list.preview', $phase) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-info mr-1">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                    <a href="{{ route('phase.doc.serve', ['id' => $codeListDoc->id, 'action' => 'view']) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary mr-1"
                                       title="Open uploaded file">
                                        <i class="fas fa-file-excel mr-1"></i> Uploaded file
                                    </a>
                                @endif
                                <a href="{{ route('accountant.phases.processed_code_list', $phase) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download mr-1"></i> Download Excel
                                </a>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="border rounded-lg p-3 h-100 {{ $bankReconMainDoc ? 'border-primary bg-light' : 'border-secondary' }}" style="border-radius:12px;">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="rounded bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <span class="badge badge-primary">Constituency</span>
                                </div>
                                <div class="font-weight-bold text-navy">Main account reconciliation</div>
                                <small class="text-muted d-block mb-2">Current main account bank reconciliation from the constituency.</small>
                                <div class="small text-truncate text-muted mb-2" title="{{ $bankReconMainDoc?->original_filename }}">
                                    {{ $bankReconMainDoc?->original_filename ?? 'Not on file' }}
                                    @if($bankReconMainDoc?->statement_period_label)
                                        <span class="d-block">Statement: {{ $bankReconMainDoc->statement_period_label }}</span>
                                    @endif
                                </div>
                                @include('partials.phase-document-actions', ['doc' => $bankReconMainDoc, 'labeled' => true])
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="border rounded-lg p-3 h-100 {{ $bankReconRepaymentDoc ? 'border-info bg-light' : 'border-secondary' }}" style="border-radius:12px;">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="rounded bg-info text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </div>
                                    <span class="badge badge-info">Constituency</span>
                                </div>
                                <div class="font-weight-bold text-navy">Loan repayment reconciliation</div>
                                <small class="text-muted d-block mb-2">Current loan repayment account reconciliation from the constituency.</small>
                                <div class="small text-truncate text-muted mb-2" title="{{ $bankReconRepaymentDoc?->original_filename }}">
                                    {{ $bankReconRepaymentDoc?->original_filename ?? 'Not on file' }}
                                    @if($bankReconRepaymentDoc?->statement_period_label)
                                        <span class="d-block">Statement: {{ $bankReconRepaymentDoc->statement_period_label }}</span>
                                    @endif
                                </div>
                                @include('partials.phase-document-actions', ['doc' => $bankReconRepaymentDoc, 'labeled' => true])
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="border rounded-lg p-3 h-100 {{ $transferLetterDoc ? 'border-warning bg-light' : 'border-secondary' }}" style="border-radius:12px;">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="rounded bg-warning text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fas fa-file-signature"></i>
                                    </div>
                                    <span class="badge badge-warning text-dark">Constituency</span>
                                </div>
                                <div class="font-weight-bold text-navy">Request for transfer letter</div>
                                <small class="text-muted d-block mb-2">Uploaded by the constituency before HQ submission.</small>
                                <div class="small text-truncate text-muted mb-2" title="{{ $transferLetterDoc?->original_filename }}">
                                    {{ $transferLetterDoc?->original_filename ?? 'Not on file' }}
                                </div>
                                @include('partials.phase-document-actions', ['doc' => $transferLetterDoc, 'labeled' => true])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($phase->accountant_notes)
                <div class="alert alert-light border hq-inbox-item mb-0">
                    <div class="small text-uppercase font-weight-bold text-muted mb-1">Previous accounts notes</div>
                    {{ $phase->accountant_notes }}
                    @if($phase->accountantReviewer)
                        <div class="small text-muted mt-1">— {{ $phase->accountantReviewer->name }}, {{ $phase->accountant_reviewed_at?->format('d M Y H:i') }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card hq-panel-card border-success mb-3" style="border-left:4px solid #059669 !important;">
                <div class="card-header bg-white border-0">
                    <h6 class="font-weight-bold text-success mb-0">
                        <i class="fas fa-paper-plane mr-2"></i>Forward to HQ management
                    </h6>
                </div>
                <div class="card-body pt-0">
                    @if(!$bankReconMainDoc || !$bankReconRepaymentDoc)
                        <div class="alert alert-warning small mb-3">
                            Both current bank reconciliations (main account and loan repayment) must be on file before approval.
                            @if(!$bankReconMainDoc) Main account reconciliation is missing. @endif
                            @if(!$bankReconRepaymentDoc) Loan repayment reconciliation is missing. @endif
                        </div>
                    @endif
                    <p class="text-muted small">If the processed code list, bank reconciliations, and transfer letter are correct, add notes and send to HQ for management committee approval.</p>
                    <div class="form-group">
                        <label class="small font-weight-bold text-uppercase text-muted">Notes for HQ</label>
                        <textarea wire:model="notes" class="form-control border" rows="4" placeholder="Accounts review notes…" style="border-radius:10px;"></textarea>
                    </div>
                    <button type="button" wire:click="approveForHq"
                            class="btn btn-success btn-block shadow-sm font-weight-bold"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="approveForHq">
                            <i class="fas fa-check mr-1"></i> Approve &amp; send to HQ management
                        </span>
                        <span wire:loading wire:target="approveForHq">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Saving…
                        </span>
                    </button>
                </div>
            </div>

            <div class="card hq-panel-card border-warning" style="border-left:4px solid #f59e0b !important;">
                <div class="card-header bg-white border-0">
                    <h6 class="font-weight-bold text-warning mb-0">
                        <i class="fas fa-reply mr-2"></i>Return to HQ for clarification
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <p class="text-muted small">If the processed code list or bank reconciliation is not correct, return to HQ with notes. HQ will follow up with the constituency if needed.</p>
                    <div class="form-group">
                        <label class="small font-weight-bold text-uppercase text-muted">Clarification notes for HQ</label>
                        <textarea wire:model="returnComments" class="form-control border" rows="4" placeholder="What must be corrected…" style="border-radius:10px;"></textarea>
                    </div>
                    <button type="button" wire:click="returnToHqForClarification"
                            class="btn btn-warning btn-block shadow-sm font-weight-bold"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="returnToHqForClarification">
                            <i class="fas fa-undo mr-1"></i> Return to HQ with notes
                        </span>
                        <span wire:loading wire:target="returnToHqForClarification">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Sending…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
