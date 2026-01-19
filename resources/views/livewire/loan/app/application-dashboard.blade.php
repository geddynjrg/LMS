<section class="content">
    <div class="container-fluid">
        {{-- <div class="row mb-3">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-male"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Males</span>
                        <span class="info-box-number">{{ number_format($stats['total_males'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-pink" style="background-color: #e83e8c !important; color: white;"><i class="fas fa-female"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Females</span>
                        <span class="info-box-number">{{ number_format($stats['total_females'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-teal" style="background-color: #20c997 !important; color: white;"><i class="fas fa-wheelchair"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">PWD Beneficiaries</span>
                        <span class="info-box-number">{{ number_format($stats['total_pwds'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-olive" style="background-color: #3d9970 !important; color: white;"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Requested</span>
                        <span class="info-box-number">KES {{ number_format($stats['total_amount'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div> --}}
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total'] }}</h3>
                        <p>Total Applications</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <a href="#" wire:click="resetFilters" class="small-box-footer">
                        View all <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['not_in_phase'] }}</h3>
                        <p>Not in Phase</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="#" wire:click="$set('phaseFilter', 'not_in_phase')" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['in_phase'] }}</h3>
                        <p>In Phase</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <a href="{{ route('const.app.app_phases', [$constituency->county->slug, $constituency->slug]) }}"
                    class="small-box-footer">
                        Manage Phases <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['approved'] }}</h3>
                        <p>Approved</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <a href="#" wire:click="$set('statusFilter', 'approved')" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['rejected'] }}</h3>
                        <p>Rejected</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <a href="#" wire:click="$set('statusFilter', 'rejected')" class="small-box-footer">
                        View <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $activePhases->count() }}</h3>
                        <p>Active Phases</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>

                    <x-modal-trigger
                        class="small-box-footer border-0 w-100"
                        target="createNewPhase"
                        label="New Phase"
                        icon="fas fa-plus"
                        variant=""
                        block="true"
                    />
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="card">
            <div class="card-header">

                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Applications List</h3>

                    <div class="align-items-center d-flex flex-sm-row-reverse flex-wrap" style="gap: 10px;">
                        <div>
                            <x-loading-button
                                target="exportApplications"
                                class="btn-dark"
                                icon="fas fa-file-excel"
                                label="Export"
                                loadingLabel="Exporting..."
                            />
                        </div>
                        <!-- Refresh and Reset Buttons - Now inline -->
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

                        <!-- Search Input - Inline with buttons -->
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text"
                                wire:model.live.debounce.500ms="search"
                                class="form-control"
                                placeholder="Search applications...">
                            <div class="input-group-append">
                                <button class="btn btn-default" wire:loading.attr="disabled">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{ $selectedOption }}
                <!-- Active Filters -->
                @if($search || $statusFilter || $phaseFilter || $dateFrom || $dateTo)
                <div class="alert alert-info alert-dismissible fade show mb-3">
                    <strong>Active Filters:</strong>
                    @if($search)
                        <span class="badge badge-primary mr-2">Search: "{{ $search }}"</span>
                    @endif
                    @if($statusFilter)
                        <span class="badge badge-success mr-2">Status: {{ ucfirst($statusFilter) }}</span>
                    @endif
                    @if($phaseFilter)
                        <span class="badge badge-warning mr-2">
                            Phase: {{ $phaseFilter === 'not_in_phase' ? 'Not in Phase' : $phaseFilter }}
                        </span>
                    @endif
                    @if($dateFrom || $dateTo)
                        <span class="badge badge-info mr-2">
                            Date: {{ $dateFrom ?: 'Start' }} to {{ $dateTo ?: 'End' }}
                        </span>
                    @endif
                    <button type="button" class="close" wire:click="resetFilters">
                        <span>&times;</span>
                    </button>
                </div>
                @endif

                <!-- Filter Controls -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        @php
                            $status = [
                                'submitted' => 'Submitted',
                                'pending' => 'Pending',
                                'pending_submission_to_hq' => 'Pending Submission to HQ',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'disbursed' => 'Disbursed',
                                'in_training' => 'In Training',
                            ]
                        @endphp
                        <x-select2
                            model="statusFilter"
                            label="Application Status"
                            :options="$status"
                            placeholder="All Statuses"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-select2
                            model="phaseFilter"
                            label="Phase"
                            :options="$allPhases"
                            placeholder="All Phases"
                            optionLabel="phase"
                            optionValue="slug"
                            default="not_in_phase"
                            defaultLabel="Not in Phase"
                        />
                    </div>

                    <div class="col-md-2">
                        <label for="datefrom">From</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm"
                            placeholder="From Date">
                    </div>

                    <div class="col-md-2">
                        <label for="dateto">To</label>
                        <input type="date" wire:model.live="dateTo" class="form-control form-control-sm"
                            placeholder="To Date">
                    </div>

                    <div class="col-md-1">
                        <label for="perpage">Per Page</label>
                        <select wire:model.live="perPage" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label class="small text-muted mb-1 d-block">&nbsp;</label>
                            @if($this->selectedAppsCount > 0)
                            <div class="btn-group">
                                <button class="btn btn-primary btn-sm"
                                        wire:click="$set('showQuickAssignModal', true)"
                                        wire:loading.attr="disabled">
                                    <i class="fas fa-layer-group"></i>
                                    Assign ({{ $this->selectedAppsCount }})
                                </button>

                                <button class="btn btn-outline-danger btn-sm"
                                        wire:click="$set('selectedApplications', [])"
                                        wire:loading.attr="disabled">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @else
                            <div class="text-muted small pt-2">
                                Select items to assign
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="position-relative">
                    <div wire:loading.delay.shortest
                        wire:target="nextPage, previousPage, gotoPage, refreshData, perPage, statusFilter, phasefilter, dateFrom, dateTo, search"
                        class="w-100 h-100 position-absolute justify-content-center align-items-center"
                        style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 200px;">
                        <div class="text-center">
                            <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                            <p class="text-navy font-weight-bold mt-2">Loading Data...</p>
                        </div>
                    </div>
                    <!-- Applications Table -->
                    <div class="table-responsive p-0" style="max-height: 450px;">
                        <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" wire:model.live="selectAll">
                                    </th>
                                    <th>Application ID</th>
                                    <th>Group Name</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Phase</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                            wire:model.live="selectedApplications"
                                            value="{{ $app->id }}"
                                            {{ !is_null($app->phaseApplication) ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ $app->application_id }}</strong>
                                    </td>
                                    <td>{{ $app->group_name }}</td>
                                    <td>
                                        <strong class="text-success">
                                            KES {{ number_format($app->loan_amount) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if(!is_null($app->phaseApplication))
                                            <span class="badge badge-{{ $this->getStatusBadgeClass($app->phaseApplication->status) }}">
                                                {{ $this->getStatusBadgeText($app->phaseApplication->status) }}
                                            </span>
                                        @else
                                            <span class="badge badge-{{ $this->getStatusBadgeClass($app->status) }}">
                                                {{ $this->getStatusBadgeText($app->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!is_null($app->phaseApplication))
                                            <span class="badge badge-info" title="{{ $app->phaseApplication->reviewPhase->phase }}">
                                                {{ $app->phaseApplication->reviewPhase->phase }}
                                            </span>
                                            @if($app->phaseApplication->status === 'pending')
                                            <button class="btn btn-sm btn-danger p-0 px-1 ml-1"
                                                    wire:click="removeFromPhase({{ $app->phaseApplication->reviewPhase->id }}, {{ $app->id }})"
                                                    onclick="return confirm('Remove from phase?')"
                                                    title="Remove from phase">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @endif
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $app->created_at->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $app->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <x-table-action-btn
                                                type="view"
                                                route="const.app.application-view"
                                                :params="[
                                                    'county' => $constituency->county->slug,
                                                    'constituency' => $constituency->slug,
                                                    'applicationId' => $app->application_id
                                                ]"
                                            />

                                            @if(is_null($app->phaseApplication))
                                                <x-modal-trigger
                                                    target="assignToPhaseModal({{ $app->id }})"
                                                    label=""
                                                    title="Assign to Phase"
                                                    icon="fas fa-layer-group"
                                                    variant="warning"
                                                    defaultLabel=""
                                                />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <h5 class="text-muted">No applications found</h5>
                                        @if($search || $statusFilter || $phaseFilter)
                                            <p class="text-muted">Try adjusting your filters</p>
                                            <button class="btn btn-sm btn-primary" wire:click="resetFilters">
                                                <i class="fas fa-filter"></i> Clear Filters
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-footer clearfix">
                <div class="pagination-sm">
                    {{ $applications->links() }}
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
                                    placeholder="{{ $suggestedPhaseName }}" required>
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

        <!-- Quick Assign Modal -->
        <div wire:ignore.self class="modal fade shadow-lg" id="assign-to-phase-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <x-select2
                                model="selectedPhaseForQuickAssign"
                                label="-- Select a Phase --"
                                :options="$activePhases"
                                placeholder="Phase"
                                optionLabel="phase"
                                optionValue="id"
                            />

                            <small class="text-muted">
                                Only phases in "draft" or "in_review" status can accept new applications
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Assigning {{ $this->selectedAppsCount }} selected applications to phase
                        </div>

                        @error('applications')
                            <div class="alert alert-warning">
                                <strong>Some applications were skipped:</strong>
                                <ul>
                                    @foreach($errors->get('applications') as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @enderror

                        @if($this->selectedAppsCount > 0)
                        <div class="small text-muted">
                            Selected applications:
                            <ul class="mb-0 mt-1 pl-3">
                                @foreach($this->selectedAppsDisplayList as $app)
                                    <li>{{ $app['application_id'] }} - {{ $app['group_name'] }}</li>
                                @endforeach
                                @if($this->selectedAppsExtraCount > 0)
                                    <li>... and {{ $this->selectedAppsExtraCount }} more</li>
                                @endif
                            </ul>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <x-btn-submit
                            target="quickAssignToPhase"
                            label="Assign Applications"
                            icon="fas fa-layer-group"
                            :disabled="!$selectedPhaseForQuickAssign"
                            defaultLabel="Assigning..."
                            color="primary"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@push('styles')
    <style>

        /* Table Row height and vertical alignment */
        .table td {
            vertical-align: middle !important;
            padding: 12px 8px !important;
        }
    </style>


@endpush
@push('scripts')
<script>

    document.addEventListener('livewire:init', function () {
        Livewire.on('flash-success', ({ message }) => { toastr.success(message); });
        Livewire.on('flash-error', ({ message }) => { toastr.error(message); });

        Livewire.on('clear-selects', () => {
            $('select.select2').val(null).trigger('change');
        });


    });

    window.addEventListener('open-modal', event => { $('#' + event.detail.name).modal('show'); });
    window.addEventListener('close-modal', event => { $('#' + event.detail.name).modal('hide'); });
</script>
@endpush
