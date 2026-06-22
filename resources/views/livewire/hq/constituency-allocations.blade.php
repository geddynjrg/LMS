<div class="hq-allocations-shell bg-white p-3 p-md-4">
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card hq-kpi-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <span class="hq-sidebar-label d-block">Profiles saved</span>
                    <h3 class="font-weight-bold mb-0">{{ $this->savedProfilesCount }}</h3>
                    <small class="text-muted">Constituencies with allocation data</small>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="alert alert-light border hq-inbox-item mb-0 h-100">
                <strong class="text-navy">Used in processed code lists</strong>
                <p class="mb-0 small text-muted">
                    These figures appear on the right-hand summary panel when HQ generates or exports a code list Excel workbook
                    (2013/2014 through 2025/2026 allocations, dynamic transfer levels, phase issued amounts, and balance).
                </p>
            </div>
        </div>
    </div>

    <div class="card hq-panel-card hq-filter-card border-0 mb-4">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="font-weight-bold text-navy text-uppercase small mb-0">Select constituency</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase">County</label>
                    <x-dropdown-search
                        wire:key="alloc-county-{{ count($countyOptions) }}"
                        model="countyFilter"
                        :options="$countyOptions"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select county"
                        icon="fas fa-map-marker-alt"
                    />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small font-weight-bold text-muted text-uppercase">Constituency</label>
                    @if(!empty($constituencyOptions))
                        <x-dropdown-search
                            wire:key="alloc-constituency-{{ $countyFilter }}-{{ count($constituencyOptions) }}"
                            model="constituencyFilter"
                            :options="$constituencyOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select constituency"
                            icon="fas fa-map-signs"
                        />
                    @else
                        <div class="form-control bg-light text-muted">Select a county first</div>
                    @endif
                </div>
            </div>

            @if($this->selectedConstituency)
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a href="{{ route('hq.allocations.template', $this->selectedConstituency->slug) }}"
                       class="btn btn-outline-success btn-sm shadow-sm">
                        <i class="fas fa-file-excel mr-1"></i> Download Excel template (prefilled)
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if($constituencyFilter)
        <div class="row">
            <div class="col-lg-7">
                <div class="card hq-panel-card border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="font-weight-bold text-navy text-uppercase small mb-0">Allocations by year (KES)</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            @foreach($allocationLabels as $key => $label)
                                <div class="col-12 mb-3">
                                    <label class="small font-weight-bold text-muted">{{ $label }}</label>
                                    <input type="number" min="0" step="0.01"
                                           wire:model.live.debounce.300ms="allocations.{{ $key }}"
                                           class="form-control"
                                           placeholder="0">
                                    @error('allocations.'.$key) <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card hq-panel-card border-0 mb-4">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h6 class="font-weight-bold text-navy text-uppercase small mb-0">Transfers (optional, KES)</h6>
                        <button type="button" wire:click="addTransferLevel" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus mr-1"></i> Add Transfer Level
                        </button>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            @foreach($transferLevels as $index => $transfer)
                                <div class="col-md-6 col-lg-4 mb-3" wire:key="transfer-{{ $transfer['level'] }}-{{ $index }}">
                                    <label class="small font-weight-bold text-muted">
                                        {{ \App\Support\ConstituencyAllocationFields::transferLabelForLevel($transfer['level']) }}
                                    </label>
                                    <input type="number" min="0" step="0.01"
                                           wire:model.live.debounce.300ms="transferLevels.{{ $index }}.amount"
                                           class="form-control"
                                           placeholder="0">
                                </div>
                            @endforeach
                        </div>

                        <div class="border-top pt-3 mt-2">
                            <div class="row">
                                <div class="col-12 mb-0">
                                    <label class="small font-weight-bold text-muted">Total Loan Portfolio</label>
                                    <input type="text" readonly
                                           value="KES {{ number_format($this->computedTotalLoanPortfolio, 2) }}"
                                           class="form-control bg-light font-weight-bold">
                                    <small class="text-muted">
                                        Total Allocations (KES {{ number_format($this->computedTotalAllocations, 2) }})
                                        + Total Transfers (KES {{ number_format($this->computedTotalTransfers, 2) }})
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card hq-panel-card border-0 mb-4">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h6 class="font-weight-bold text-navy text-uppercase small mb-0">Actual Amount Issued by Phase (KES)</h6>
                        <button type="button" wire:click="addPhaseIssuedAmount" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Add Phase
                        </button>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            @foreach($phaseIssuedAmounts as $index => $phaseRow)
                                <div class="col-12 mb-3" wire:key="phase-{{ $phaseRow['phase'] }}-{{ $index }}">
                                    <label class="small font-weight-bold text-muted">
                                        {{ \App\Support\ConstituencyAllocationFields::phaseIssuedLabel($phaseRow['phase']) }}
                                    </label>
                                    <input type="number" min="0" step="0.01"
                                           wire:model.live.debounce.300ms="phaseIssuedAmounts.{{ $index }}.amount"
                                           class="form-control"
                                           placeholder="0">
                                </div>
                            @endforeach
                        </div>

                        <div class="border-top pt-3 mt-2">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="small font-weight-bold text-muted">Total Approvals</label>
                                    <input type="text" readonly
                                           value="KES {{ number_format($this->computedTotalApprovals, 2) }}"
                                           class="form-control bg-light font-weight-bold">
                                    <small class="text-muted">Sum of all phase issued amounts</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card hq-panel-card border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="font-weight-bold text-navy text-uppercase small mb-0">Account balance (read only)</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-muted">Balance in the main account</label>
                                <input type="text" readonly
                                       value="KES {{ number_format($this->computedBalanceMainAccount, 2) }}"
                                       class="form-control bg-light font-weight-bold">
                                <small class="text-muted">Total Loan Portfolio minus Total Approvals</small>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="save" class="btn btn-success shadow-sm font-weight-bold mb-4" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save"><i class="fas fa-save mr-1"></i> Save allocation profile</span>
                    <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin mr-1"></i> Saving…</span>
                </button>
            </div>

            <div class="col-lg-5">
                <div class="card hq-panel-card border-0 border-left border-success" style="border-left-width:4px !important;">
                    <div class="card-header bg-white border-0">
                        <h6 class="font-weight-bold text-success text-uppercase small mb-0">
                            <i class="fas fa-upload mr-1"></i> Import from Excel
                        </h6>
                    </div>
                    <div class="card-body pt-0">
                        <p class="text-muted small">
                            Download the template for this constituency, update the amounts in Excel, then upload the same file here.
                        </p>
                        <x-file-upload model="uploadFile" height="48px" accept=".xls,.xlsx" />
                        @error('uploadFile') <small class="text-danger d-block mt-2">{{ $message }}</small> @enderror
                        <button type="button" wire:click="importTemplate" class="btn btn-outline-success btn-block mt-3 font-weight-bold" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="importTemplate,uploadFile"><i class="fas fa-file-import mr-1"></i> Upload &amp; import</span>
                            <span wire:loading wire:target="importTemplate,uploadFile"><i class="fas fa-spinner fa-spin mr-1"></i> Importing…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center text-muted py-5">
            <i class="fas fa-map-marked-alt fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">Select a county and constituency to manage allocation figures.</p>
        </div>
    @endif
</div>
