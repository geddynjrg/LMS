<section class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            @foreach($this->getStats as $key => $kpi)
                <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                    <div class="card card-hover shadow-sm border-left-success h-100 py-2">
                        <div class="card-body py-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-xs font-weight-bold  d-block text-primary text-uppercase mb-1">{{ $kpi['title'] }}</span>
                                    <span class="h5 mb-1 font-weight-bold text-gray-900">KES {{ number_format($kpi['val']) }}</span>
                                </div>
                                <div class="icon-circle bg-light-info text-primary">
                                    <i class="fas {{ $kpi['icon'] }}"></i>
                                </div>
                            </div>
                            <hr class="my-2">

                            <div class="col">
                                <small class="text-xs text-gray-500">{{ $kpi['desc'] }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card card-outline card-info shadow-sm border-0 mt-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                    <h3 class="card-title mb-3 mb-md-0 mr-md-4">
                        <i class="fas fa-list text-primary mr-2"></i> Group Loans
                    </h3>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-md-end flex-md-grow-1" style="gap: 15px;">

                        <div class="input-group premium-input-group order-2 transition-all"
                            x-data="{ focused: false }"
                            :class="{ 'premium-focus': focused }"
                            style="min-width: 250px; max-width: 350px;">

                            <input type="text"
                                wire:model.live.debounce.500ms="search"
                                @focus="focused = true"
                                @blur="focused = false"
                                class="form-control border-right-0 shadow-none text-sm"
                                placeholder="Search Applications..."
                                style="height: 32px; border-radius: 10px 0 0 10px; font-size: 0.8rem; border-color: #ced4da; padding-left: 12px;">

                            <div class="input-group-append">
                                <span class="input-group-text bg-white border-left-0 py-0"
                                    style="border-radius: 0 10px 10px 0; height: 32px; width: 35px; justify-content: center; transition: 0.2s;">
                                    <i class="fas fa-search text-muted"
                                    :class="focused ? 'text-brand animate__animated animate__pulse' : ''"
                                    style="font-size: 0.8rem;"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex order-1 order-sm-2" style="gap: 8px;">
                            <div class="btn-group">
                                <x-loading-button
                                    title="refresh data"
                                    target="refreshData"
                                    class="btn-default"
                                    icon="fas fa-sync-alt"
                                    label=""
                                />
                                <x-loading-button
                                    title="clear filters"
                                    target="resetFilters"
                                    class="btn-default"
                                    icon="fas fa-filter"
                                    label=""
                                />
                            </div>

                            {{-- <x-loading-button
                                target="exportGroupLoans"
                                class="btn-outline-dark"
                                icon="fas fa-file-excel"
                                label="Export Data"
                                loadingLabel="..."
                            /> --}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Filters --}}
                <div class="row mb-3">
                    {{-- County Filter --}}
                    <div class="col-sm-2 col-6">
                        <label class="small font-weight-bold text-muted ml-1">COUNTY</label>
                        <x-dropdown-search
                            wire:key="dropdown-n-countyFilter-{{ count($countyOptions) }}"
                            model="countyFilter"
                            :options="$countyOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select County"
                            icon="fas fa-map-marker-alt"
                        />
                    </div>
                    <div class="col-sm-2 col-6" wire:key="constituency-n-wrapper-{{ $countyFilter }}">
                        @if(!empty($constituencyOptions))
                            <label class="small font-weight-bold text-muted ml-1">CONSTITUENCY</label>
                            <x-dropdown-search
                                wire:key="dropdown-{{ $countyFilter }}-{{ count($constituencyOptions) }}"
                                model="constituencyFilter"
                                :options="$constituencyOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Constituency"
                                icon="fas fa-map-signs"
                            />
                        @else
                            <label class="small text-muted ml-1 opacity-50">CONSTITUENCY</label>
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a county first...</small>
                                <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading constituencies...</small>
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-2 col-6" wire:key="wards-n-wrapper-{{ $constituencyFilter }}">
                        @if(!empty($wardsOptions))
                            <label class="small font-weight-bold text-muted ml-1">WARD</label>
                            <x-dropdown-search
                                wire:key="dropdown-{{ $constituencyFilter }}-{{ count($wardsOptions) }}"
                                model="wardFilter"
                                :options="$wardsOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Ward"
                                icon="fas fa-map-signs"
                            />
                        @else
                            <label class="small text-muted ml-1 opacity-50">WARD</label>
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a constituency first...</small>
                                <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading wards...</small>
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-2 col-6" wire:key="phase-n-wrapper-{{ $constituencyFilter }}">
                        @if(!empty($phaseOptions))
                            <label class="small font-weight-bold text-muted ml-1">PHASE</label>
                            <x-dropdown-search
                                wire:key="dropdown-{{ $constituencyFilter }}-{{ count($phaseOptions) }}"
                                model="phaseFilter"
                                :options="$phaseOptions"
                                optionLabel="phase"
                                optionValue="slug"
                                placeholder="Select Phase"
                                icon="fas fa-route"
                            />
                        @else
                            <label class="small text-muted ml-1 opacity-50">PHASE</label>
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a constituency first...</small>
                                <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading phases...</small>
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-2 col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Status</label>

                            <x-dropdown-search
                                model="statusFilter"
                                :options="$this->statusOptions"
                                optionLabel="name"
                                optionValue="value"
                                placeholder="select"
                                icon="fas fa-check-circle"
                            />
                        </div>
                    </div>
                    <div class="col-sm-2 col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">Disbursed From</label>
                        <div class="position-relative">
                            {{-- Slate Icon --}}
                            <div class="position-absolute d-flex align-items-center justify-content-center"
                                style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                            </div>

                            <input type="date"
                                wire:model.live="dateFrom"
                                onclick="this.showPicker()"
                                max="{{ date('Y-m-d') }}"
                                class="form-control shadow-none border-light-subtle custom-date-input"
                                style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                        </div>
                    </div>
                    <div class="col-sm-2 col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">Disbursed To</label>
                        <div class="position-relative">
                            {{-- Slate Icon --}}
                            <div class="position-absolute d-flex align-items-center justify-content-center"
                                style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                            </div>

                            <input type="date"
                                wire:model.live="dateTo"
                                onclick="this.showPicker()"
                                max="{{ date('Y-m-d') }}"
                                min="{{ $dateFrom }}"
                                class="form-control shadow-none border-light-subtle custom-date-input"
                                style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                        </div>
                    </div>
                    <div class="col-sm-2 col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">Display</label>
                        <x-dropdown-search
                            model="perPage"
                            :options="$this->perPageOptions"
                            icon="fas fa-list-ol"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="10 per page"
                        />
                    </div>
                </div>

                <div class="position-relative">
                    <div wire:loading.delay.shortest
                        wire:target="activeTab, nextPage, previousPage, gotoPage, updatedSearch, refreshData, sortBy, updatedWardFilter, updatedPhaseFilter, updatedDateFrom, updatedDateTo, updatedStatusFilter"
                        class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center"
                        style="background: rgba(255, 255, 255, 0.6);
                                backdrop-filter: blur(4px);
                                z-index: 1050;
                                top: 0;
                                left: 0;
                                transition: all 0.3s ease;
                                display: none !important;">

                        <div class="text-center bg-white p-4 rounded-lg shadow-lg border" style="min-width: 180px;">

                            <div class="mb-3">
                                <i class="fas fa-circle-notch fa-spin fa-2x text-primary"></i>
                            </div>
                            <p class="m-0 text-uppercase tracking-wider font-weight-bold text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">
                                Loading...
                            </p>
                            <small class="text-gray-500">Updating Registry</small>
                        </div>
                    </div>

                    <div class="table-responsive p-0"
                        style="max-height: 650px; transition: opacity 0.3s ease;"
                        wire:key="hq-applications">
                        <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                            <thead>
                                <tr>
                                    <x-table-header
                                        field="county"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Location
                                    </x-table-header>
                                    <x-table-header
                                        field="groupName"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Group
                                    </x-table-header>
                                    <x-table-header
                                        field="phase"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Phase
                                    </x-table-header>
                                    <x-table-header
                                        field="status"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Status
                                    </x-table-header>
                                    <x-table-header
                                        field="disbursement_date"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Disbursement
                                    </x-table-header>
                                    <x-table-header
                                        field="issued"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Issued
                                    </x-table-header>
                                    <x-table-header
                                        field="repaid"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Repaid
                                    </x-table-header>
                                    <x-table-header
                                        field="balance"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Balance
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Action
                                    </x-table-header>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->groupLoans as $groupLoan)
                                    <tr wire:key="app-row-{{ $groupLoan->gl_id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                                        <td class="px-4 py-3">
                                            <span class="text-sm d-block text-gray-900">{{ $groupLoan->county_name }}</span>
                                            <span class="text-xs text-gray-600">{{ Str::ucfirst(Str::lower($groupLoan->constituency_name ?? 'N/A')) }} | {{ Str::ucfirst(Str::lower($groupLoan->ward_name ?? 'N/A')) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $groupLoan->name }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ ucwords($groupLoan->phase) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($groupLoan->status === 'completed')
                                                <span class="bg-blue-50 border-blue-200 text-blue-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                    Cleared
                                                </span>
                                            @elseif($groupLoan->status === 'active')
                                                <span class="bg-amber-50 border-amber-200 text-amber-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                    Performing
                                                </span>
                                            @else
                                                <span class="bg-red-50 border-red-200 text-red-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                    Defaulted
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $groupLoan->disbursement_date }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ number_format($groupLoan->amount_issued ?? 0, 2) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ number_format($groupLoan->amount_repaid ?? 0,2) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ number_format($groupLoan->balance ?? 0, 2) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex flex-column">
                                                <button type="button" class="btn btn-link btn-sm p-0 text-left" wire:click="viewGroupLoan({{ $groupLoan->gl_id }})">View</button>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-left" wire:click="openHqCorrectModal({{ $groupLoan->gl_id }})">Correct approved / GFS</button>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-left" wire:click="openRequestCorrectionModal({{ $groupLoan->gl_id }})">Prompt officer</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <h5 class="text-sm text-gray-500">No Groups found</h5>
                                        @if($search || $wardFilter || $phaseFilter || $dateFrom || $dateTo)
                                            <x-loading-button
                                                target="resetFilters"
                                                class="btn-refresh-premium"
                                                icon="fas fa-filter"
                                                label="Refresh Data"
                                            />
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-footer clearfix bg-transparent">
                <div class="pagination-sm">
                    {{ $this->groupLoans()->links() }}
                </div>
            </div>
        </div>
    </div>
    <x-action-modal
        id="loan-info-modal"
        title=" {{$selectedGroupLoan? $selectedGroupLoan->group->name . '(' . $selectedGroupLoan->loanPhase->phase .')' : ''}} RECEIPTS"
        type="primary"
        icon="fas fa-money-check-alt"
        size="modal-xl"
        x-on:open-modal.window="if($event.detail.name === 'loan-info-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'loan-info-modal') $($el).modal('hide')"
    >

        @if($selectedGroupLoan)
            <div class="loan-entry mb-5 position-relative pl-4 border-left" style="border-left: 2px solid #e9ecef !important;">
            <div class="position-absolute" style="left: -9px; top: 0;">
                <i class="fas fa-circle text-{{ $selectedGroupLoan->total_arrears <= 0 ? 'success' : 'danger' }}"
                    style="font-size: 14px; background: white; padding: 2px; box-shadow: 0 0 0 3px white;"></i>
            </div>

            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge badge-soft-dark text-uppercase mb-1" style="font-size: 0.65rem;">
                        {{ ucwords($selectedGroupLoan->loanPhase->phase ?? 'PHASE NOT DEFINED') }}
                    </span>
                    <h6 class="font-weight-bold mb-0 text-lg">KES {{ number_format($selectedGroupLoan->amount_issued, 0) }}</h6>
                    <class="text-muted">
                        <i class="far fa-calendar-check mr-1"></i> Disbursed: {{ date('D, j M Y', strtotime($selectedGroupLoan->disbursement_date)) }}
                    </class=>
                </div>

                <div class="text-right d-flex flex-column align-items-end">
                    <button wire:click="downloadStatement({{ $selectedGroupLoan->id }})"
                            wire:loading.attr="disabled"
                            class="btn btn-sm btn-white border shadow-sm mb-2 hover-shadow transition-all">

                        <span wire:loading.remove wire:target="downloadStatement({{ $selectedGroupLoan->id }})">
                            <i class="fas fa-file-pdf text-danger mr-1"></i>
                        </span>

                        <span wire:loading wire:target="downloadStatement({{ $selectedGroupLoan->id }})" class="spinner-border spinner-border-sm text-primary mr-1" role="status"></span>

                        <span class="font-weight-bold">Print Statement</span>
                    </button>

                    @php
                        $percent = $selectedGroupLoan->amount_issued > 0 ? ($selectedGroupLoan->amount_repaid / $selectedGroupLoan->amount_issued) * 100 : 0;
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
                        <p class="font-weight-bold text-success mb-0">KES {{ number_format($selectedGroupLoan->amount_repaid, 0) }}</p>
                    </div>
                    <div class="col-6 col-md-3 mb-2 mb-md-0 border-left">
                        <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Balance</label>
                        <p class="font-weight-bold mb-0">KES {{ number_format($selectedGroupLoan->amount_issued - $selectedGroupLoan->amount_repaid, 0) }}</p>
                    </div>
                    <div class="col-6 col-md-3 border-left">
                        <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Arrears</label>
                        <p class="font-weight-bold text-{{ $selectedGroupLoan->total_arrears > 0 ? 'danger' : 'dark' }} mb-0">KES {{ number_format($selectedGroupLoan->total_arrears, 0) }}</p>
                    </div>
                    <div class="col-6 col-md-3 border-left">
                        <label class="text-muted text-uppercase mb-0" style="font-size: 0.6rem;">Receipt Count</label>
                        <p class="font-weight-bold mb-0">{{ $selectedGroupLoan->loanReceipts->count() }} Transactions</p>
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
                                @forelse ($selectedGroupLoan->loanReceipts as $repayment)
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
                            @if($selectedGroupLoan->loanReceipts->isNotEmpty())
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-right pl-3">TOTAL REPAID:</td>
                                    <td class="text-right pr-3 text-primary">
                                        {{ number_format($selectedGroupLoan->total_active_repayments, 0) }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">

                <x-pill-button
                    label="Close"
                    target="closeGroupLoanModal"
                    color="primary"
                    icon="fas fa-edit"
                    data-dismiss="modal"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="hq-correct-historical-modal"
        title="Correct approved amount / GFS"
        type="info"
        icon="fa-edit"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'hq-correct-historical-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'hq-correct-historical-modal') $($el).modal('hide')"
    >
        <form wire:submit.prevent="saveHqHistoricalCorrection">
            <p class="small text-muted">Headquarters may correct these historical fields directly.</p>
            <div class="form-group">
                <label class="small font-weight-bold text-uppercase">GFS code</label>
                <input type="text" class="form-control" wire:model="editGfsCode">
                @error('gfsCode') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="small font-weight-bold text-uppercase">Approved amount (KES)</label>
                <input type="number" step="0.01" min="0" class="form-control" wire:model="editAmountApproved">
                @error('amountApproved') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save correction</button>
            </x-slot>
        </form>
    </x-action-modal>

    <x-action-modal
        id="hq-request-correction-modal"
        title="Prompt field officer to correct"
        type="warning"
        icon="fa-bell"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'hq-request-correction-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'hq-request-correction-modal') $($el).modal('hide')"
    >
        <form wire:submit.prevent="sendCorrectionPrompt">
            <p class="small mb-2">Group: <strong>{{ $promptGroupLabel }}</strong></p>
            <p class="small text-muted">Sends email and a login modal. The modal clears after the officer saves the selected fields.</p>
            <div class="form-group">
                <label class="small font-weight-bold text-uppercase d-block">Suggest corrections</label>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="pf_date" value="disbursement_date" wire:model="promptFields">
                    <label class="custom-control-label" for="pf_date">When the cheque was issued / date issued</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="pf_issued" value="amount_issued" wire:model="promptFields">
                    <label class="custom-control-label" for="pf_issued">How much was issued</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="pf_repaid" value="amount_repaid" wire:model="promptFields">
                    <label class="custom-control-label" for="pf_repaid">Amount repaid</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="pf_cheque" value="cheque_no" wire:model="promptFields">
                    <label class="custom-control-label" for="pf_cheque">Cheque number</label>
                </div>
            </div>
            <div class="form-group">
                <label class="small font-weight-bold text-uppercase">Message to officer</label>
                <textarea class="form-control" rows="3" wire:model="promptMessage" placeholder="Optional guidance"></textarea>
            </div>
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Send prompt</button>
            </x-slot>
        </form>
    </x-action-modal>
</section>