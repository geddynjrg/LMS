<section class="content">
    <div class="container-fluid">

        <div class="row pt-3 mb-4">
            <div class="col-md-6">
                <h3 class="font-weight-bold">Phase Management</h3>
                <p class="text-muted"><i class="fas fa-map-marker-alt"></i> {{ $constituency->constituency_name }} Constituency | Loan Phases Control</p>
            </div>
            <div class="col-md-6 text-right">
                <x-modal-trigger
                    class="shadow-sm"
                    target="createNewPhase"
                    label="Initiate New Phase"
                    icon="fas fa-plus-circle"
                    variant="primary"
                    size=""
                />
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-layer-group"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Phases</span>
                        <span class="info-box-number h5">{{ $summary['total_phases'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Submission</span>
                        <span class="info-box-number h5">{{ $pendingApplications }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-paper-plane"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Processing at HQ</span>
                        <span class="info-box-number h5">{{ $processingPhases }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Approved (Action)</span>
                        <span class="info-box-number h5">{{ $approved }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-navy card-tabs shadow">
            <div class="card-header border-0 py-3">
                <h3 class="card-title font-weight-bold">Batch Review Cycles</h3>
                <div class="card-tools mb-3">
                    <div class="align-items-center d-flex flex-sm-row-reverse flex-wrap" style="gap: 10px;">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text"
                                wire:model.live.debounce.200ms="search"
                                class="form-control float-right"
                                placeholder="Search phase name or number...">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                            </div>
                        </div>

                        <div class="btn-group">
                            <x-loading-button
                                target="refreshData"
                                class="btn-default"
                                icon="fas fa-sync-alt"
                            />

                            <x-loading-button
                                target="resetFilters"
                                class="btn-default"
                                icon="fas fa-filter"
                            />
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>
                <ul class="nav nav-tabs px-3" id="phaseTaskTabs" role="tablist">
                    <li class="nav-item {{ $activeTab === 'tab-pending' ? 'border border-bottom-0':'' }}">
                        <button class="nav-link {{ $activeTab === 'tab-pending' ? 'active font-weight-bold border border-bottom-0 border-success' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-pending')" type="button">
                            <i class="fas fa-edit mr-1 {{ $activeTab === 'tab-pending' ? 'text-warning' : '' }}"></i>
                            Pending Submission
                            <span class="badge {{ 1 > 0 ? 'badge-warning' : 'badge-light border' }} ml-1">
                                {{ $pendingApplications }}
                            </span>
                        </button>
                    </li>

                    <li class="nav-item {{ $activeTab === 'tab-processing' ? 'border border-bottom-0':'' }}">
                        <button class="nav-link {{ $activeTab === 'tab-processing' ? 'active font-weight-bold border-bottom-0 border-success' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-processing')" type="button">
                            <i class="fas fa-spinner fa-spin-hover mr-1 {{ $activeTab === 'tab-processing' ? 'text-info' : '' }}"></i>
                            Processing (HQ)
                            <span class="badge {{ 1 > 0 ? 'badge-info' : 'badge-light border' }} ml-1">
                                {{ $processingPhases }}
                            </span>
                        </button>
                    </li>

                    <li class="nav-item {{ $activeTab === 'tab-approved' ? 'border border-bottom-0':'' }}">
                        <button class="nav-link {{ $activeTab === 'tab-approved' ? 'active font-weight-bold border-bottom-0 border-success' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-approved')" type="button">
                            <i class="fas fa-check-double mr-1 {{ $activeTab === 'tab-approved' ? 'text-success' : '' }}"></i>
                            Approved (Action)
                            <span class="badge {{ 1 > 0 ? 'badge-success' : 'badge-light border' }} ml-1 pulsate-badge">
                                {{ $approved }}
                            </span>
                        </button>
                    </li>

                    <li class="nav-item {{ $activeTab === 'tab-declined' ? 'border border-bottom-0':'' }}">
                        <button class="nav-link {{ $activeTab === 'tab-declined' ? 'active font-weight-bold border-bottom-0 border-success' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-declined')" type="button">
                            <i class="fas fa-ban mr-1 {{ $activeTab === 'tab-declined' ? 'text-danger' : '' }}"></i>
                            Declined
                            <span class="badge badge-light border ml-1">{{ $declined }}</span>
                        </button>
                    </li>

                    <li class="nav-item {{ $activeTab === 'tab-disbursed' ? 'border border-bottom-0':'' }}">
                        <button class="nav-link {{ $activeTab === 'tab-disbursed' ? 'active font-weight-bold border-bottom-0 border-success' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-disbursed')" type="button">
                            <i class="fas fa-hand-holding-usd mr-1 {{ $activeTab === 'tab-disbursed' ? 'text-navy' : '' }}"></i>
                            Disbursed
                            <span class="badge badge-navy ml-1">{{ $disbursed }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body position-relative">
                <div wire:loading.delay.shortest wire:target="activeTab, nextPage, previousPage, gotoPage, updatedSearch, refreshData"
                    wire:key="loading-overlay-container"
                    class="w-100 h-100 position-absolute justify-content-center align-items-center"
                    style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 100px;">
                    <div class="text-center">
                        <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                        <p class="text-navy font-weight-bold mt-2">Updating Tasks View...</p>
                    </div>
                </div>
                <div class="tab-content" wire:key="tab-content-wrapper">
                    @if($activeTab === 'tab-pending')
                    <div class="tab-pane fade show active" id="tab-pending" wire:key="content-pending">
                        <div class="table-responsive p-0" style="max-height: 450px;">
                            <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                                <thead class="bg-light text-uppercase font-weight-bold">
                                    <tr class="text-lightblue text-md-left">
                                        <th class="pl-4">Phase Details</th>
                                        <th>Vetting Progress</th>
                                        <th>Total Applied</th>
                                        <th>Total Approved</th>
                                        <th>Review Date</th>
                                        <th class="text-right pr-4">Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($phases as $phase)
                                    {{-- route('const.app.app_phase_details', [$constituency->county->slug, $constituency->slug]) --}}
                                    <tr wire:key="phase-row-{{ $phase->id }}-{{ $activeTab }}">

                                        <td class="pl-4">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <span class="d-block font-weight-bold">{{ ucwords($phase->phase) }}</span>
                                                    @if ($phase->total_apps > 0)
                                                        <small class="text-muted font-weight-bold">Applications: {{ $phase->total_apps }}</small><br>
                                                        @if ($phase->pending_apps > 0)
                                                            <small class="text-muted text-cyan text-wrap">{{ $phase->pending_apps }} Applications pending Vetting</small>
                                                        @else
                                                            <small class="text-muted text-blue">Awaiting submission</small>
                                                        @endif
                                                    @else
                                                        <small class="text-muted text-red">No applications assigned</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between align-items-end mb-1">
                                                <small class="text-muted " style="font-size: 0.65rem; letter-spacing: 0.5px;">Completion</small>
                                                <span class="badge {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) == 100 ? 'badge-success' : 'badge-primary' }} shadow-sm">
                                                    {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) }}%
                                                </span>
                                            </div>
                                            <div class="progress progress-xxs shadow-sm">
                                                <div class="progress-bar bg-{{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) == 100 ? 'success' : 'primary' }}"
                                                    role="progressbar"
                                                    style="width: {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) }}%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark font-weight-bold">KES {{ number_format($phase->total_requested) }}</span><br>
                                            <small class="text-muted">Total Applied</small>
                                        </td>
                                        <td>
                                            <span class="text-dark font-weight-bold">KES {{ number_format($phase->total_approved) }}</span><br>
                                            <small class="text-muted">Total Approved</small>
                                        </td>
                                        <td>
                                            <div class="text-dark"><i class="far fa-calendar-alt mr-1"></i> {{ $phase->review_date?->format('M d, Y') ?? 'Not Scheduled' }}</div>
                                            <small class="text-{{ $phase->review_date?->isPast() ? 'danger' : 'muted' }}">
                                                {{ $phase->review_date?->diffForHumans() ?? '' }}
                                            </small>
                                        </td>
                                        <td class="text-right pr-4">
                                            <div class="btn-group">
                                                <a href="{{ route('const.app.app_phase_details', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}" class="btn btn-white btn-sm border shadow-sm px-3" title="View Batch Details" wire:navigate>
                                                    <i class="fas fa-folder-open text-primary mr-1"></i> Manage
                                                </a>

                                                <button type="button" class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon" data-toggle="dropdown" data-boundary="viewport">
                                                    <span class="sr-only">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                                    @if($phase->canAddApplications())
                                                        <x-modal-trigger
                                                            class="dropdown-item py-2"
                                                            target="$dispatchTo('add-apps-to-phase-modal', 'open-add-apps-modal', {
                                                                phaseId: {{ $phase->id }},
                                                                constituency_code: '{{ $constituency->constituency_code }}'
                                                            })"
                                                            label="Attach Applications"
                                                            icon="fas fa-plus text-warning"
                                                            variant=""
                                                            size="sm"
                                                        />

                                                    @endif

                                                    @if($phase->status === 'draft')
                                                        <button type="button" class="dropdown-item py-2 text-success font-weight-bold" wire:click="submitToHq({{ $phase->id }})">
                                                            <i class="fas fa-paper-plane mr-2"></i> Submit to HQ
                                                        </button>
                                                    @endif

                                                    <div class="dropdown-divider"></div>

                                                    <x-modal-trigger
                                                        class="dropdown-item py-2 text-danger"
                                                        target="deletePhase({{ $phase->id }})"
                                                        label="Remove Phase"
                                                        icon="fas fa-trash-alt"
                                                        variant=""
                                                        size="sm"
                                                    />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr wire:key="empty-state-{{ $activeTab }}">
                                            <td colspan="6" class="text-center py-5">
                                                @if(filled($search))
                                                    <img src="{{ asset('dist/img/empty-folder.svg') }}" style="width: 150px;" class="mb-3 opacity-50">
                                                    <h5 class="text-muted">No pending phases detected for "{{ $search }}".</h5>
                                                    <x-modal-trigger
                                                        class="px-4 mr-1"
                                                        target="resetFilters"
                                                        label="Clear Filters"
                                                        icon="fas fa-eraser"
                                                        variant="primary"
                                                        size="sm"
                                                    />
                                                @else
                                                    <img src="{{ asset('dist/img/empty-folder.svg') }}" style="width: 150px;" class="mb-3 opacity-50">
                                                    <h5 class="text-muted">No review phases detected.</h5>
                                                    <p class="text-muted px-5">Groups cannot be approved until they are assigned to a specific review phase.</p>
                                                    <x-modal-trigger
                                                        class="px-4 mr-1"
                                                        target="createNewPhase"
                                                        label="Create A New Phase"
                                                        icon="fas fa-plus"
                                                        variant="primary"
                                                        size="sm"
                                                    />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($activeTab === 'tab-processing')
                        <div class="tab-pane fade show active" id="tab-processing" wire:key="content-processing">
                            <div class="table-responsive p-0" style="max-height: 450px;">
                                <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                                    <thead class="bg-light text-uppercase font-weight-bold">
                                        <tr class="text-lightblue text-md-left">
                                            <th class="pl-4">Phase Details</th>
                                            <th>HQ Progress</th> <th>Submission Info</th> <th>Current Queue</th> <th>Priority</th>
                                            <th class="text-right pr-4">Operations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($phases as $phase)
                                        <tr wire:key="phase-row-{{ $phase->id }}-{{ $activeTab }}">
                                            <td class="pl-4">
                                                <span class="d-block font-weight-bold">{{ $phase->phase }}</span>
                                                <small class="badge badge-info"><i class="fas fa-university mr-1"></i> Under HQ Review</small>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="font-weight-bold">{{ $phase->total_apps }} Apps</small>
                                                    <small class="text-muted">{{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) }}% Verified</small>
                                                </div>
                                                <div class="progress progress-xxs shadow-sm">
                                                    <div class="progress-bar bg-info" style="width: {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) }}%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-dark">{{ $phase->submitted_by_name ?? 'Constituency Admin' }}</span><br>
                                                <small class="text-muted"><i class="far fa-clock mr-1"></i> {{ $phase->submitted_at?->format('M d, Y') }}</small>
                                            </td>
                                            <td>
                                                <div class="text-dark">Verification Office</div>
                                                <small class="text-orange font-weight-bold">
                                                    <i class="fas fa-hourglass-half mr-1"></i> Pending for {{ $phase->submitted_at?->diffInDays() }} days
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge border text-{{ $phase->total_requested > 1000000 ? 'danger' : 'muted' }}">
                                                    {{ $phase->total_requested > 1000000 ? 'High Value' : 'Standard' }}
                                                </span>
                                            </td>
                                            <td class="text-right pr-4">
                                                <div class="btn-group">
                                                    <a href="#" class="btn btn-white btn-sm border shadow-sm px-3">
                                                        <i class="fas fa-eye text-info mr-1"></i> Track Progress
                                                    </a>
                                                    <button type="button" class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon" data-toggle="dropdown"></button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="#"><i class="fas fa-file-pdf mr-2 text-danger"></i> Export Submission Manifest</a>
                                                        <a class="dropdown-item" href="#"><i class="fas fa-history mr-2"></i> View Audit Trail</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr wire:key="empty-state-{{ $activeTab }}">
                                            <td colspan="6" class="text-center py-5">
                                                @if($search)
                                                    <i class="fas fa-search fa-3x text-light mb-3"></i>
                                                    <h5 class="text-muted">No results for "{{ $search }}"</h5>
                                                    <button wire:click="resetFilters" class="btn btn-link btn-sm">Clear Search</button>
                                                @else
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="fas fa-microchip fa-4x text-light mb-3"></i>
                                                        <h5 class="text-muted font-weight-bold">No Phases in Processing</h5>
                                                        <p class="text-muted text-sm px-5">
                                                            When you submit a review phase from the <b>Pending</b> tab, it will appear here for HQ verification and final approval.
                                                        </p>
                                                        <button wire:click="$set('activeTab', 'tab-pending')" class="btn btn-outline-primary btn-sm mt-2">
                                                            <i class="fas fa-arrow-left mr-1"></i> View Pending Phases
                                                        </button>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($activeTab === 'tab-approved')
                        <div class="tab-pane fade show active" id="tab-approved" wire:key="content-approved">
                            <div class="table-responsive p-0" style="max-height: 450px;">
                                <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                                    <thead class="bg-light text-uppercase font-weight-bold">
                                        <tr class="text-success text-md-left">
                                            <th class="pl-4">Approved Phase</th>
                                            <th>App Count</th>
                                            <th>Financials (Applied vs Approved)</th>
                                            <th>Approval Date</th>
                                            <th class="text-right pr-4">Operations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($phases as $phase)
                                        <tr wire:key="phase-row-{{ $phase->id }}-{{ $activeTab }}">
                                            <td class="pl-4">
                                                <span class="d-block font-weight-bold">{{ $phase->phase }}</span>
                                                <small class="badge badge-success text-xs">
                                                    <i class="fas fa-check-circle mr-1"></i> Fully Vetted & Approved
                                                </small>
                                            </td>
                                            <td>
                                                <div class="text-dark font-weight-bold">{{ $phase->approved_apps }} / {{ $phase->total_apps }}</div>
                                                <small class="text-muted">Groups Successful</small>
                                            </td>
                                            <td>
                                                <div class="text-xs text-muted">Applied: KES {{ number_format($phase->total_requested) }}</div>
                                                <div class="text-dark font-weight-bold">Approved: KES {{ number_format($phase->total_approved) }}</div>

                                                @php
                                                    $variance = $phase->total_requested > 0 ? ($phase->total_approved / $phase->total_requested) * 100 : 0;
                                                @endphp
                                                <div class="progress progress-xxs mt-1" style="width: 100px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $variance }}%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-dark"><i class="fas fa-calendar-check mr-1"></i> {{ $phase->updated_at->format('M d, Y') }}</div>
                                                <small class="text-muted">Finalized by HQ</small>
                                            </td>
                                            <td class="text-right pr-4">
                                                <div class="btn-group">
                                                    <button class="btn btn-white btn-sm border shadow-sm px-3" title="Print Approval Letter">
                                                        <i class="fas fa-print text-dark mr-1"></i> Print
                                                    </button>
                                                    <button type="button" class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon" data-toggle="dropdown"></button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="#"><i class="fas fa-file-excel mr-2 text-success"></i> Export Beneficiary List</a>
                                                        <a class="dropdown-item" href="#"><i class="fas fa-certificate mr-2 text-warning"></i> View Approval Certificate</a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-primary" href="#"><i class="fas fa-money-bill-wave mr-2"></i> Move to Disbursed</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr wire:key="empty-state-{{ $activeTab }}">
                                            <td colspan="5" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-award fa-4x text-light mb-3"></i>
                                                    <h5 class="text-muted">No Approved Phases Yet</h5>
                                                    <p class="text-muted text-sm">Phases will appear here once HQ completes the final award process.</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($activeTab === 'tab-declined')
                        <div class="tab-pane fade show active" id="tab-declined" wire:key="content-declined">
                            <div class="table-responsive p-0" style="max-height: 450px;">
                                <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                                    <thead class="bg-light text-uppercase font-weight-bold">
                                        <tr class="text-danger text-md-left">
                                            <th class="pl-4">Declined Phase</th>
                                            <th>Reason for Rejection</th>
                                            <th>Impact (Apps/Funds)</th>
                                            <th>Date Rejected</th>
                                            <th class="text-right pr-4">Operations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($phases as $phase)
                                        <tr wire:key="phase-row-{{ $phase->id }}-{{ $activeTab }}">
                                            <td class="pl-4">
                                                <span class="d-block font-weight-bold">{{ $phase->phase }}</span>
                                                <small class="badge badge-danger">
                                                    <i class="fas fa-times-circle mr-1"></i> Disapproved by HQ
                                                </small>
                                            </td>
                                            <td style="max-width: 250px;">
                                                <div class="text-dark text-sm">
                                                    {{ Str::limit($phase->rejection_comment ?? 'No specific reason provided by HQ.', 80) }}
                                                </div>
                                                @if(strlen($phase->rejection_comment) > 80)
                                                    <a href="#" class="text-xs text-primary" wire:click.prevent="viewReason({{ $phase->id }})">Read full comment</a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="text-dark font-weight-bold">{{ $phase->total_apps }} Applications</div>
                                                <small class="text-red">KES {{ number_format($phase->total_requested) }} lost</small>
                                            </td>
                                            <td>
                                                <div class="text-dark"><i class="fas fa-calendar-times mr-1"></i> {{ $phase->updated_at->format('M d, Y') }}</div>
                                                <small class="text-muted">Decision Finalized</small>
                                            </td>
                                            <td class="text-right pr-4">
                                                <div class="btn-group">
                                                    @if($phase->is_reversible)
                                                        <button class="btn btn-white btn-sm border shadow-sm px-3" wire:click="moveToPending({{ $phase->id }})">
                                                            <i class="fas fa-undo text-warning mr-1"></i> Fix & Resubmit
                                                        </button>
                                                    @else
                                                        <button class="btn btn-white btn-sm border shadow-sm px-3" title="View Rejection Report">
                                                            <i class="fas fa-file-pdf text-danger mr-1"></i> Report
                                                        </button>
                                                    @endif

                                                    <button type="button" class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon" data-toggle="dropdown"></button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="#"><i class="fas fa-history mr-2"></i> View Decision Log</a>
                                                        <a class="dropdown-item text-danger" href="#"><i class="fas fa-archive mr-2"></i> Archive Phase</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr wire:key="empty-state-{{ $activeTab }}">
                                            <td colspan="5" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-thumbs-up fa-4x text-light mb-3"></i>
                                                    <h5 class="text-muted">No Declined Phases</h5>
                                                    <p class="text-muted text-sm px-5">This is great! All submitted phases have either been approved or are still in processing.</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($activeTab === 'tab-disbursed')
                        <div class="tab-pane fade show active" id="tab-disbursed" wire:key="content-disbursed">
                            <div class="table-responsive p-0" style="max-height: 450px;">
                                <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                                    <thead class="bg-light text-uppercase font-weight-bold">
                                        <tr class="text-indigo text-md-left">
                                            <th class="pl-4">Disbursed Phase</th>
                                            <th>Payment Reference</th>
                                            <th>Final Payout</th>
                                            <th>Date Paid</th>
                                            <th class="text-right pr-4">Operations</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($phases as $phase)
                                        <tr wire:key="phase-row-{{ $phase->id }}-{{ $activeTab }}">
                                            <td class="pl-4">
                                                <span class="d-block font-weight-bold text-indigo">{{ $phase->phase }}</span>
                                                <small class="badge badge-info">
                                                    <i class="fas fa-hand-holding-usd mr-1"></i> Funds Released
                                                </small>
                                            </td>
                                            <td>
                                                <div class="text-dark font-weight-bold text-sm">{{ $phase->disbursement_ref ?? 'REF-'.strtoupper(Str::random(8)) }}</div>
                                                <small class="text-muted">Bank/Treasury ID</small>
                                            </td>
                                            <td>
                                                <div class="text-dark font-weight-bold">KES {{ number_format($phase->total_approved) }}</div>
                                                <small class="text-success">
                                                    <i class="fas fa-check-double mr-1"></i> {{ $phase->approved_apps }} Groups Paid
                                                </small>
                                            </td>
                                            <td>
                                                <div class="text-dark"><i class="fas fa-calendar-day mr-1"></i> {{ $phase->disbursed_at?->format('M d, Y') ?? $phase->updated_at->format('M d, Y') }}</div>
                                                <small class="text-muted">Transaction Complete</small>
                                            </td>
                                            <td class="text-right pr-4">
                                                <div class="btn-group">
                                                    <button class="btn btn-white btn-sm border shadow-sm px-3" title="Download Disbursement Schedule">
                                                        <i class="fas fa-file-invoice-dollar text-indigo mr-1"></i> Schedule
                                                    </button>

                                                    <button type="button" class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon" data-toggle="dropdown"></button>
                                                    <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                                        <a class="dropdown-item py-2" href="#">
                                                            <i class="fas fa-receipt mr-2 text-muted"></i> View Bank Receipt
                                                        </a>
                                                        <a class="dropdown-item py-2" href="#">
                                                            <i class="fas fa-users mr-2 text-muted"></i> Individual Group Status
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item py-2 text-primary" href="#">
                                                            <i class="fas fa-chart-line mr-2"></i> Impact Report
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr wire:key="empty-state-{{ $activeTab }}">
                                            <td colspan="5" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-money-check-alt fa-4x text-light mb-3"></i>
                                                    <h5 class="text-muted">No Disbursements Found</h5>
                                                    <p class="text-muted text-sm px-5">Once the Treasury or Finance department releases the funds for an approved phase, it will appear here for your records.</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>


            <div class="card-footer clearfix">
                <div class="pagination-sm">
                    {{ $phases->links() }}
                </div>
            </div>

        </div>



        <!-- Create Phase Modal -->
        <div wire:ignore.self class="modal fade shadow-lg" id="create-phase-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Phase</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form wire:submit.prevent="createPhase">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Phase Name *</label>
                                <input type="text" wire:model="phaseName" class="form-control"
                                    placeholder="Phase 1" required>
                                @error('phaseName') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label>Review Date *</label>
                                <input type="date" wire:model="reviewDate" class="form-control" required>
                                @error('reviewDate') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label>Review Venue (Optional)</label>
                                <input type="text" wire:model="reviewVenue" class="form-control"
                                    placeholder="e.g., Constituency Office, Community Hall">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Phase
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Applications Modal -->
        @if($showAddAppsModal && $selectedPhase)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showAddAppsModal', false)">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Applications to Phase</h5>
                        <button type="button" class="close" wire:click="$set('showAddAppsModal', false)">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if($pendingApplications->count() > 0)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Select applications to add to the review phase. Applications already in other phases are not shown.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" wire:model="selectAll">
                                            </th>
                                            <th>Application ID</th>
                                            <th>Group Name</th>
                                            <th>Amount</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingApplications as $app)
                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                    wire:model="selectedApplications"
                                                    value="{{ $app->id }}">
                                            </td>
                                            <td>
                                                <strong>{{ $app->application_id }}</strong>
                                            </td>
                                            <td>{{ $app->group_name }}</td>
                                            <td>
                                                <strong class="text-success">
                                                    KES {{ number_format($app->loan_amount) }}
                                                </strong>
                                            </td>
                                            <td>{{ $app->created_at->format('M d, Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">All applications are assigned!</h5>
                                <p class="text-muted">No pending applications available to add to this phase.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showAddAppsModal', false)">
                            Cancel
                        </button>
                        @if($pendingApplications->count() > 0)
                        <button type="button" class="btn btn-primary"
                                wire:click="addApplicationsToPhase({{ $selectedPhase }})"
                                wire:loading.attr="disabled"
                                {{ empty($selectedApplications) ? 'disabled' : '' }}>
                            <span wire:loading.remove>Add Selected ({{ count($selectedApplications) }})</span>
                            <span wire:loading>Adding...</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@push('styles')
    <style>

        /* Table Row height and vertical alignment */
        .table td {
            vertical-align: middle !important;
            padding: 12px 8px !important;
        }

        .clickable-row {
            cursor: pointer;
        }
    </style>

@endpush
@push('scripts')
<script>

    document.addEventListener('livewire:init', function () {
        Livewire.on('flash-success', ({ message }) => { toastr.success(message); });
        Livewire.on('flash-error', ({ message }) => { toastr.error(message); });


    });

    window.addEventListener('open-modal', event => { $('#' + event.detail.name).modal('show'); });
    window.addEventListener('close-modal', event => { $('#' + event.detail.name).modal('hide'); });
</script>
@endpush
