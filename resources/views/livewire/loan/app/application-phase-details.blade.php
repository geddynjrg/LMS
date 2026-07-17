<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-layer-group text-info mr-2"></i>
                            {{ ucwords($this->detailedPhase->phase) ?? 'Review Phase' }}
                        </h3>
                        <div class="mt-2 d-flex align-items-center">
                            @php $phaseStatus = $this->phaseDisplayStatus(); @endphp
                            <span class="{{ $phaseStatus['class'] }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">Status: {{ $phaseStatus['label'] }}</span>
                            <span class="mx-2 text-light">|</span>
                            <span class="text-muted small">
                                <i class="fas fa-calendar-alt mr-1"></i> Proposed Review:
                                <b class="text-dark">{{ $this->detailedPhase->review_date?->format('M d, Y') ?? 'Not set' }}</b>
                            </span>
                            <span class="mx-2 text-light">|</span>
                            <span class="text-muted small">
                                <i class="fas fa-map-marker-alt mr-1"></i> {{ $constituency->constituency_name }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0 d-flex flex-column align-items-md-end">
                        <span class="badge badge-pill badge-primary px-3 py-2 shadow-sm mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                        </span>

                        @if($this->canEditPhaseDetails($this->detailedPhase) || $this->canDeletePhase($this->detailedPhase))
                            <div x-data="{ open: false }" class="btn-group">
                                <button type="button"
                                        x-ref="phaseActionsTrigger"
                                        class="btn btn-sm btn-outline-secondary shadow-sm"
                                        @click="open = !open">
                                    <i class="fas fa-cog mr-1"></i> Phase Actions
                                </button>

                                <template x-teleport="body">
                                    <div x-show="open"
                                         x-anchor.bottom-end="$refs.phaseActionsTrigger"
                                         @click.away="open = false"
                                         x-cloak
                                         class="dropdown-menu show shadow-lg border-0"
                                         style="z-index: 9999; min-width: 200px; position: fixed;">
                                        @if($this->canEditPhaseDetails($this->detailedPhase))
                                            <button type="button"
                                                    class="dropdown-item py-2"
                                                    wire:click="openEditPhaseModal({{ $this->detailedPhase->id }})"
                                                    @click="open = false">
                                                <i class="fas fa-edit text-info mr-2"></i> Edit Phase Details
                                            </button>
                                        @endif

                                        @if($this->canEditPhaseDetails($this->detailedPhase) && $this->canDeletePhase($this->detailedPhase))
                                            <div class="dropdown-divider"></div>
                                        @endif

                                        @if($this->canDeletePhase($this->detailedPhase))
                                            <button type="button"
                                                    class="dropdown-item py-2 text-danger"
                                                    wire:click="deletePhase({{ $this->detailedPhase->id }})"
                                                    @click="open = false">
                                                <i class="fas fa-trash-alt mr-2"></i> Remove Phase
                                            </button>
                                        @endif
                                    </div>
                                </template>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                <div class="card card-hover shadow-sm border-left-success h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Applications</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">{{ number_format($detailedPhase->total_applications ?? 0) }}</div>

                        <hr class="my-2">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success font-weight-bold">{{ $detailedPhase->pending_submission_to_hq_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Apprv.</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            @if($this->detailedPhase->status === 'draft')
                            <div>
                                <span class="text-pink font-weight-bold">{{ $detailedPhase->pending_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Pending</small>
                            </div>
                            @else<div>
                                <span class="text-pink font-weight-bold">{{ $detailedPhase->pending_hq_approval_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Pending HQ</small>
                            </div>
                            @endif
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-danger font-weight-bold">{{ $detailedPhase->rejected_count ?? 0 }}</span>
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
                        <div class="h5 mb-1 font-weight-bold text-gray-800">KES {{ number_format($detailedPhase->total_requested ?? 0) }}</div>

                        <hr class="my-2">

                        <div class="row no-gutters align-items-center">
                            <div class="col">
                                <small class="d-block text-muted text-xs text-uppercase font-weight-bold">HQ Submission</small>
                                <span class="text-primary font-weight-bold">{{ $this->detailedPhase->pending_submission_to_hq_count ?? 0 }}</span>
                                <small class="text-xs text-gray-500">Awaiting Submission</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-export fa-2x text-gray-200"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                <div class="card card-hover shadow-sm h-100 py-2">
                    <div class="card-body py-1">
                        <span class="d-block text-xs font-weight-bold text-primary text-uppercase mb-1">
                            @if(in_array($this->detailedPhase->status, ['draft', 'submitted_to_hq']))
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
                                <i class="fas fa-list text-primary mr-2"></i>
                                @if($this->isHistoricalPhase)
                                    Groups
                                @else
                                    Applications
                                @endif
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
                                        placeholder="{{ $this->isHistoricalPhase ? 'Search groups...' : 'Search Applications...' }}"
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
                                    @if($phase->canAddApplications())
                                    <x-loading-button
                                        target="$dispatchTo('add-apps-to-phase-modal', 'open-add-apps-modal', {
                                                    phaseId: {{ $this->detailedPhase->id }},
                                                    constituency_code: '{{ $constituency->constituency_code }}'
                                                })"
                                        class="btn-info"
                                        icon="fas fa-plus"
                                        label=""
                                        loadingLabel="..."
                                        title="add new application"
                                    />
                                    @endif

                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-body">
                        @if($this->isHistoricalPhase)
                            @include('livewire.loan.app.partials.historical-phase-applications')
                        @else
                        {{-- filters --}}
                        <div class="row mb-3">
                            <div class="col-sm col-6">
                                <label class="small font-weight-bold text-muted ml-1">APPLICATION STATUS</label>
                                <x-dropdown-search
                                    model="statusFilter"
                                    :options="$this->statusOptions"
                                    optionLabel="name"
                                    optionValue="value"
                                    placeholder="select"
                                    icon="fas fa-shield-alt"
                                />
                            </div>

                            <div class="col-sm col-6">
                                <label class="small font-weight-bold text-uppercase text-muted ml-1">Risk Level</label>
                                <x-dropdown-search
                                    model="riskFilter"
                                    :options="$this->riskOptions"
                                    optionLabel="name"
                                    optionValue="value"
                                    placeholder="select"
                                    icon="far fa-surprise"
                                />
                            </div>

                            <div class="col-sm-3 col-6">
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
                                wire:target="nextPage, previousPage, gotoPage, updatedSearch, updatedFilter, updatedRisk, sortBy"
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
                                wire:key="{{$this->detailedPhase->slug}}-phase">
                                {{$riskFilter}}
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
                                            <tr wire:key="app-row-{{ $app->id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
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
                                                        @if($app->status === 'pending_disbursement' || ($app->status === 'in_training' && $app->is_trained))
                                                            Pending Disbursement
                                                        @elseif($app->status === 'in_training')
                                                            In Training
                                                        @else
                                                            {{ $this->getStatusName($app->status) }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="d-flex flex-column align-items-stretch gap-1" style="min-width: 180px;">
                                                        @if($this->isPendingAutoRejectionReview($app))
                                                            <div class="alert alert-warning py-2 px-2 mb-1 text-left">
                                                                <small class="d-block font-weight-bold">Missed training — auto-rejected</small>
                                                                <small class="d-block text-muted">{{ $app->rejection_message }}</small>
                                                                <div class="d-flex gap-1 mt-2">
                                                                    <button type="button"
                                                                            wire:click="reinstateAutoRejectedApplication({{ $app->id }})"
                                                                            wire:confirm="Reinstate this group to in-training status?"
                                                                            class="btn btn-success btn-sm flex-fill">
                                                                        Reinstate
                                                                    </button>
                                                                    <button type="button"
                                                                            wire:click="confirmAutoRejectedApplication({{ $app->id }})"
                                                                            wire:confirm="Confirm this auto-rejection?"
                                                                            class="btn btn-outline-danger btn-sm flex-fill">
                                                                        Confirm Rejection
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if($this->canScheduleGroupTraining($app))
                                                            <button type="button"
                                                                    wire:click="scheduleGroupTraining({{ $app->id }})"
                                                                    class="btn btn-primary btn-sm shadow-sm">
                                                                <i class="fas fa-calendar-alt mr-1"></i> Schedule Training
                                                            </button>
                                                        @endif

                                                        @if($app->status === 'in_training' && $app->training_start_date)
                                                            <small class="text-muted text-xs text-left px-1">
                                                                <strong>Training:</strong>
                                                                {{ $app->training_start_date->format('d M Y') }}
                                                                @if($app->training_end_date)
                                                                    – {{ $app->training_end_date->format('d M Y') }}
                                                                @endif
                                                                @if($app->training_venue)
                                                                    <br><strong>Venue:</strong> {{ $app->training_venue }}
                                                                @endif
                                                            </small>
                                                        @endif

                                                        @if($this->canMarkTrainingComplete($app))
                                                            <button type="button"
                                                                    wire:click="toggleAttendance({{ $app->id }})"
                                                                    class="btn btn-success btn-sm shadow-sm">
                                                                <i class="fas fa-graduation-cap mr-1"></i> Mark Training Complete
                                                            </button>
                                                        @endif

                                                        @if($notice = $this->trainingCertificateNoticeForApp($app))
                                                            <small class="text-muted text-xs text-left px-1">{{ $notice }}</small>
                                                        @endif

                                                        @if($this->canDownloadTrainingCertificate($app))
                                                            <a href="{{ route('training-certificate.download', $app) }}"
                                                               class="btn btn-outline-primary btn-sm shadow-sm">
                                                                <i class="fas fa-file-download mr-1"></i> Download Certificate
                                                            </a>
                                                        @endif

                                                        @if($this->canApproveDisbursement($app))
                                                            <button type="button"
                                                                    wire:click="toggleDisbursement({{ $app->id }})"
                                                                    class="btn btn-primary btn-sm shadow-sm">
                                                                <i class="fas fa-coins mr-1"></i> Mark Disbursed
                                                            </button>
                                                            <button type="button"
                                                                    wire:click="toggleNotDisbursed({{ $app->id }})"
                                                                    class="btn btn-outline-danger btn-sm shadow-sm">
                                                                <i class="fas fa-times-circle mr-1"></i> Not Disbursed
                                                            </button>
                                                        @endif

                                                        <div x-data="{ open: false }" class="btn-group mt-1">
                                                        <a href="{{ route('const.app.application-view', [$constituency->county->slug, $constituency->slug, 'applicationId' => $app->tempApplication->application_id]) }}"
                                                        class="btn btn-white btn-sm border shadow-sm">
                                                            <i class="fas fa-folder-open text-primary mr-1"></i> Manage
                                                        </a>

                                                        @if($app->tempApplication && $this->canDeletePhaseApplication($app))
                                                            <button type="button"
                                                                    class="btn btn-outline-danger btn-sm border shadow-sm ml-1"
                                                                    wire:click="confirmDeleteApplication({{ $app->tempApplication->id }})"
                                                                    title="Delete Application">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        @endif

                                                        @if(in_array($app->status, ['approved', 'in_training', 'pending_disbursement'], true))
                                                            <button type="button"
                                                                    x-ref="trigger"
                                                                    @click="open = !open"
                                                                    class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon">
                                                            </button>

                                                            <template x-teleport="body">
                                                                <div x-show="open"
                                                                    x-anchor.bottom-end="$refs.trigger"
                                                                    @click.away="open = false"
                                                                    x-cloak
                                                                    class="dropdown-menu show shadow-lg border-0 animate__animated animate__fadeIn animate__faster"
                                                                    style="z-index: 9999; min-width: 200px; position: fixed;">

                                                                    <h6 class="dropdown-header text-uppercase text-xs">Group Actions</h6>

                                                                    @if($this->canScheduleGroupTraining($app))
                                                                    <button class="dropdown-item" wire:click="scheduleGroupTraining({{ $app->id }})" @click="open = false">
                                                                        <i class="fas fa-calendar-alt text-primary mr-2"></i>
                                                                        Schedule Training
                                                                    </button>
                                                                    @endif

                                                                    @if($this->canMarkTrainingComplete($app))
                                                                    <button class="dropdown-item" wire:click="toggleAttendance({{ $app->id }})" @click="open = false">
                                                                        <i class="fas fa-graduation-cap text-success mr-2"></i>
                                                                        Mark Training Complete
                                                                    </button>
                                                                    @endif

                                                                    @if($this->canApproveDisbursement($app))
                                                                    <button class="dropdown-item" wire:click="toggleDisbursement({{ $app->id }})" @click="open = false">
                                                                        <i class="fas fa-coins text-primary mr-2"></i> Mark Disbursed
                                                                    </button>
                                                                    <button class="dropdown-item text-danger" wire:click="toggleNotDisbursed({{ $app->id }})" @click="open = false">
                                                                        <i class="fas fa-times-circle mr-2"></i> Not Disbursed
                                                                    </button>
                                                                    @endif

                                                                    <div class="dropdown-divider"></div>

                                                                    <button class="dropdown-item text-danger"
                                                                            wire:click="cancelApplication({{ $app->id }})"
                                                                            @click="open = false">
                                                                        <i class="fas fa-ban mr-2"></i> Cancel (Unreal Group)
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        @endif
                                                        </div>
                                                    </div>
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
                        <div class="card-footer clearfix bg-transparent">
                            <div class="pagination-sm">
                                {{ $this->applications->links() }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-3 order-first order-md-last d-flex flex-column" style="max-height: 115vh;">
                <div class="sidebar-upload-zone scrollable-sidebar-content flex-grow-1 pr-1" style="overflow-y: auto; overflow-x: hidden; min-height: 0;">
                    @if($this->canSubmitApplication())
                        @if($this->canUploadBankReconciliationMain())
                            @include('partials.application.bank-reconciliation-upload', ['detailedPhase' => $this->detailedPhase, 'reconciliationType' => 'main'])
                        @endif
                        @if($this->canUploadBankReconciliationLoanRepayment())
                            @include('partials.application.bank-reconciliation-upload', ['detailedPhase' => $this->detailedPhase, 'reconciliationType' => 'repayment'])
                        @endif

                        @if($this->detailedPhase->status === 'draft')
                            <div class="card shadow-sm mb-4 border-0">
                                <div class="card-header bg-amber-50 border-0 py-2">
                                    <span class="text-xs font-weight-bold text-uppercase text-navy">
                                        <i class="fas fa-clipboard-check mr-1"></i> Mandatory documents for HQ submission
                                    </span>
                                </div>
                                <div class="card-body py-2">
                                    <p class="text-xs text-gray-500 mb-2">
                                        All documents listed below are required before you can submit this phase to HQ.
                                    </p>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($this->mandatoryHqSubmissionDocuments as $mandatoryDoc)
                                            <li class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                                <span class="text-xs text-dark">{{ $mandatoryDoc['label'] }}</span>
                                                @if($mandatoryDoc['uploaded'])
                                                    <span class="badge badge-success">On file</span>
                                                @else
                                                    <span class="badge badge-danger">Required</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                        @if(!$this->detailedPhase->has_minutes)
                            <div class="card shadow-sm mb-4 border-0">
                                <div class="card-header bg-amber-50 border-0 py-2">
                                    <span class="text-xs font-weight-bold text-uppercase text-navy">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Missing Mandatory Document
                                    </span>
                                </div>
                                <div class="card-body pt-1 pb-3">
                                    <p class="text-xs text-gray-500">You must upload the signed committee minutes to finalize this batch.</p>

                                    <div class="form-group my-3 premium-file-container">
                                        <label class="small font-weight-bold text-uppercase text-muted mb-2">Signed Minutes (PDF/Word)</label>

                                        <div class="position-relative" style="height: 48px;">
                                            {{-- 1. THE REAL INPUT --}}
                                            @if(!$minutesFile)
                                                <input type="file"
                                                    wire:model="minutesFile"
                                                    id="minutesFile"
                                                    class="position-absolute w-100 h-100"
                                                    style="opacity: 0; z-index: 5; cursor: pointer; top: 0; left: 0;"
                                                    accept=".pdf, .doc, .docx">
                                            @endif

                                            {{-- 2. THE VISUAL PROXY (Note the 'proxy-box' class) --}}
                                            <div class="form-control d-flex align-items-center shadow-none proxy-box {{ $minutesFile ? 'border-success bg-success-subtle' : 'border-light-subtle' }}"
                                                style="height: 48px; border-radius: 12px; padding: 0 15px; position: absolute; top: 0; left: 0; width: 100%; z-index: 1; transition: all 0.2s ease-in-out;">

                                                @if($minutesFile)
                                                    @php
                                                        $ext = $minutesFile->getClientOriginalExtension();
                                                        $icon = match($ext) {
                                                            'pdf' => 'fa-file-pdf',
                                                            'doc', 'docx' => 'fa-file-word',
                                                            default => 'fa-file-alt'
                                                        };
                                                    @endphp
                                                    <i class="fas {{ $icon }} text-primary fa-lg mr-2"></i>
                                                    <div class="d-flex flex-column overflow-hidden">
                                                        <span class="text-gray-900 text-xs text-truncate" style="max-width: 180px;">
                                                            {{ $minutesFile->getClientOriginalName() }}
                                                        </span>
                                                        <small class="text-gray-500 x-small">Ready to upload</small>
                                                    </div>

                                                    <button type="button"
                                                            wire:click="$set('minutesFile', null)"
                                                            class="btn btn-sm cancel-btn ml-auto p-0 text-success"
                                                            style="z-index: 10;">
                                                        <i class="fas fa-times-circle fa-lg"></i>
                                                    </button>
                                                @else
                                                    <div class="d-flex align-items-center w-100" style="pointer-events: none;">
                                                        <i class="fas fa-cloud-upload-alt text-muted mr-3 fa-lg upload-icon"></i>
                                                        <span class="text-muted small">Click to attach document...</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div wire:loading wire:target="minutesFile" class="position-absolute" style="right: 15px; top: 14px; z-index: 6;">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                            </div>
                                        </div>
                                        @error('minutesFile')
                                            <small class="text-danger d-block mt-2" style="font-size: 0.75rem;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                            </small>
                                        @enderror
                                    </div>
                                    <div class="d-flex justify-content-center mt-4">
                                        <button wire:click="uploadMinutes"
                                                wire:loading.attr="disabled"
                                                wire:target="minutesFile, uploadMinutes"
                                                class="btn btn-refresh-premium btn-sm shadow-sm"
                                                style="min-width: 200px; border-radius: 10px; height: 40px;">
                                            <span wire:loading.remove wire:target="uploadMinutes">
                                                <i class="fas fa-cloud-upload-alt mr-2"></i> Upload & Unlock
                                            </span>
                                            <span wire:loading wire:target="uploadMinutes">
                                                <i class="fas fa-spinner fa-spin mr-2"></i> Uploading...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($this->canUploadRequestForTransferLetter())
                                <div class="card shadow-sm mb-4 border-0">
                                    <div class="card-header bg-amber-50 border-0 py-2">
                                        <span class="text-xs font-weight-bold text-uppercase text-navy">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Missing Mandatory Document
                                        </span>
                                    </div>
                                    <div class="card-body pt-1 pb-3">
                                        <p class="text-xs text-gray-500 mb-2">
                                            You must upload the request for transfer letter before submitting this phase to HQ. It will also be visible to accounts after upload.
                                        </p>
                                        <div class="form-group my-3 premium-file-container">
                                            <label class="small font-weight-bold text-uppercase text-muted mb-2">Request for Transfer Letter (PDF/Word)</label>
                                            <x-file-upload model="transferLetterFile" height="48px" accept=".pdf,.doc,.docx" />
                                            @error('transferLetterFile')
                                                <small class="text-danger d-block mt-2" style="font-size: 0.75rem;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                </small>
                                            @enderror
                                        </div>
                                        <div class="d-flex justify-content-center mt-2">
                                            <button wire:click="uploadRequestForTransferLetter"
                                                    wire:loading.attr="disabled"
                                                    wire:target="transferLetterFile, uploadRequestForTransferLetter"
                                                    class="btn btn-outline-info btn-sm shadow-sm"
                                                    style="min-width: 200px; border-radius: 10px; height: 40px;">
                                                <span wire:loading.remove wire:target="uploadRequestForTransferLetter">
                                                    <i class="fas fa-cloud-upload-alt mr-2"></i> Upload transfer letter
                                                </span>
                                                <span wire:loading wire:target="uploadRequestForTransferLetter">
                                                    <i class="fas fa-spinner fa-spin mr-2"></i> Uploading...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0 animate__animated animate__fadeIn">
                                @if ($this->hasAllMandatoryHqDocuments() && $detailedPhase->pending_count == 0)
                                    <div class="card-body">
                                        <p class="text-xs text-gray-600">By submitting, you confirm that committee minutes, both current bank reconciliations, and the request for transfer letter are uploaded and amounts match the Excel sheets.</p>

                                        <div class="d-flex justify-content-center mt-2">
                                            <x-modal-trigger
                                                target="submitToHq"
                                                class="shadow-sm btn-refresh-premium"
                                                icon="fas fa-paper-plane"
                                                size="lg"
                                                label="Submit to HQ"
                                            />
                                        </div>
                                    </div>
                                @else
                                    <div class="card-body">
                                        <p class="text-xs text-gray-600">To submit the phase to HQ, complete reviewing all applications, upload signed committee minutes, both current bank reconciliations (main account and loan repayment), the request for transfer letter, and ensure at least one application is approved.</p>
                                        @if($missing = $this->missingMandatoryHqDocuments())
                                            <p class="text-xs text-danger mb-0 mt-2">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                Still required: {{ implode(', ', $missing) }}.
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @elseif($this->detailedPhase->status === 'submitted_to_hq')
                            @if($this->canUploadRequestForTransferLetter())
                                <div class="card shadow-sm mb-4 border-0">
                                    <div class="card-header bg-amber-50 border-0 py-2">
                                        <span class="text-xs font-weight-bold text-uppercase text-navy">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Missing Mandatory Document
                                        </span>
                                    </div>
                                    <div class="card-body pt-1 pb-3">
                                        <p class="text-xs text-gray-500 mb-2">
                                            Upload the request for transfer letter. This is required for HQ submission and visible to accounts when uploaded.
                                        </p>
                                        <div class="form-group my-3 premium-file-container">
                                            <label class="small font-weight-bold text-uppercase text-muted mb-2">Request for Transfer Letter (PDF/Word)</label>
                                            <x-file-upload model="transferLetterFile" height="48px" accept=".pdf,.doc,.docx" />
                                            @error('transferLetterFile')
                                                <small class="text-danger d-block mt-2" style="font-size: 0.75rem;">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                </small>
                                            @enderror
                                        </div>
                                        <div class="d-flex justify-content-center mt-2">
                                            <button wire:click="uploadRequestForTransferLetter"
                                                    wire:loading.attr="disabled"
                                                    wire:target="transferLetterFile, uploadRequestForTransferLetter"
                                                    class="btn btn-outline-info btn-sm shadow-sm"
                                                    style="min-width: 200px; border-radius: 10px; height: 40px;">
                                                <span wire:loading.remove wire:target="uploadRequestForTransferLetter">
                                                    <i class="fas fa-cloud-upload-alt mr-2"></i> Upload transfer letter
                                                </span>
                                                <span wire:loading wire:target="uploadRequestForTransferLetter">
                                                    <i class="fas fa-spinner fa-spin mr-2"></i> Uploading...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @elseif($this->detailedPhase->status ==='approved')
                            <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0 animate__animated animate__fadeIn">
                                <div class="card-body">
                                    @if($this->canScheduleTraining())
                                        <p class="text-xs text-gray-600 mb-2">
                                            Secretariat has authorized training. Use <strong>Schedule Training</strong> in the
                                            <strong>Action</strong> column for each approved group. Different groups may have different dates and venues.
                                        </p>
                                    @else
                                        <p class="text-xs text-gray-600">HQ has approved this phase. Awaiting Head of Secretariat sign-off before training can be scheduled.</p>
                                    @endif

                                    @if($blockers = $this->trainingScheduleBlockers())
                                        <p class="text-xs text-warning mb-2">
                                            Head of Secretariat must complete
                                            {{ implode(', ', array_map(fn ($type) => str_replace('_', ' ', $type), $blockers)) }}
                                            before training can be scheduled.
                                        </p>
                                    @endif

                                    @if($this->canScheduleTraining())
                                        <div class="d-flex flex-column gap-2 justify-content-center mt-2">
                                            @if($this->approvedGroupsAwaitingTrainingCount() > 1)
                                                <x-modal-trigger
                                                    target="scheduleTraining"
                                                    class="shadow-sm btn btn-outline-primary btn-sm"
                                                    icon="fas fa-users"
                                                    size="lg"
                                                    label="Schedule all remaining groups (same dates)"
                                                />
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @elseif($this->showsTrainingDisbursementPanel())
                            <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0 animate__animated animate__fadeIn">
                                <div class="card-body">
                                    @if($this->detailedPhase->status === 'disbursed' && $this->detailedPhase->hasPendingTrainingOrDisbursement())
                                        <p class="text-xs text-warning mb-2">
                                            This phase was closed early. Complete training and disbursement for each group below,
                                            then close the phase again when all groups are processed.
                                        </p>
                                    @endif

                                    @if($this->approvedGroupsAwaitingTrainingCount() > 0)
                                        <p class="text-xs text-warning mb-2">
                                            <strong>{{ $this->approvedGroupsAwaitingTrainingCount() }}</strong>
                                            approved group(s) still need training scheduled. Use
                                            <strong>Schedule Training</strong> in the Action column for each group.
                                        </p>
                                        @if($this->canScheduleTraining() && $this->approvedGroupsAwaitingTrainingCount() > 1)
                                            <x-modal-trigger
                                                target="scheduleTraining"
                                                class="shadow-sm btn btn-outline-primary btn-sm mb-2"
                                                icon="fas fa-users"
                                                size="sm"
                                                label="Schedule all remaining groups (same dates)"
                                            />
                                        @endif
                                    @endif

                                    @if($this->detailedPhase->training_start_date)
                                        <p class="text-xs text-gray-600 mb-2">
                                            <strong>Scheduled training:</strong>
                                            {{ $this->detailedPhase->training_start_date->format('d M Y') }}
                                            @if($this->detailedPhase->training_end_date)
                                                – {{ $this->detailedPhase->training_end_date->format('d M Y') }}
                                            @endif
                                            @if($this->detailedPhase->training_venue)
                                                <br><strong>Venue:</strong> {{ $this->detailedPhase->training_venue }}
                                            @endif
                                        </p>
                                    @endif

                                    @if($notice = $this->trainingCertificateNotice())
                                        <p class="text-xs text-info mb-2">{{ $notice }}</p>
                                    @endif

                                    @if($this->pendingTrainingCompletionCount() > 0)
                                        <p class="text-xs text-gray-600 mb-2">
                                            <strong>{{ $this->pendingTrainingCompletionCount() }}</strong>
                                            group(s) still need training marked complete.
                                            Use the green <strong>Mark Training Complete</strong> button in the
                                            <strong>Action</strong> column on each group row below.
                                        </p>
                                    @else
                                        <p class="text-xs text-success mb-2">All groups in this phase have been marked as training complete.</p>
                                    @endif

                                    @if($this->pendingDisbursementCount() > 0)
                                        <p class="text-xs text-gray-600 mb-2">
                                            <strong>{{ $this->pendingDisbursementCount() }}</strong>
                                            group(s) are ready for disbursement. Use
                                            <strong>Mark Disbursed</strong> or <strong>Not Disbursed</strong> in the Action column.
                                            All group members receive an SMS notification.
                                        </p>
                                    @endif

                                    <p class="text-xs text-gray-600">Group members are notified by SMS when training is scheduled. Mark each group as training complete when they attend, or mark all groups at once. Certificates become downloadable four days after the scheduled training start date.</p>

                                    <div class="d-flex flex-column gap-2 mt-3">
                                        @if($this->canMarkAllTrainingComplete())
                                            <button type="button"
                                                    wire:click="openConfirmAllTrainingModal"
                                                    class="btn btn-success btn-sm shadow-sm rounded-pill py-2 font-weight-bold">
                                                <i class="fas fa-users mr-1"></i> Mark All Training Complete
                                            </button>
                                        @endif

                                        @if($this->canBatchDisbursePhase($this->detailedPhase))
                                            <button type="button"
                                                    wire:click="openBatchDisbursementModal"
                                                    class="btn btn-primary btn-sm shadow-sm rounded-pill py-2 font-weight-bold">
                                                <i class="fas fa-coins mr-1"></i> Disburse All Trained Groups
                                            </button>
                                        @endif

                                        @if($this->canFinalizePhase())
                                            <x-modal-trigger
                                                target="finalizePhase"
                                                class="shadow-sm btn-refresh-premium"
                                                icon="fas fa-window-close"
                                                size="lg"
                                                label="Close Phase"
                                            />
                                        @elseif($this->canMarkAllTrainingComplete() || $this->canBatchDisbursePhase($this->detailedPhase))
                                            <button type="button"
                                                    wire:click="finalizePhase"
                                                    class="btn btn-outline-primary btn-sm shadow-sm rounded-pill py-2 font-weight-bold">
                                                <i class="fas fa-forward mr-1"></i> Continue to Next Step
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0 animate__animated animate__fadeIn">
                                <div class="card-body">
                                    <span class="{{ $this->getStatusClasses($this->detailedPhase->status) }} border align-items-center d-block text-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                        {{ $this->getStatusName($this->detailedPhase->status) }}
                                    </span>
                                    <p class="text-xs text-gray-500 mb-0">No further actions can be applied to this phase at this stage.</p>
                                </div>
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
                            <div class="bg-light px-3 py-2 border-bottom">
                                <small class="text-uppercase font-weight-bold text-muted" style="letter-spacing: 1px;">Mandatory for HQ submission</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" style="table-layout: fixed; width: 100%;">
                                    <tbody>
                                        @foreach($this->mandatoryHqSubmissionDocuments as $mandatoryDoc)
                                            <tr wire:key="mandatory-doc-{{ $mandatoryDoc['type'] }}">
                                                <td class="pl-3 py-2" style="width: 75%;">
                                                    <div class="text-xs font-weight-bold text-dark">{{ $mandatoryDoc['label'] }}</div>
                                                    <div class="x-small text-muted">Required before submit to HQ</div>
                                                </td>
                                                <td class="text-right align-middle pr-3" style="width: 25%;">
                                                    @if($mandatoryDoc['uploaded'])
                                                        <span class="badge badge-success">On file</span>
                                                    @else
                                                        <span class="badge badge-danger">Missing</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-light px-3 py-2 border-top border-bottom">
                                <small class="text-uppercase font-weight-bold text-muted" style="letter-spacing: 1px;">Uploaded files</small>
                            </div>
                            <div class="table-responsive">
                                <table id="table-data" class="table table-sm mb-0" style="table-layout: fixed; width: 100%;">
                                    <tbody>
                                        @forelse($this->detailedPhase->phaseApplicationDocuments as $doc)
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
                                                                @if($doc->statement_period_label)
                                                                    • {{ $doc->statement_period_label }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="text-right align-middle pr-3" style="width: 25%;">
                                                    <div class="btn-group">
                                                        @if($doc->can_view_inline)
                                                            <a href="{{ route('phase.doc.preview', $doc->id) }}"
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
                            @if($this->showsApprovedCodeListDownload() || $this->canDownloadProcessedCodeList() || $this->canDownloadLetterOfAuthority())
                                <div class="bg-light px-3 py-2 border-top border-bottom">
                                    <small class="text-uppercase font-weight-bold text-muted" style="letter-spacing: 1px;">HQ &amp; Secretariat Documents</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="table-layout: fixed; width: 100%;">
                                        <tbody>
                                            @if($this->canDownloadProcessedCodeList())
                                                <tr>
                                                    <td class="pl-3 py-2" style="width: 75%;">
                                                        <div class="text-xs font-weight-bold text-dark">Processed Codelist (Excel)</div>
                                                        <div class="x-small text-muted">Same layout as HQ processed codelist, including financial summary</div>
                                                    </td>
                                                    <td class="text-right align-middle pr-3" style="width: 25%;">
                                                        <a href="{{ route('const.app.app_phase_processed_code_list.preview', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                                                           target="_blank"
                                                           class="btn btn-outline-info btn-sm mr-1"
                                                           title="Preview in browser">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('const.app.app_phase_processed_code_list', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                                                           class="btn btn-outline-success btn-sm"
                                                           title="Download processed codelist">
                                                            <i class="fas fa-file-excel"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                            @if($this->showsApprovedCodeListDownload())
                                                <tr>
                                                    <td class="pl-3 py-2" style="width: 75%;">
                                                        <div class="text-xs font-weight-bold text-dark">Approved Codelist (PDF)</div>
                                                        <div class="x-small text-muted">Signed PDF with Uwezo Fund logo and Head of Secretariat signature</div>
                                                    </td>
                                                    <td class="text-right align-middle pr-3" style="width: 25%;">
                                                        @if($this->canDownloadSignedCodeList())
                                                            <a href="{{ route('const.app.app_phase_signed_code_list', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                                                               class="btn btn-outline-danger btn-sm"
                                                               title="Download approved codelist">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                        @else
                                                            @php $approvedDoc = $this->detailedPhase->phaseApplicationDocuments->firstWhere('document_type', \App\Support\PhaseDocumentType::SIGNED_APPROVED_CODE_LIST); @endphp
                                                            @if($approvedDoc)
                                                                <a href="{{ route('phase.doc.serve', ['id' => $approvedDoc->id, 'action' => 'download']) }}"
                                                                   class="btn btn-outline-danger btn-sm"
                                                                   title="Download approved codelist">
                                                                    <i class="fas fa-file-pdf"></i>
                                                                </a>
                                                            @endif
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                            @if($this->canDownloadLetterOfAuthority())
                                                <tr>
                                                    <td class="pl-3 py-2" style="width: 75%;">
                                                        <div class="text-xs font-weight-bold text-dark">Letter of Authority to Disburse Funds (PDF)</div>
                                                        <div class="x-small text-muted">Available because approved amount exceeds constituency bank balance</div>
                                                    </td>
                                                    <td class="text-right align-middle pr-3" style="width: 25%;">
                                                        <a href="{{ route('const.app.app_phase_letter_of_authority', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                                                           class="btn btn-outline-danger btn-sm"
                                                           title="Download letter of authority to disburse funds">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- submit modal --}}
    <x-action-modal
            id="phase-submission-modal"
            title="Final Submission to HQ"
            type="info"
            icon="far fa-check-circle"
            size="modal-lg"
            x-on:open-modal.window="if($event.detail.name === 'phase-submission-modal') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'phase-submission-modal') $($el).modal('hide')"
        >
        <div class="alert alert-default-primary border-left-primary shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x mr-3 text-primary"></i>
                <div>
                    <strong>STERN WARNING:</strong><br>
                    <small>Once submitted, this phase is <strong>LOCKED</strong>. No further corrections are possible.</small>
                </div>
            </div>
        </div>

        <p class="mb-2 text-muted">Review the phase details below:</p>
        <h6 class="text-primary mb-3"><strong>{{ $this->phase->phase }}</strong></h6>

        <table class="table table-sm table-hover border mb-3">
            <tr>
                <td><strong>Total Applications:</strong></td>
                <td class="text-right"><strong>{{ number_format($this->detailedPhase->total_applications) }}</strong></td>
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

        <div class="form-group" wire:key="phase-submission-remarks">
            <label class="text-xs font-weight-bold text-uppercase">Final Committee Remarks (Optional)</label>
            <textarea class="form-control" wire:model.blur="remarks" rows="3" placeholder="Enter any final notes for HQ..."></textarea>
            @error('remarks') <small class="text-danger d-block mt-1 font-weight-bold">{{ $message }}</small> @enderror
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                {{-- Cancel Button --}}
                <x-pill-button
                    target="executeHqSubmission"
                    label="Close"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                {{-- Action Button --}}
                <x-pill-button
                    label="Confirm & Submit to HQ"
                    target="executeHqSubmission"
                    color="info"
                    icon="fas fa-send"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    {{-- training --}}
    <x-action-modal
        id="set-training-schedule-modal"
        title="Schedule Training for All Remaining Groups"
        type="success"
        icon="fas fa-calendar-alt"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'set-training-schedule-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'set-training-schedule-modal') $($el).modal('hide')"
    >
        <div class="alert alert-default-info shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x mr-3 text-primary"></i>
                <div>
                    <strong>Bulk schedule (optional)</strong><br>
                    <span class="text-xs">Apply the same dates and venue to every approved group that has not been scheduled yet. To set different dates per group, use <strong>Schedule Training</strong> on each row instead.</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label class="font-weight-bold text-gray-600 text-uppercase">Training Start Date</label>
                <div class="position-relative">
                    {{-- Slate Icon --}}
                    <div class="position-absolute d-flex align-items-center justify-content-center"
                        style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                        <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                    </div>

                    <input type="date"
                        wire:model.live="trainingStartDate"
                        min="{{ now()->format('Y-m-d') }}"
                        onclick="this.showPicker()"
                        class="form-control shadow-none border-light-subtle custom-date-input"
                        style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                </div>
                @error('trainingStartDate') <small class="text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-6">
                <label class="font-bold text-gray-600 text-uppercase">Training End Date</label>
                <div class="position-relative">
                    {{-- Slate Icon --}}
                    <div class="position-absolute d-flex align-items-center justify-content-center"
                        style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                        <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                    </div>

                    <input type="date"
                        wire:model.live="trainingEndDate"
                        min="{{ $trainingStartDate ?? now()->format('Y-m-d') }}"
                        onclick="this.showPicker()"
                        class="form-control shadow-none border-light-subtle custom-date-input"
                        style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                </div>
                @error('trainingEndDate') <small class="text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="form-group mt-3">
            <label class="text-uppercase font-bold text-gray-600 mb-2">Training Venue</label>
            <div class="input-group premium-input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-map-marker-alt text-info"></i></span>
                </div>
                <input type="text" wire:model="trainingVenue"
                    class="form-control"
                    placeholder="e.g., Constituency Office Hall">
            </div>
            @error('trainingVenue') <small class="text-success font-weight-bold">{{ $message }}</small> @enderror
        </div>

        <div class="form-group mt-3">
            <label class="text-uppercase font-bold text-gray-600 mb-2">Additional Instructions (Optional)</label>
            <textarea wire:model.live="trainingNotes" class="form-control" rows="2" placeholder="e.g. Groups should bring their original registration certificates..."></textarea>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    target="saveTrainingSchedule"
                    label="Close"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Schedule All & Notify Groups"
                    target="saveTrainingSchedule"
                    color="primary"
                    icon="fas fa-calendar-check"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="set-group-training-schedule-modal"
        title="Schedule Training for This Group"
        type="success"
        icon="fas fa-calendar-alt"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'set-group-training-schedule-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'set-group-training-schedule-modal') $($el).modal('hide')"
    >
        <div class="alert alert-default-info shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x mr-3 text-primary"></i>
                <div>
                    <strong>Schedule training for one group</strong><br>
                    <span class="text-xs">Set dates and venue for this group only. Members will be notified by SMS. Training certificates become downloadable four days after the scheduled start date.</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label class="font-weight-bold text-gray-600 text-uppercase">Training Start Date</label>
                <div class="position-relative">
                    <div class="position-absolute d-flex align-items-center justify-content-center"
                        style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                        <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                    </div>
                    <input type="date"
                        wire:model.live="trainingStartDate"
                        min="{{ now()->format('Y-m-d') }}"
                        onclick="this.showPicker()"
                        class="form-control shadow-none border-light-subtle custom-date-input"
                        style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                </div>
                @error('trainingStartDate') <small class="text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-6">
                <label class="font-bold text-gray-600 text-uppercase">Training End Date</label>
                <div class="position-relative">
                    <div class="position-absolute d-flex align-items-center justify-content-center"
                        style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                        <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                    </div>
                    <input type="date"
                        wire:model.live="trainingEndDate"
                        min="{{ $trainingStartDate ?? now()->format('Y-m-d') }}"
                        onclick="this.showPicker()"
                        class="form-control shadow-none border-light-subtle custom-date-input"
                        style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                </div>
                @error('trainingEndDate') <small class="text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="form-group mt-3">
            <label class="text-uppercase font-bold text-gray-600 mb-2">Training Venue</label>
            <div class="input-group premium-input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-map-marker-alt text-info"></i></span>
                </div>
                <input type="text" wire:model="trainingVenue"
                    class="form-control"
                    placeholder="e.g., Constituency Office Hall">
            </div>
            @error('trainingVenue') <small class="text-success font-weight-bold">{{ $message }}</small> @enderror
        </div>

        <div class="form-group mt-3">
            <label class="text-uppercase font-bold text-gray-600 mb-2">Additional Instructions (Optional)</label>
            <textarea wire:model.blur="trainingNotes" class="form-control" rows="2" placeholder="e.g. Groups should bring their original registration certificates..."></textarea>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    target="saveGroupTrainingSchedule"
                    label="Close"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Save Schedule & Notify Group"
                    target="saveGroupTrainingSchedule"
                    color="primary"
                    icon="fas fa-calendar-check"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="confirm-training-modal"
        title="Confirm Training Completion"
        type="success"
        icon="fas fa-graduation-cap"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'confirm-training-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'confirm-training-modal') $($el).modal('hide')"
    >
        <div class="text-center">
            <div class="mb-4 d-flex justify-content-center align-items-center">
                {{-- Current State --}}
                <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0;">
                    <span class="text-gray-500 text-xs d-block mb-1">Current Status</span>
                    <span class="text-primary font-bold h6 mb-0">Pending Training</span>
                </div>

                <i class="fas fa-arrow-right mx-3 text-info"></i>

                {{-- Destination State --}}
                <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 2px solid #b523a1;">
                    <span class="text-gray-500 text-xs d-block mb-1">New Status</span>
                    <span class="text-success font-weight-bold h6 mb-0">Trained & Verified</span>
                </div>
            </div>

            <p class="mb-1 text-dark font-weight-bold h5">Confirm Training Completion?</p>
            <p class="text-muted px-3">
                By confirming, you verify that the group members have successfully completed the
                <span class="text-primary font-weight-bold">Uwezo Fund Mandatory Training</span>.
                The application will move to pending disbursement. The training certificate will become
                downloadable four days after the scheduled training start date.
            </p>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Not Yet"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Confirm Trained"
                    target="confirmTraining"
                    color="primary"
                    icon="fas fa-check-double"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="approve-disbursement-modal"
        title="Final Disbursement Approval"
        type="primary"
        icon="fas fa-money-check-alt"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'approve-disbursement-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'approve-disbursement-modal') $($el).modal('hide')"
    >
        <div class="alert alert-default-info shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-invoice-dollar fa-2x mr-3 text-info"></i>
                <div>
                    <strong class="text-bold">Final Action: Fund Disbursement</strong><br>
                    <span class="text-gray-600">
                        Please record the final amount to issued to the group respective of the official cheque.
                        Once confirmed, this application will move to <strong>'Disbursed'</strong> status and the repayment schedule will be initialized.
                    </span>
                    <span class="d-block text-gray-600">
                        Amount Approved: <strong>KES {{ number_format($app_approved_amount ?? 0, 2)}}</strong>
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Actual Amount Issued</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-money-bill text-info"></i></span>
                    </div>
                    <input type="number"
                        required
                        wire:model.live="app_amount_issued"
                        class="form-control"
                        placeholder="0.00">
                </div>
                @error('app_amount_issued') <small class="d-block text-success font-bold">{{ $message }}</small> @enderror
                <span class="text-xs text-gray-500">Populated from Approved Amount: <strong>{{ number_format($app_approved_amount ?? 0) }}</strong></span>
            </div>

            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Cheque / Reference Number</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-money-check text-info"></i></span>
                    </div>
                    <input type="text"
                        required
                        wire:model.live="app_cheque_number"
                        class="form-control"
                        placeholder="">
                </div>
                @error('app_cheque_number') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Group Unique Identifier <span class="text-xs">(System Generated)</span></label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-id-badge text-info"></i></span>
                    </div>
                    <input type="text"
                        required
                        {{ $hasExistingIdentifier ? 'disabled' : '' }}
                        wire:model.live="app_group_identifier"
                        class="form-control"
                        placeholder="MYN001">
                </div>
                @error('app_group_identifier') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
                @if($hasExistingIdentifier)
                    <small class="text-xs text-gray-500"><i class="fas fa-info-circle mr-1"></i> This identifier is already locked for this group.</small>
                @else
                    <small class="text-xs text-gray-500">Format: [Initials][GroupCode]. e.g., <strong>UWF402</strong></small>
                @endif
            </div>
            @if($app_approved_amount != $app_amount_issued)
                <div class="form-group col-md-6">
                    <label class="text-uppercase font-bold text-gray-600 mb-2">Justification for the amount discrepancy(*)</label>
                    <textarea wire:model.live="app_disbursement_comment" class="form-control" rows="2" placeholder="State Reason for difference in amount issued "></textarea>
                    @error('app_disbursement_comment') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Cancel"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Confirm & Disburse"
                    target="processDisbursement"
                    color="primary"
                    icon="fas fa-paper-plane"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="confirm-all-training-modal"
        title="Mark All Groups Training Complete"
        type="success"
        icon="fas fa-users"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'confirm-all-training-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'confirm-all-training-modal') $($el).modal('hide')"
    >
        <p class="text-muted mb-0">
            Confirm that <strong>{{ $this->pendingTrainingCompletionCount() }}</strong>
            {{ Str::plural('group', $this->pendingTrainingCompletionCount()) }}
            successfully completed mandatory training. Each group will move to
            <strong>Pending Disbursement</strong> and members will be notified by SMS.
        </p>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Cancel"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Mark All Complete"
                    target="confirmAllTraining"
                    color="primary"
                    icon="fas fa-check-double"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="batch-disbursement-modal"
        title="Disburse All Trained Groups"
        type="primary"
        icon="fas fa-coins"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'batch-disbursement-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'batch-disbursement-modal') $($el).modal('hide')"
    >
        <div class="alert alert-default-info shadow-sm mb-4">
            <strong>Batch disbursement</strong><br>
            <span class="text-gray-600">
                This will mark <strong>{{ $this->pendingDisbursementCount() }}</strong>
                trained {{ Str::plural('group', $this->pendingDisbursementCount()) }} as disbursed at their approved amounts.
                Each group receives a cheque/reference number using the prefix below plus the group code.
                All members are notified by SMS.
            </span>
        </div>

        <div class="form-group">
            <label class="font-weight-bold text-gray-600">Cheque / Reference Prefix</label>
            <input type="text" wire:model.live="batchChequePrefix" class="form-control" placeholder="CHQ-20260612">
            @error('batchChequePrefix') <small class="text-danger d-block">{{ $message }}</small> @enderror
        </div>

        <div class="form-group mb-0">
            <label class="font-weight-bold text-gray-600">Comment (optional)</label>
            <textarea wire:model.live="batchDisbursementComment" class="form-control" rows="2" placeholder="Optional note for this disbursement batch"></textarea>
            @error('batchDisbursementComment') <small class="text-danger d-block">{{ $message }}</small> @enderror
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Cancel"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Disburse All Groups"
                    target="processBatchDisbursement"
                    color="primary"
                    icon="fas fa-paper-plane"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="not-disbursed-modal"
        title="Record: Not Disbursed"
        type="warning"
        icon="fas fa-times-circle"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'not-disbursed-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'not-disbursed-modal') $($el).modal('hide')"
    >
        <div class="alert alert-warning shadow-sm mb-4">
            <strong>Disbursement was not completed</strong><br>
            <span class="text-sm">The group trained successfully but did not receive funds (e.g. no cheque collection). This updates the loans portal after sync.</span>
        </div>
        <div class="form-group">
            <label class="font-weight-bold text-gray-600">Reason</label>
            <select wire:model.live="disbursementFailureReason" class="form-control">
                <option value="">Select reason...</option>
                @foreach($this->disbursementFailureReasonOptions() as $reason)
                    <option value="{{ $reason['id'] }}">{{ $reason['name'] }}</option>
                @endforeach
            </select>
            @error('disbursementFailureReason') <small class="text-danger d-block">{{ $message }}</small> @enderror
        </div>
        <div class="form-group mb-0">
            <label class="font-weight-bold text-gray-600">Details (required)</label>
            <textarea wire:model.live="disbursementFailureNotes" class="form-control" rows="3" placeholder="Explain why disbursement did not happen (min 20 characters)"></textarea>
            @error('disbursementFailureNotes') <small class="text-danger d-block">{{ $message }}</small> @enderror
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
                <x-pill-button label="Confirm Not Disbursed" target="processNotDisbursed" color="warning" icon="fas fa-check" class="mx-1 shadow-sm btn-refresh-premium" />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
            id="modal-reject-application"
            title="Reject Application"
            type="reject"
            icon="fa-user-times"
            size="modal-lg"
            x-on:open-modal.window="if($event.detail.name === 'modal-reject-application') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'modal-reject-application') $($el).modal('hide')"
        >
        <div class="alert alert-light border mb-4 py-3 px-3 shadow-sm" style="border-radius: 12px; background-color: #fff5f5; border-left: 4px solid #dc3545 !important;">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-lg mr-3 text-danger"></i>
                <div>
                    <strong class="text-danger d-block">Irreversible Action</strong>
                    <span class="text-xs text-gray-500">
                        Cancelling will move this group to the <strong>'Rejected'</strong> pool. This action requires a valid justification for HQ auditing.
                    </span>
                </div>
            </div>
        </div>
        <form wire:submit.prevent="confirmRejectApplication">

            {{-- Primary Reason (Searchable Dropdown) --}}
            <div class="form-group mb-4 text-left">
                <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">
                    Primary Reason <span class="text-danger">*</span>
                </label>

                <x-dropdown-search
                    model="selectedRejectionKey"
                    :options="$this->formattedRejectionReasons"
                    icon="fas fa-list-ul"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Search rejection reason..."
                />

                @error('selectedRejectionKey')
                    <span class="text-success small font-weight-bold mt-1 d-block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Detailed Explanation --}}
            <div class="form-group mb-0 text-left">
                <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">
                    Detailed Explanation <span class="text-danger">*</span>
                </label>
                <div class="input-group premium-input-group" style="border-radius: 16px !important; overflow: hidden; border: 1px solid #ced4da;">
                    <div class="input-group-prepend align-items-start pt-3 pl-3">
                        <span class="input-group-text bg-transparent border-0 p-0">
                            <i class="fas fa-comment-dots text-info"></i>
                        </span>
                    </div>
                    <textarea wire:model="rejectionComment"
                        class="form-control border-0 shadow-none py-3"
                        rows="4"
                        placeholder="Provide specific details (e.g. which documents are missing)..."
                        style="min-height: 120px; font-size: 0.95rem;"></textarea>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    @error('rejectionComment')
                        <span class="text-success small font-weight-bold d-block">{{ $message }}</span>
                    @enderror
                    <small class="text-muted ml-auto">Min. 20 characters</small>
                </div>
            </div>

            <x-slot name="footer">
                <x-pill-button label="Cancel" variant="info" color="dark" data-dismiss="modal" class="mx-1 shadow-sm btn-refresh-premium" />
                <x-pill-button
                    type="submit"
                    label="Confirm Rejection"
                    color="primary"
                    target="confirmRejectApplication"
                    icon="fas fa-check-circle"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </x-slot>
        </form>
    </x-action-modal>

    <x-action-modal
        id="confirm-close-modal"
        title="Confirm Phase Completion"
        type="success"
        icon="fas fa-check"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'confirm-close-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'confirm-close-modal') $($el).modal('hide')"
     >
        <div class="text-center">
            <div class="mb-4 d-flex justify-content-center align-items-center">
                {{-- Current State --}}
                <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0;">
                    <span class="text-gray-500 text-xs d-block mb-1">Current Status</span>
                    <span class="text-primary font-bold h6 mb-0">{{$this->detailedPhase->status}}</span>
                </div>

                <i class="fas fa-arrow-right mx-3 text-info"></i>

                {{-- Destination State --}}
                <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 2px solid #b523a1;">
                    <span class="text-gray-500 text-xs d-block mb-1">New Status</span>
                    <span class="text-success font-weight-bold h6 mb-0">Disbursed & Closed</span>
                </div>
            </div>

            <p class="mb-1 text-primary font-weight-bold h5">Finalize & Close Phase?</p>
            <p class="text-muted px-3">
                By confirming, you lock this phase after all groups have been marked as
                <strong>disbursed</strong>, <strong>not disbursed</strong>, or <strong>rejected</strong>.
                Once closed, disbursement details cannot be changed.
            </p>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Not Yet"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Close Phase"
                    target="confirmFinalizePhase"
                    color="primary"
                    icon="fas fa-check-double"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    @include('livewire.loan.app.partials.phase-edit-modal')

    <x-action-modal
        id="confirm-deletePhase-modal"
        title="Confirm Phase Removal"
        type="danger"
        icon="fas fa-trash-alt"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'confirm-deletePhase-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'confirm-deletePhase-modal') $($el).modal('hide')"
    >
        <div class="text-center px-3">
            @if($deletingPhaseName)
                <p class="mb-3">
                    You are about to permanently remove <strong>{{ $deletingPhaseName }}</strong>.
                </p>
            @endif

            <div class="alert alert-warning text-left mb-0" role="alert">
                <p class="mb-2 font-weight-bold">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    SQL backup and audit trail
                </p>
                <p class="mb-2 small">
                    A SQL backup is saved automatically before deletion. Linked groups, members, and applications in this phase may also be removed during the grace period.
                </p>
                @if($deletingPhaseGroupCount > 0)
                    <p class="mb-0 small">
                        This phase has <strong>{{ $deletingPhaseGroupCount }}</strong> linked group(s)
                        @if($deletingPhaseMemberCount > 0)
                            and <strong>{{ $deletingPhaseMemberCount }}</strong> member record(s)
                        @endif.
                    </p>
                @endif
            </div>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button label="Cancel" variant="outline" color="secondary" data-dismiss="modal" icon="fas fa-times" class="mx-1" />
                <x-pill-button label="Confirm Delete" target="confirmDeletePhase" color="danger" icon="fas fa-trash-alt" class="mx-1" />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="modal-delete-application"
        title="Delete Application"
        type="danger"
        icon="fas fa-trash-alt"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'modal-delete-application') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'modal-delete-application') $($el).modal('hide')"
    >
        <div class="text-center px-3">
            @if($deletingApplicationLabel)
                <p class="mb-3">
                    You are about to permanently delete <strong>{{ $deletingApplicationLabel }}</strong>.
                </p>
            @endif

            <div class="alert alert-warning text-left mb-0" role="alert">
                <p class="mb-2 font-weight-bold">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    This action cannot be undone from this screen
                </p>
                <p class="mb-0 small">
                    A SQL backup is saved automatically and the action is logged in the county audit trail.
                    @if($gracePeriodActive)
                        Extended delete access is active until <strong>{{ $gracePeriodEndsAt }}</strong>.
                    @endif
                </p>
            </div>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button label="Cancel" variant="outline" color="secondary" data-dismiss="modal" icon="fas fa-times" class="mx-1" />
                <x-pill-button label="Confirm Delete" target="deleteApplication" color="danger" icon="fas fa-trash-alt" class="mx-1" />
            </div>
        </x-slot>
    </x-action-modal>
</section>
@push('styles')
    <style>
        /* Prevent the responsive wrapper from clipping the dropdown */

    </style>
@endpush
