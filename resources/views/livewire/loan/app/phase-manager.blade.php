<section class="content">
    <div class="container-fluid">

        <div class="row pt-3 mb-4">
            <div class="col-md-6">
                <h3 class="font-weight-bold">Phase Management</h3>
                <p class="text-muted"><i class="fas fa-map-marker-alt"></i> {{ $constituency->constituency_name }} Constituency | Loan Batch Control</p>
            </div>
            <div class="col-md-6 text-right">
                <button wire:click="$set('showCreateModal', true)" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus-circle mr-1"></i> Initiate New Phase
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-layer-group"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Phases</span>
                        <span class="info-box-number h5">{{ $phases->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Submission</span>
                        <span class="info-box-number h5">{{ $phases->where('status', 'draft')->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-paper-plane"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Processing at HQ</span>
                        <span class="info-box-number h5">{{ $phases->where('status', 'submitted')->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Approved / Ready</span>
                        <span class="info-box-number h5">{{ 4 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-navy card-tabs shadow">
            <div class="card-header border-0 py-3">
                <h3 class="card-title font-weight-bold">Batch Review Cycles</h3>
                <div class="card-tools mb-3">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" wire:model.live="search" class="form-control float-right" placeholder="Search phase name or number...">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <div class="clearfix"></div>
                <ul class="nav nav-tabs px-3" id="phaseTaskTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'tab-pending' ? 'active font-weight-bold border-bottom-0' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-pending')" type="button">
                            <i class="fas fa-edit mr-1 {{ $activeTab === 'tab-pending' ? 'text-warning' : '' }}"></i>
                            Pending Submission
                            <span class="badge {{ 1 > 0 ? 'badge-warning' : 'badge-light border' }} ml-1">
                                {{ 1 }}
                            </span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'tab-processing' ? 'active font-weight-bold border-bottom-0' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-processing')" type="button">
                            <i class="fas fa-spinner fa-spin-hover mr-1 {{ $activeTab === 'tab-processing' ? 'text-info' : '' }}"></i>
                            Processing (HQ)
                            <span class="badge {{ 1 > 0 ? 'badge-info' : 'badge-light border' }} ml-1">
                                {{ 1 }}
                            </span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'tab-approved' ? 'active font-weight-bold border-bottom-0' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-approved')" type="button">
                            <i class="fas fa-check-double mr-1 {{ $activeTab === 'tab-approved' ? 'text-success' : '' }}"></i>
                            Approved (Action)
                            <span class="badge {{ 1 > 0 ? 'badge-success' : 'badge-light border' }} ml-1 pulsate-badge">
                                {{ 1 }}
                            </span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'tab-declined' ? 'active font-weight-bold border-bottom-0' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-declined')" type="button">
                            <i class="fas fa-ban mr-1 {{ $activeTab === 'tab-declined' ? 'text-danger' : '' }}"></i>
                            Declined
                            <span class="badge badge-light border ml-1">{{ 2 }}</span>
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'tab-disbursed' ? 'active font-weight-bold border-bottom-0' : 'text-muted' }}"
                                wire:click="$set('activeTab', 'tab-disbursed')" type="button">
                            <i class="fas fa-hand-holding-usd mr-1 {{ $activeTab === 'tab-disbursed' ? 'text-navy' : '' }}"></i>
                            Disbursed
                            <span class="badge badge-navy ml-1">{{ 3 }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body position-relative">
                <div wire:loading.flex wire:target="activeTab"
                    class="w-100 h-100 position-absolute justify-content-center align-items-center"
                    style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 100px;">
                    <div class="text-center">
                        <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                        <p class="text-navy font-weight-bold mt-2">Updating Tasks View...</p>
                    </div>
                </div>
                <div class="tab-content">
                    @if($activeTab === 'tab-pending')
                    <div class="tab-pane fade show active" id="tab-pending">
                        <div class="table-responsive p-0" style="max-height: 450px;">
                            <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                                <thead class="bg-light text-uppercase small font-weight-bold">
                                    <tr class="text-lightblue text-md-left">
                                        <th class="pl-4">Phase Details</th>
                                        <th>Workflow Status</th>
                                        <th>Vetting Progress</th>
                                        <th>Financial Exposure</th>
                                        <th>Review Date</th>
                                        <th class="text-right pr-4">Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($phases as $phase)
                                    <tr>
                                        <td class="pl-4">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <span class="d-block font-weight-bold">{{ $phase->phase_name }}</span>
                                                    <small class="text-muted text-uppercase">Constituency Batch</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $statusClasses = [
                                                    'draft' => 'badge-warning',
                                                    'under_review' => 'badge-info',
                                                    'submitted' => 'badge-success',
                                                    'rejected' => 'badge-danger'
                                                ];
                                            @endphp
                                            <span class="badge {{ $statusClasses[$phase->status] ?? 'badge-secondary' }} px-3 py-2">
                                                <i class="fas fa-dot-circle mr-1"></i> {{ str_replace('_', ' ', strtoupper($phase->status)) }}
                                            </span>
                                        </td>
                                        <td style="width: 15%">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="font-weight-bold">{{ $phase->phase_applications_count }} Apps</small>
                                                <small class="text-muted">{{ $phase->vetted_percentage }}%</small>
                                            </div>
                                            <div class="progress progress-xxs shadow-sm">
                                                <div class="progress-bar bg-{{ $phase->vetted_percentage == 100 ? 'success' : 'primary' }}"
                                                    role="progressbar"
                                                    style="width: {{ $phase->vetted_percentage }}%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark font-weight-bold">KES {{ number_format($phase->total_amount_applied) }}</span><br>
                                            <small class="text-muted">Total Requested</small>
                                        </td>
                                        <td>
                                            <div class="text-dark"><i class="far fa-calendar-alt mr-1"></i> {{ $phase->review_date?->format('M d, Y') ?? 'Not Scheduled' }}</div>
                                            <small class="text-{{ $phase->review_date?->isPast() ? 'danger' : 'muted' }}">
                                                {{ $phase->review_date?->diffForHumans() ?? '' }}
                                            </small>
                                        </td>
                                        <td class="text-right pr-4">
                                            <div class="btn-group">
                                                <a href="#" class="btn btn-white btn-sm border shadow-sm px-3" title="View Batch Details">
                                                    <i class="fas fa-folder-open text-primary mr-1"></i> Manage
                                                </a>

                                                <button type="button" class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                                    <span class="sr-only">Toggle Dropdown</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                                    @if($phase->canAddApplications())
                                                        <a class="dropdown-item py-2" href="#" wire:click.prevent="openAddAppsModal({{ $phase->id }})">
                                                            <i class="fas fa-plus text-warning mr-2"></i> Attach Applications
                                                        </a>
                                                    @endif

                                                    @if($phase->status === 'draft')
                                                        <a class="dropdown-item py-2 text-success font-weight-bold" href="#" wire:click.prevent="submitToHq({{ $phase->id }})">
                                                            <i class="fas fa-paper-plane mr-2"></i> Submit to HQ
                                                        </a>
                                                    @endif

                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item py-2 text-danger" href="#" wire:click.prevent="confirmDelete({{ $phase->id }})">
                                                        <i class="fas fa-trash-alt mr-2"></i> Remove Phase
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <img src="{{ asset('dist/img/empty-folder.svg') }}" style="width: 150px;" class="mb-3 opacity-50">
                                            <h5 class="text-muted">No review phases detected.</h5>
                                            <p class="text-muted px-5">Groups cannot be approved until they are assigned to a specific review phase.</p>
                                            <button wire:click="$set('showCreateModal', true)" class="btn btn-primary btn-sm px-4">
                                                <i class="fas fa-plus mr-1"></i> Create Your First Phase
                                            </button>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    links
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
