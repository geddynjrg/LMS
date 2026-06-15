<div class="card border-0 animate-on-load">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 font-weight-bold text-dark text-uppercase" style="letter-spacing: 1px;">
                <i class="fas fa-history mr-2 text-primary"></i> Prior Loan Performance
            </h6>
            <class="text-muted">Filtered by: <span class="badge badge-soft-info">Financed Loans Only</span></class=>
        </div>

        @if ($this->application->link_status === 'linked' && $this->application->phaseApplication->group_id)
            <div class="text-right">
                <span class="badge badge-pill badge-{{ $this->application->phaseApplication->linkedGroup->defaulted_count > 0 ? 'danger' : 'success' }}-light py-2 px-3">
                    <i class="fas {{ $this->application->phaseApplication->linkedGroup->defaulted_count > 0 ? 'fa-exclamation-circle' : 'fa-check-double' }} mr-1"></i>
                    {{ $this->application->phaseApplication->linkedGroup->defaulted_count > 0 ? $this->application->phaseApplication->linkedGroup->defaulted_count . ' Defaulted Records' : 'No Defaults Detected' }}
                </span>
            </div>
        @endif
    </div>

    <div class="card-body p-0">
        @if ($this->application->link_status === 'linked' && $this->application->phaseApplication->group_id)

            <div class="row px-4 mb-4 mt-2">
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white shadow-sm h-100">
                        <class="text-muted text-uppercase d-block mb-1 font-weight-bold" style="font-size: 0.7rem;">Lifetime Borrowing</class=>
                        <h4 class="font-weight-bold mb-0 text-dark">KES {{ number_format($this->application->phaseApplication->linkedGroup->total_issued, 0) }}</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white shadow-sm h-100">
                        <class="text-muted text-uppercase d-block mb-1 font-weight-bold" style="font-size: 0.7rem;">Total Repaid</class=>
                        <h4 class="font-weight-bold text-success mb-0">KES {{ number_format($this->application->phaseApplication->linkedGroup->total_repaid, 0) }}</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white shadow-sm h-100 border-{{ $this->application->phaseApplication->linkedGroup->total_arrears > 0 ? 'danger' : 'light' }}">
                        <class="text-muted text-uppercase d-block mb-1 font-weight-bold" style="font-size: 0.7rem;">Current Arrears</class=>
                        <h4 class="font-weight-bold text-{{ $this->application->phaseApplication->linkedGroup->total_arrears > 0 ? 'danger' : 'dark' }} mb-0">
                            KES {{ number_format($this->application->phaseApplication->linkedGroup->total_arrears, 0) }}
                        </h4>
                    </div>
                </div>
            </div>

            <hr class="mx-4 my-4">

            <div class="timeline-container px-4 pb-4">
                @forelse ($this->application->phaseApplication->linkedGroup->groupLoans as $loan)
                    <div class="loan-entry mb-5 position-relative pl-4 border-left" style="border-left: 2px solid #e9ecef !important;">
                        <div class="position-absolute" style="left: -9px; top: 0;">
                            <i class="fas fa-circle text-{{ $loan->total_arrears <= 0 ? 'success' : 'danger' }}"
                               style="font-size: 14px; background: white; padding: 2px; box-shadow: 0 0 0 3px white;"></i>
                        </div>

                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge badge-soft-dark text-uppercase mb-1" style="font-size: 0.65rem;">
                                    {{ $loan->loanPhase->phase ?? 'PHASE NOT DEFINED' }}
                                </span>
                                <h6 class="font-weight-bold mb-0 text-lg">KES {{ number_format($loan->amount_issued, 0) }}</h6>
                                <class="text-muted">
                                    <i class="far fa-calendar-check mr-1"></i> Disbursed: {{ date('D, j M Y', strtotime($loan->disbursement_date)) }}
                                </class=>
                            </div>

                            <div class="text-right d-flex flex-column align-items-end">
                                <button wire:click="downloadStatement({{ $loan->id }})"
                                        wire:loading.attr="disabled"
                                        class="btn btn-sm btn-white border shadow-sm mb-2 hover-shadow transition-all">

                                    <span wire:loading.remove wire:target="downloadStatement({{ $loan->id }})">
                                        <i class="fas fa-file-pdf text-danger mr-1"></i>
                                    </span>

                                    <span wire:loading wire:target="downloadStatement({{ $loan->id }})" class="spinner-border spinner-border-sm text-primary mr-1" role="status"></span>

                                    <span class="font-weight-bold">Print Statement</span>
                                </button>

                                @php
                                    $percent = $loan->amount_issued > 0 ? ($loan->amount_repaid / $loan->amount_issued) * 100 : 0;
                                @endphp
                                <span class="badge badge-{{ $percent >= 100 ? 'success' : ($percent > 50 ? 'warning' : 'danger') }}-light py-1 px-2">
                                    {{ number_format($percent, 1) }}% Recovered
                                </span>
                            </div>
                        </div>

                        <div class="bg-light rounded p-3 mb-3 border-0">
                            <div class="row">
                                <div class="col-6 col-md-3 mb-2 mb-md-0">
                                    <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Amount Paid</label>
                                    <p class="font-weight-bold text-success mb-0">KES {{ number_format($loan->amount_repaid, 0) }}</p>
                                </div>
                                <div class="col-6 col-md-3 mb-2 mb-md-0 border-left">
                                    <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Balance</label>
                                    <p class="font-weight-bold mb-0">KES {{ number_format($loan->amount_issued - $loan->amount_repaid, 0) }}</p>
                                </div>
                                <div class="col-6 col-md-3 border-left">
                                    <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Arrears</label>
                                    <p class="font-weight-bold text-{{ $loan->total_arrears > 0 ? 'danger' : 'dark' }} mb-0">KES {{ number_format($loan->total_arrears, 0) }}</p>
                                </div>
                                <div class="col-6 col-md-3 border-left">
                                    <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Receipt Count</label>
                                    <p class="font-weight-bold mb-0">{{ $loan->loanReceipts->count() }} Transactions</p>
                                </div>
                            </div>
                        </div>

                        <div x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="btn btn-link btn-sm p-0 text-decoration-none shadow-none text-muted font-weight-bold">
                                <i class="fas mr-1" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                <span x-text="open ? 'Hide Transaction Details' : 'View Transaction Details'"></span>
                            </button>

                            <div x-show="open" x-collapse x-cloak>
                                <div class="table-responsive mt-2 border rounded bg-white shadow-sm">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light text-uppercase">
                                            <tr>
                                                <th class="border-0 pl-3">Receipt No</th>
                                                <th class="border-0">Date</th>
                                                <th class="border-0">Reference</th>
                                                <th class="border-0 text-right pr-3">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-dark">
                                            @forelse ($loan->loanReceipts as $repayment)
                                                <tr>
                                                    <td class="pl-3 font-weight-bold">{{ $repayment->receipt_number }}</td>
                                                    <td>{{ date('j M, Y', strtotime($repayment->transaction_date)) }}</td>
                                                    <td class="text-muted">{{ $repayment->reference_number }}</td>
                                                    <td class="text-right pr-3 font-weight-bold text-success">
                                                        {{ number_format($repayment->amount, 0) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">No receipts found for this loan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-folder-open fa-3x text-light"></i>
                        </div>
                        <h6 class="font-weight-bold">No History Found</h6>
                        <p class="text-muted small">No 'Financed' loans are currently associated with this group link.</p>
                    </div>
                @endforelse
            </div>

        @else
            <div class="text-center py-5 px-4 bg-light mx-4 my-4 rounded border-dashed" style="border: 2px dashed #dee2e6;">
                <div class="avatar-soft-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fas fa-link-slash fa-lg"></i>
                </div>
                <h6 class="font-weight-bold">History Unavailable</h6>
                <p class="text-muted mx-auto" style="max-width: 350px;">
                    {{ $this->application->link_status === 'attempted_no_match'
                        ? 'System attempted to sync data but could not find a Master Group match.'
                        : 'Please establish a link to the Master Group in the Verification tab to see prior loan performance.' }}
                </p>
            </div>
        @endif
    </div>
</div>
