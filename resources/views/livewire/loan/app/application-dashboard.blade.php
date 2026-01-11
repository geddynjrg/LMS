<section class="content">
    <div class="container-fluid">
        <!-- Constituency Header -->
        @if($constituency)
        <div class="callout callout-info">
            <h5><i class="fas fa-map-marker-alt mr-2"></i>
                Showing applications for:
                <strong>{{ $constituency->constituency_name }} Constituency</strong>
            </h5>
        </div>
        @endif
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
                    <a href="#" wire:click="$set('showCreatePhaseModal', true)" class="small-box-footer">
                        Create Phase <i class="fas fa-plus"></i>
                    </a>
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
                        <label for="statusfilter">Status</label>
                        <select wire:model.live="statusFilter" class="form-control form-control-sm">
                            <option value="">All Statuses</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="disbursed">Disbursed</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="phasefilter">Phase</label>
                        <select wire:model.live="phaseFilter" class="form-control form-control-sm">
                            <option value="">All Phases</option>
                            <option value="not_in_phase">Not in Phase</option>
                            @foreach($allPhases as $phase)
                                <option value="{{ $phase->phase }}">
                                    {{ $phase->phase }} - {{ $phase->status }}
                                </option>
                            @endforeach
                        </select>
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

                <div wire:loading.delay.shortest wire:target="nextPage, previousPage, gotoPage, updatedSearch, updatedStatusFilter, updatedPhaseFilter, updatedDateFrom, updatedDateTo, perPage, refreshData"
                    >
                    <div class="text-center py-5">
                        {{-- <div class="spinner-border text-primary" role="status"></div> --}}
                        <p class="mt-2 text-muted">Loading applications...</p>
                    </div>
                </div>

                <div wire:loading.remove wire:target="nextPage,previousPage,gotoPage,updated*,refreshData">
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
                                        <span class="badge badge-{{ $this->getStatusBadgeClass($app->status) }}">
                                            {{ $this->getStatusBadgeText($app->status) }}
                                        </span>
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
                                            <a href="{{ route('const.app.application-view', [
                                                'county' => $constituency->county->slug,
                                                'constituency' => $constituency->slug,
                                                'applicationId' => $app->application_id
                                            ]) }}"
                                            class="btn btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if(is_null($app->phaseApplication))
                                                <button class="btn btn-outline-warning"
                                                        wire:click="assignSingleToPhase({{ $app->id }})"
                                                        title="Assign to Phase">
                                                    <i class="fas fa-layer-group"></i>
                                                </button>
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
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Showing {{ $applications->firstItem() ?? 0 }} to {{ $applications->lastItem() ?? 0 }}
                        of {{ $applications->total() }} entries
                        @if($this->selectedAppsCount > 0)
                            • <span class="text-primary">{{ $this->selectedAppsCount }} selected</span>
                        @endif
                    </div>
                    <ul class="pagination pagination-sm m-0 float-right">
                        {{ $applications->links() }}
                    </ul>
                </div>

            </div>
        </div>

        <!-- Create Phase Modal -->
        @if($showCreatePhaseModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);"
            wire:click.self="$set('showCreatePhaseModal', false)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Phase</h5>
                        <button type="button" class="close"
                                wire:click="$set('showCreatePhaseModal', false)">
                            <span>&times;</span>
                        </button>
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
                            <button type="button" class="btn btn-secondary"
                                    wire:click="$set('showCreatePhaseModal', false)">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Phase
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Assign Modal -->
        @if($showQuickAssignModal)
        <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);"
            wire:click.self="$set('showQuickAssignModal', false)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign to Phase</h5>
                        <button type="button" class="close"
                                wire:click="$set('showQuickAssignModal', false)">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Phase</label>
                            <select wire:model.live="selectedPhaseForQuickAssign" class="form-control">
                                <option value="">-- Select a Phase --</option>
                                @foreach($activePhases as $phase)
                                    @if($phase->canAddApplications())
                                    <option value="{{ $phase->id }}">
                                        {{ $phase->phase }} -
                                        ({{ $phase->review_date?->format('M d') ?? 'No Date' }} • {{ $phase->status }})
                                    </option>
                                    @endif
                                @endforeach
                            </select>
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
                        <button type="button" class="btn btn-secondary"
                                wire:click="$set('showQuickAssignModal', false)">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary"
                                wire:click="quickAssignToPhase"
                                wire:loading.attr="disabled"
                                wire:target="quickAssignToPhase"
                                {{ !$selectedPhaseForQuickAssign ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="quickAssignToPhase">
                                <i class="fas fa-layer-group"></i> Assign Applications
                            </span>
                            <span wire:loading wire:target="quickAssignToPhase">
                                <i class="fas fa-spinner fa-spin"></i> Assigning...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Flash Messages -->
        @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif
    </div>
</section>
@push('styles')
<style>
    .fade-in-on-load {
        opacity: 0;
        transition: opacity 0.4s ease-in-out;
    }

    .fade-in-on-load.loaded {
        opacity: 1;
    }

    /* Loading placeholder styling */
    #table-loading-placeholder {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 0.25rem;
    }
</style>
@endpush
@push('scripts')

<script>
    document.addEventListener('livewire:init', function () {
        // Listen for success message
        Livewire.on('flash-success', ({ message }) => {
            toastr.success(message);
        });

        // Listen for error message
        Livewire.on('flash-error', ({message}) => {
            toastr.error(message);
        });
    });

    document.addEventListener('livewire:init', () => {
       Livewire.on('error', (event) => {
           // This forces the UI to unlock if the server crashes
           console.error('Livewire Error:', event);
       });
    });
</script>
@endpush
