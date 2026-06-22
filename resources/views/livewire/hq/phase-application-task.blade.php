<section class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                <div class="card card-hover shadow-sm h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Applications</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-900">{{ number_format($this->detailedPhase->total_applications ?? 0) }}</div>

                        <hr class="my-2">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success font-weight-bold">{{ $this->detailedPhase->pending_submission_to_hq_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Apprv.</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            @if($phase->status === 'draft')
                            <div>
                                <span class="text-pink font-weight-bold">{{ $this->detailedPhase->pending_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Pending</small>
                            </div>
                            @else<div>
                                <span class="text-pink font-weight-bold">{{ $this->detailedPhase->pending_hq_approval_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Pending Approval</small>
                            </div>
                            @endif
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-danger font-weight-bold">{{ $this->detailedPhase->rejected_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Decli.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                <div class="card card-hover shadow-sm h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Amount Requested</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">KES {{ number_format($this->detailedPhase->total_requested ?? 0, 2) }}</div>

                        <hr class="my-2">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success font-weight-bold">{{ $this->detailedPhase->youth_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Youth.</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-pink font-weight-bold">{{ $this->detailedPhase->women_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Women</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-danger font-weight-bold">{{ $this->detailedPhase->pwd_count ?? 0 }}</span>
                                <small class="text-muted text-xs">PWD.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                <div class="card card-hover shadow-sm h-100 py-2">
                    <div class="card-body py-1">
                        <span class="d-block text-xs font-weight-bold text-primary text-uppercase mb-1">
                            @if(in_array($phase->status, ['draft', 'submitted_to_hq']))
                            Total Amount Proposed
                            @else
                            Total Amount Approved
                            @endif
                        </span>
                        <span class="h5 mb-1 font-weight-bold text-gray-800">KES {{ number_format($this->detailedPhase->total_approved ?? 0, 2) }}</span>

                        <hr class="my-2">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success font-weight-bold">{{ $this->detailedPhase->approved_youth_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Youth.</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-pink font-weight-bold">{{ $this->detailedPhase->approved_women_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Women</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-danger font-weight-bold">{{ $this->detailedPhase->approved_pwd_count ?? 0 }}</span>
                                <small class="text-muted text-xs">PWD.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                <div class="card card-hover shadow-sm h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Amount Disbursed</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">KES {{ number_format($this->detailedPhase->total_issued ?? 0, 2) }}</div>

                        <hr class="my-2">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success font-weight-bold">{{ $this->detailedPhase->disbursed_youth_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Youth.</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-pink font-weight-bold">{{ $this->detailedPhase->disbursed_women_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Women</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-danger font-weight-bold">{{ $this->detailedPhase->disbursed_pwd_count ?? 0 }}</span>
                                <small class="text-muted text-xs">PWD.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9 order-last order-md-first">
                <div class="card card-outline premium-summary-card shadow-sm border-0 mt-2">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                            <h3 class="card-title text-gray-900 text-capitalize">
                                <i class="fas fa-list text-primary mr-2"></i> Applications
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
                                        target="exportApplications"
                                        class="btn-primary"
                                        icon="fas fa-file-excel"
                                        label="Export Data"
                                        loadingLabel="..."
                                    /> --}}
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="card-body">
                        {{-- filters --}}
                        <div class="row mb-3">
                            <div class="col-sm-4 col-6">
                                <div class="form-group">
                                    <label class="small font-weight-bold text-uppercase text-muted">Application Status</label>
                                    <x-dropdown-search
                                        model="statusFilter"
                                        :options="$this->statusOptions"
                                        optionLabel="name"
                                        optionValue="value"
                                        placeholder="Select Status"
                                        icon="fas fa-clipboard-check"
                                    />
                                </div>
                            </div>
                            <div class="col-sm-3 col-6">
                                <label class="small font-weight-bold text-uppercase text-muted ml-1">Risk Level</label>
                                <x-dropdown-search
                                    model="riskFilter"
                                    :options="$this->riskOptions"
                                    optionLabel="name"
                                    optionValue="value"
                                    placeholder="Select Level"
                                    icon="far fa-surprise"
                                />
                            </div>

                            <div class="col-sm-3 col-6">
                                <label class="small font-weight-bold text-uppercase text-muted">Integrity</label>
                                <x-dropdown-search
                                    model="integrityFilter"
                                    :options="$this->integrityOptions"
                                    icon="fas fa-list-ol"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="select"
                                />
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

                        {{-- data table --}}
                        <div class="position-relative">
                            <div wire:loading.delay.shortest
                                wire:target="nextPage, previousPage, gotoPage, updatedSearch, updatedFilter, updatedRisk, sortBy, updatedIntegrityFilter"
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
                                    <small class="text-gray-500">Updating Data</small>
                                </div>
                            </div>
                            <div class="table-responsive p-0"
                                style="max-height: 550px; transition: opacity 0.3s ease;"
                                wire:key="{{$phase->slug}}-phase">
                                <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                                    <thead>
                                        <tr>
                                            <x-table-header
                                                field="applicationId"
                                                :sortField="$sortField"
                                                :sortDirection="$sortDirection"
                                            >
                                                Application Details
                                            </x-table-header>
                                            <x-table-header
                                                field="category"
                                                :sortField="$sortField"
                                                :sortDirection="$sortDirection"
                                            >
                                                Demographic
                                            </x-table-header>
                                            <x-table-header
                                                field="amount_approved"
                                                :sortField="$sortField"
                                                :sortDirection="$sortDirection"
                                            >
                                                Financials
                                            </x-table-header>
                                            <x-table-header
                                                field="status"
                                                :sortField="$sortField"
                                                :sortDirection="$sortDirection"
                                            >
                                                Status
                                            </x-table-header>
                                            <x-table-header :sortable="false">
                                                Action
                                            </x-table-header>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @forelse($this->applications as $app)
                                            <tr wire:key="app-row-{{ $app->id }}" class="animate__animated animate__fadeIn {{ ($app->is_duplicate || isset($app->legacy_debt_balance)) ? 'bg-light' : '' }} style="animation-delay: {{ $loop->index * 0.1 }}s;">
                                                <td class="px-4 py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            <span class="text-sm text-gray-900">{{ $app->tempApplication->group_name }}</span>
                                                            <div class="text-xs text-gray-600">
                                                                <span class="d-block font-mono">{{ $app->tempApplication->application_id }}</span>
                                                                <span>
                                                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $app->tempApplication?->ward->ward_name ?? 'Unallocated' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="d-flex flex-column">
                                                        <div class="text-sm text-gray-900">
                                                            <span>Category: {{ ucwords($app->tempApplication->nature_of_group) ?? 'N/A' }}</span>
                                                        </div>

                                                        <div class="text-xs text-gray-500">
                                                            <span>Sector: </span>

                                                            <span>
                                                                @if(is_array($app->tempApplication->business_type))
                                                                    {{ $app->tempApplication->business_type[0] ?? 'N/A' }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            @if($app->tempApplication->loan_type)
                                                                @if($app->tempApplication->loan_type === 'Wezesha')
                                                                    <span class="align-items-center bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg whitespace-nowrap">
                                                                        Wezesha
                                                                    </span>
                                                                @elseif ($app->tempApplication->loan_type === 'Endeleza')
                                                                    <span class="align-items-center bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg whitespace-nowrap">
                                                                        Endeleza - {{$app->loan_level}}
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="d-flex flex-column">
                                                        <div class="text-sm text-gray-900">
                                                            <span>Applied:</span>
                                                            <span class="whitespace-nowrap">KES {{ number_format($app->tempApplication->loan_amount ?? 0, 2) }}</span>
                                                        </div>
                                                        @if(in_array($app->status, $approvedStatuses))
                                                            <div class="text-xs text-gray-500">
                                                                <span class="whitespace-nowrap">Approved: KES {{ number_format($app->amount_approved ?? 0, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        @if($app->status === 'disbursed')
                                                            <div class="text-xs text-gray-500">
                                                                <span class="whitespace-nowrap">Issued: KES {{ number_format($app->amount_issued ?? 0, 2) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div>
                                                        Risk:
                                                        <span>
                                                            {{ $app->tempApplication?->risk_level ?? 'N/A' }}
                                                        </span>
                                                    </div>
                                                    <span class="{{ $this->getStatusClasses($app->status) }} border align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                        {{ $this->getStatusName($app->status) }}
                                                    </span>
                                                    <div>
                                                    @if($app->is_defaulter)
                                                    <span class="bg-gray-50 border align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                        Defaulter
                                                    </span>
                                                    @endif
                                                    @if($app->is_duplicate)
                                                    <span class="bg-gray-50 border align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                        Duplicate
                                                    </span>
                                                    @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <x-table-action-btn
                                                        type="view"
                                                        route="hq.submissions.app.view"
                                                        :params="[
                                                            'applicationId' => $app->tempApplication->application_id
                                                        ]"
                                                    />
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                    <h5 class="text-sm text-gray-500">No applications found</h5>
                                                    @if($search || $statusFilter || $riskFilter)
                                                        <x-loading-button
                                                            target="resetFilters"
                                                            class="btn-refresh-premium text-xs"
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
                            {{ $this->applications->links() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 order-first order-md-last d-flex flex-column" style="max-height: 115vh;">
                <div class="sidebar-upload-zone scrollable-sidebar-content flex-grow-1 pr-1 mt-2" style="overflow-y: auto; overflow-x: hidden; min-height: 0;">
                    <div class="card shadow-sm mb-4 border-0 overflow-hidden" style="border-radius: 12px;">
                        <div class="card-header bg-navy border-0 py-2">
                            <span class="text-xs font-weight-bold text-uppercase text-white">
                                <i class="fas fa-microchip mr-1"></i> Phase Analytics
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <div class="d-flex flex-column">
                                <div class="p-2 border-bottom d-flex align-items-center {{ $global_duplicate_count > 0 ? 'bg-success-subtle' : '' }}">
                                    <div class="rounded-circle bg-white shadow-sm p-2 mr-2 text-center" style="width: 35px; height: 35px;">
                                        <i class="fas fa-copy text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs font-weight-bold text-navy">Phase Conflicts</div>
                                        <div class="x-small {{ $global_duplicate_count > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                            {{ $global_duplicate_count }} {{ Str::plural('Group', $global_default_count) }} Found in other Batches
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2 border-bottom d-flex align-items-center {{ $global_default_count > 0 ? 'bg-danger-subtle' : '' }}">
                                    <div class="rounded-circle bg-white shadow-sm p-2 mr-2 text-center" style="width: 35px; height: 35px;">
                                        <i class="fas fa-hand-holding-usd text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs font-weight-bold text-navy">Legacy Debtors</div>
                                        <div class="x-small {{ $global_default_count > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                            {{ $global_default_count }} {{ Str::plural('Group', $global_default_count) }} with unpaid balances
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($this->canReturnToConstituency())
                        <div class="card shadow-sm mb-4 border-0 border-top border-warning animate__animated animate__fadeIn">
                            <div class="card-body">
                                <h6 class="text-sm font-weight-bold text-navy mb-1">Return for Amendment</h6>
                                <p class="text-xs text-gray-500 mb-3">Send this batch back to the constituency dashboard so the constituency officer can make corrections and resubmit.</p>
                                <div class="d-flex justify-content-center">
                                    <button wire:click="returnPhaseToConstituency"
                                            wire:loading.attr="disabled"
                                            wire:target="returnPhaseToConstituency"
                                            class="btn btn-outline-warning btn-sm shadow-sm">
                                        <span wire:loading.remove wire:target="returnPhaseToConstituency">
                                            <i class="fas fa-undo mr-2"></i> Return to Constituency
                                        </span>
                                        <span wire:loading wire:target="returnPhaseToConstituency">
                                            <i class="fas fa-spinner fa-spin mr-2"></i> Opening...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($this->canApproveApplication())
                        @if(!$this->detailedPhase->has_processed_code_list)
                            <div class="card shadow-sm mb-4 border-0 animate__animated animate__fadeIn">
                                <div class="card-header bg-amber-50 border-0 py-2">
                                    <span class="text-xs font-weight-bold text-uppercase text-navy">
                                        <i class="fas fa-file-excel mr-1 text-success"></i> Processed Code List Required
                                    </span>
                                </div>

                                <div class="card-body pt-1 pb-3">
                                    <span class="text-xs text-gray-500">Upload the processed code list as Excel (.xlsx). Accounts will review it together with the bank reconciliation submitted by the constituency when this phase was pushed to HQ.</span>


                                    <div class="form-group my-3 premium-file-container">
                                        <label class="small font-weight-bold text-uppercase text-muted mb-2">Processed Code List (Excel only)</label>
                                        <x-file-upload model="codeListFile" height="48px" accept=".xls,.xlsx" />
                                    </div>

                                    <div class="d-flex justify-content-center mt-2">
                                        <button wire:click="uploadCodeList"
                                                wire:loading.attr="disabled"
                                                wire:target="codeListFile, uploadCodeList"
                                                class="btn btn-refresh-premium btn-sm shadow-sm">
                                            <span wire:loading.remove wire:target="uploadCodeList">
                                                <i class="fas fa-cloud-upload-alt mr-2"></i> Upload processed code list
                                            </span>
                                            <span wire:loading wire:target="uploadCodeList">
                                                <i class="fas fa-spinner fa-spin mr-2"></i> Uploading...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                        @if($this->detailedPhase->accountant_review_status)
                            <div class="card shadow-sm mb-3 border-0">
                                <div class="card-body py-2">
                                    <small class="text-muted d-block">Accounts review</small>
                                    <strong>{{ \App\Support\AccountantReviewStatus::labels()[$this->detailedPhase->accountant_review_status] ?? $this->detailedPhase->accountant_review_status }}</strong>
                                    @if($this->detailedPhase->accountant_notes)
                                        <p class="text-xs text-gray-600 mb-0 mt-1">{{ $this->detailedPhase->accountant_notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($this->detailedPhase->accountant_review_status === \App\Support\AccountantReviewStatus::APPROVED && !$this->detailedPhase->has_management_proposal_minutes)
                            <div class="card shadow-sm mb-4 border-0">
                                <div class="card-header bg-light py-2">
                                    <span class="text-xs font-weight-bold text-uppercase">Management proposal review committee minutes</span>
                                </div>
                                <div class="card-body">
                                    <p class="text-xs text-gray-500">Mandatory before HQ batch approval. Upload the signed management committee minutes.</p>
                                    <x-file-upload model="managementMinutesFile" height="48px" accept=".pdf,.doc,.docx" />
                                    <div class="text-center mt-2">
                                        <button wire:click="uploadManagementMinutes" wire:loading.attr="disabled" wire:target="managementMinutesFile, uploadManagementMinutes" class="btn btn-sm btn-primary">
                                            Upload management minutes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0 animate__animated animate__fadeIn">
                            @if(in_array($this->detailedPhase->status, ['approved', 'in_training', 'disbursed']))
                                <div class="card-body">
                                    @php $phaseStatus = $this->phaseDisplayStatus(); @endphp
                                    <span class="{{ $phaseStatus['class'] }} border align-items-center d-block text-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                        {{ $phaseStatus['label'] }}
                                    </span>
                                    <p class="text-xs text-gray-500 mb-0">
                                        @if($phaseStatus['value'] === 'awaiting_secretariat')
                                            This batch is with the Head of Secretariat for training authorization. No further HQ actions are required until secretariat completes review or returns the phase.
                                        @else
                                            No further actions can be applied to this phase.
                                        @endif
                                    </p>
                                </div>
                            @else
                                <div class="card-body">
                                    <h6 class="text-sm font-weight-bold text-navy mb-1">Batch Approval</h6>
                                    @if($this->canApproveAfterAccountsReview())
                                        <p class="text-xs text-gray-500 mb-0">Accounts review and management minutes are in place. You may finalize this phase for Head of Secretariat.</p>
                                    @else
                                        <p class="text-xs text-warning mb-0">Waiting for accounts clearance and management proposal review committee minutes.</p>
                                    @endif

                                    <div class="d-flex justify-content-center mt-2">
                                        <button wire:click="approvePhase"
                                                wire:loading.attr="disabled"
                                                wire:target="approvePhase"
                                                class="btn btn-refresh-premium btn-sm shadow-sm"
                                                @disabled(!$this->canApproveAfterAccountsReview())>
                                            <span wire:loading.remove wire:target="approvePhase">
                                                <i class="fas fa-check mr-2"></i> Aprove Phase
                                            </span>
                                            <span wire:loading wire:target="approvePhase">
                                                <i class="fas fa-spinner fa-spin mr-2"></i> Processing...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endif

                        @if($this->detailedPhase->status !== 'disbursed')
                        <div class="card card-outline premium-summary-card shadow-sm border-0 mt-2">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <div class="d-flex flex-row justify-content-between align-items-center">
                                    <div>
                                        <h3 class="card-title mb-0">
                                            <span class="text-xs font-weight-bold text-uppercase text-navy tracking-wider">
                                                <i class="fas fa-plus text-primary mr-2"></i> Supporting Documents
                                            </span>
                                        </h3>
                                        <p class="text-gray-500 mb-0" style="font-size: 0.7rem;">Attach additional files</p>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <x-loading-button
                                            title="click to add"
                                            target="addUploadRow"
                                            class="btn-outline-primary shadow-sm font-weight-bold px-3"
                                            style="border-radius: 8px; border-width: 1.5px; transition: 0.2s;"
                                            size="sm" {{-- Upgraded from xs to sm for better visibility --}}
                                            icon="fas fa-plus-circle"
                                            label=""
                                            loadingLabel=""
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    @forelse($extraUploads as $index => $upload)
                                    <li class="list-group-item p-3 bg-light border-bottom">
                                        <button type="button"
                                            wire:click="removeUploadRow({{ $index }})"
                                            class="btn btn-link p-0 position-absolute"
                                            style="top: 0px; right: 5px; z-index: 10; opacity: 0.7; transition: opacity 0.2s;"
                                            onmouseover="this.style.opacity='1'"
                                            onmouseout="this.style.opacity='0.7'"
                                            title="Remove this row">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                        <div class="form-group mb-2">
                                            <div class="input-group premium-input-group transition-all"
                                                x-data="{ focused: false }"
                                                :class="{ 'premium-focus': focused }">

                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-white border-right-0 py-0"
                                                        style="border-radius: 10px 0 0 10px; height: 30px;">
                                                        <i class="fas fa-upload text-muted" :class="focused ? 'text-brand' : ''"></i>
                                                    </span>
                                                </div>

                                                <input type="text"
                                                    wire:model.live="extraUploads.{{ $index }}.title"
                                                    @focus="focused = true"
                                                    @blur="focused = false"
                                                    class="form-control border-left-0 shadow-none text-xs"
                                                    placeholder="Document Title (Transfer of funds)"
                                                    style="height: 30px; border-radius: 0 10px 10px 0;">
                                            </div>
                                            @error("extraUploads.$index.title")
                                                <small class="text-success d-block mt-2 x-small">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                </small>
                                            @enderror
                                        </div>
                                        <div class="form-group my-3 premium-file-container">
                                            <x-file-upload model="extraUploads.{{ $index }}.file" height="38px" />
                                        </div>
                                    </li>
                                    @empty
                                        <li class="list-group-item text-center py-4 text-muted small">
                                            <i class="fas fa-info-circle mr-1"></i> No documents staged. Click "Add icon" to start.
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                            @if(count($extraUploads) > 0)
                                <div class="card-footer bg-transparent border-top-0">
                                    <div class="d-flex justify-content-center">
                                        <button wire:click="uploadExtraDocuments"
                                                wire:loading.attr="disabled"
                                                wire:target="uploadExtraDocuments"
                                                class="btn btn-refresh-premium btn-xs shadow-sm">
                                            <span wire:loading.remove wire:target="uploadExtraDocuments">
                                                <i class="fas fa-cloud-upload-alt mr-2"></i> Upload {{ count($extraUploads) }} {{ Str::plural('file', count($extraUploads)) }}
                                            </span>
                                            <span wire:loading wire:target="uploadExtraDocuments">
                                                <i class="fas fa-spinner fa-spin mr-2"></i> Uploading...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endif
                    @endif

                    <div class="card card-outline premium-summary-card shadow-sm border-0 mt-2">
                        <div class="card-header bg-white border-bottom-0 py-3">
                            <h3 class="card-title mb-0">
                                <span class="text-xs font-weight-bold text-uppercase text-navy tracking-wider">
                                    <i class="fas fa-paperclip text-primary mr-2"></i> Phase Documents & Reports
                                </span>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="table-data" class="table table-sm mb-0" style="table-layout: fixed; width: 100%;">
                                    <tbody>
                                        @forelse($phase->phaseApplicationDocuments as $doc)
                                            <tr wire:key="app-row-{{ $doc->id }}" class="animate__animated animate__fadeIn">
                                                <td class="pl-3 py-2" style="width: 75%;"> {{-- Give the info side majority space --}}
                                                    <div class="d-flex align-items-start">
                                                        <div class="mr-2 text-center" style="flex: 0 0 30px;">
                                                            <i class="fas {{ $doc->icon }} fa-lg text-primary"></i>
                                                        </div>

                                                        <div style="min-width: 0; flex: 1;">
                                                            <div class="text-xs font-weight-bold text-dark text-truncate" title="{{ $doc->title }}">
                                                                {{ $doc->title }}
                                                            </div>
                                                            <div class="x-small text-muted text-truncate">
                                                                Uploaded {{ $doc->created_at->format('d M, H:i') }} • {{ $doc->formatted_size }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="text-right align-middle pr-3" style="width: 25%;">
                                                    <div class="btn-group">
                                                        @if($doc->can_view_inline)
                                                            <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'view']) }}"
                                                            target="_blank"
                                                            class="btn btn-xs btn-outline-info"
                                                            title="View in Browser">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endif

                                                        <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'download']) }}"
                                                        class="btn btn-xs btn-outline-primary"
                                                        title="Download File">
                                                            <i class="fas fa-download"></i>
                                                        </a>

                                                        @if($this->canDeleteDocument($doc))
                                                            <x-loading-button
                                                                target="deleteDocument('{{ $doc->id }}')"
                                                                wire:confirm="Permanent action: Delete this file?"
                                                                class="btn-outline-success btn-xs"
                                                                icon="fas fa-trash"
                                                                label=""
                                                                loadingLabel=""
                                                            />
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-muted">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block opacity-25"></i>
                                                    <span class="text-xs">No files uploaded yet.</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-light px-3 py-2 border-top border-bottom">
                                <small class="text-uppercase font-weight-bold text-muted" style="letter-spacing: 1px;">Generated Files</small>
                            </div>
                            <div class="table-responsive">
                                <table id="table-data" class="table table-sm mb-0" style="table-layout: fixed; width: 100%;">
                                    <tbody>
                                        @forelse($generatedReports as $report)
                                            <tr wire:key="report-row-{{ $report['id'] }}" class="animate__animated animate__fadeIn">
                                                <td class="pl-3 py-2" style="width: 75%;">
                                                    <div class="d-flex align-items-start">
                                                        <div style="min-width: 0; flex: 1;">
                                                            <div class="text-xs font-weight-bold text-dark text-truncate" title="{{ $report['name'] }}">
                                                                {{ $report['name'] }}
                                                            </div>
                                                            <div class="x-small text-muted text-truncate">
                                                                {{ $report['meta'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-right align-middle pr-3" style="width: 25%;">
                                                    <x-loading-button
                                                        target="exportReport('{{ $report['id'] }}')"
                                                        class="btn-outline-primary btn-sm"
                                                        icon="fas fa-file-excel"
                                                        label=""
                                                        loadingLabel=""
                                                    />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- confirm modal --}}
    <x-action-modal
            id="phase-approval-modal"
            title="Final Approval"
            type="info"
            icon="far fa-check-circle"
            size="modal-lg"
            x-on:open-modal.window="if($event.detail.name === 'phase-approval-modal') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'phase-approval-modal') $($el).modal('hide')"
        >
        <div class="alert alert-default-primary border-left-primary shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x mr-3 text-primary"></i>
                <div>
                    <strong>STERN WARNING:</strong><br>
                    <span>Once Approved, this phase is <strong>LOCKED</strong>. No further corrections are possible.</span>
                </div>
            </div>
        </div>

        <p class="mb-2 text-muted">Review the phase details below:</p>
        <h6 class="text-primary mb-3"><strong>{{ $this->phase->phase }}</strong></h6>

        <table class="table table-sm table-hover border mb-3">
            <tr>
                <td><strong>Total Approved Applications:</strong></td>
                <td class="text-right"><strong>{{ number_format($this->detailedPhase->approved_youth_count + $this->detailedPhase->approved_women_count + $this->detailedPhase->approved_pwd_count) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Applications With Pending Arrears:</strong></td>
                <td class="text-right"><strong>{{ number_format($global_default_count) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Duplicate Applications:</strong></td>
                <td class="text-right"><strong>{{ number_format($global_duplicate_count) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Amount Approved:</strong></td>
                <td class="text-right text-success"><strong>KES {{ number_format($this->detailedPhase->total_approved, 2) }}</strong></td>
            </tr>
            <tr class="bg-light">
                <td><strong>Submission Date:</strong></td>
                <td class="text-right">{{ now()->format('d-M-Y H:i') }}</td>
            </tr>
        </table>

        <div class="form-group">
            <label class="text-xs font-weight-bold text-uppercase">Final Remarks (Optional)</label>
            <textarea class="form-control" wire:model.live="remarks" rows="3" placeholder="Enter any final notes..."></textarea>
            @error('remarks') <small class="text-danger d-block mt-1 font-weight-bold">{{ $message }}</small> @enderror
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                {{-- Cancel Button --}}
                <x-pill-button
                    target="executeApproval"
                    label="Close"
                    variant="outline"
                    color="secondary"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    size="sm"
                    class="btn-refresh-premium shadow-sm mx-1"
                />

                {{-- Action Button --}}
                <x-pill-button
                    label="Confirm Approval"
                    target="executeApproval"
                    color="info"
                    icon="fas fa-send"
                    size="sm"
                    class="btn-refresh-premium shadow-sm mx-1"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
            id="phase-return-modal"
            title="Return Phase to Constituency"
            type="warning"
            icon="fas fa-undo"
            size="modal-lg"
            x-on:open-modal.window="if($event.detail.name === 'phase-return-modal') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'phase-return-modal') $($el).modal('hide')"
        >
        <div class="alert alert-warning border-left-warning shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
                <div>
                    <strong>Return for amendment:</strong><br>
                    <span>This will unlock the phase on the constituency dashboard. The constituency officer will be notified by email and SMS.</span>
                </div>
            </div>
        </div>

        <p class="mb-2 text-muted">Phase: <strong>{{ $this->phase->phase }}</strong></p>

        <div class="form-group">
            <label class="text-xs font-weight-bold text-uppercase">Reason for Return <span class="text-danger">*</span></label>
            <textarea class="form-control" wire:model.blur="returnRemarks" rows="4" placeholder="Explain what must be amended before resubmission..."></textarea>
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
                    label="Confirm Return"
                    target="executeReturnToConstituency"
                    color="warning"
                    icon="fas fa-undo"
                    size="sm"
                    class="shadow-sm mx-1"
                />
            </div>
        </x-slot>
    </x-action-modal>
</section>
