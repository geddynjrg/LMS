<div class="accounts-dashboard">
    <div class="row mb-4">
        <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card hq-kpi-card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <span class="hq-sidebar-label d-block">Pending review</span>
                        <h3 class="font-weight-bold text-gray-900 mb-1">{{ $pendingCount }}</h3>
                        <small class="text-muted">Awaiting accounts action</small>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-white" style="width:44px;height:44px;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card hq-kpi-card h-100 shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <span class="hq-sidebar-label d-block">Cleared for HQ</span>
                        <h3 class="font-weight-bold text-gray-900 mb-1">{{ $clearedCount }}</h3>
                        <small class="text-muted">Forwarded to management</small>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white" style="width:44px;height:44px;">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 d-none d-xl-flex align-items-stretch">
            <div class="card hq-panel-card w-100 mb-0">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="mr-3 text-teal" style="font-size:1.75rem;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <div class="font-weight-bold text-navy small text-uppercase">What you review</div>
                        <p class="mb-0 text-muted small">
                            <strong>Processed code list</strong> (HQ Excel) and
                            <strong>bank reconciliation</strong> from the constituency submission.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="hq-workspace-shell bg-white p-3 p-md-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <ul class="nav hq-workspace-tabs mb-2 mb-md-0">
                <li class="nav-item">
                    <button type="button"
                            wire:click="$set('filter', 'pending')"
                            class="nav-link {{ $filter === 'pending' ? 'active' : '' }}">
                        <i class="fas fa-inbox mr-1"></i> Pending review
                        @if($pendingCount > 0)
                            <span class="badge badge-light text-dark ml-1">{{ $pendingCount }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button"
                            wire:click="$set('filter', 'approved')"
                            class="nav-link {{ $filter === 'approved' ? 'active' : '' }}"
                            style="{{ $filter === 'approved' ? 'background:#0d9488 !important;' : '' }}">
                        <i class="fas fa-check-circle mr-1"></i> Cleared for HQ management
                        @if($clearedCount > 0)
                            <span class="badge badge-light text-dark ml-1">{{ $clearedCount }}</span>
                        @endif
                    </button>
                </li>
            </ul>
            <div wire:loading wire:target="filter" class="text-muted small">
                <span class="spinner-border spinner-border-sm text-teal mr-1"></span> Updating…
            </div>
        </div>

        <div class="card hq-panel-card border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 text-uppercase small text-muted">Phase</th>
                            <th class="border-0 text-uppercase small text-muted">Constituency</th>
                            <th class="border-0 text-uppercase small text-muted">Submitted to HQ</th>
                            <th class="border-0 text-uppercase small text-muted">Documents</th>
                            <th class="border-0 text-uppercase small text-muted">Status</th>
                            <th class="border-0 text-uppercase small text-muted text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($phases as $phase)
                            @php
                                $docs = $phase->phaseApplicationDocuments->keyBy('document_type');
                                $processedDoc = $docs->get('processed_code_list') ?? $docs->get('approved_code_list');
                                $bankMainDoc = $docs->get('bank_reconciliation_main_account') ?? $docs->get('bank_reconciliation');
                                $bankRepaymentDoc = $docs->get('bank_reconciliation_loan_repayment');
                                $transferDoc = $docs->get('request_for_transfer_letter');
                                $status = $phase->accountant_review_status;
                                $statusLabel = \App\Support\AccountantReviewStatus::labels()[$status] ?? 'Awaiting review';
                                $statusClass = match($status) {
                                    'approved' => 'badge-success',
                                    'returned_to_hq' => 'badge-warning',
                                    default => 'badge-info',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-navy">{{ $phase->phase }}</div>
                                    <small class="text-muted">{{ $phase->phase_ref }}</small>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                    {{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}
                                </td>
                                <td>
                                    <span class="text-nowrap">{{ $phase->submitted_to_hq_at?->format('d M Y') ?? '—' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap" style="gap:0.35rem;">
                                        <span class="badge {{ $processedDoc ? 'badge-light border text-success' : 'badge-secondary' }}">
                                            <i class="fas fa-file-excel mr-1"></i> Processed list
                                        </span>
                                        <span class="badge {{ $bankMainDoc ? 'badge-light border text-primary' : 'badge-secondary' }}">
                                            <i class="fas fa-university mr-1"></i> Main recon
                                        </span>
                                        <span class="badge {{ $bankRepaymentDoc ? 'badge-light border text-info' : 'badge-secondary' }}">
                                            <i class="fas fa-hand-holding-usd mr-1"></i> Repayment recon
                                        </span>
                                        @if($transferDoc)
                                            <span class="badge badge-light border text-info">
                                                <i class="fas fa-file-signature mr-1"></i> Transfer letter
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }} px-2 py-1">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('accountant.phases.review', $phase) }}"
                                       class="btn btn-sm btn-teal shadow-sm text-white"
                                       style="background:#0d9488;border-color:#0d9488;">
                                        <i class="fas fa-search mr-1"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-2" style="font-size:2rem;">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <div class="font-weight-bold text-navy">No phases in this queue</div>
                                    <p class="text-muted small mb-0">
                                        @if($filter === 'pending')
                                            Phases appear here after HQ uploads a processed code list for a constituency submission.
                                        @else
                                            No phases have been cleared for HQ management yet.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($phases->hasPages())
                <div class="card-footer bg-white border-top">{{ $phases->links() }}</div>
            @endif
        </div>
    </div>
</div>
