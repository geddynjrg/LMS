<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid var(--color-primary);">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-0 font-bold text-3xl text-gray-900">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            Phase Management
                        </h1>
                        <p class="text-gray-600 mb-0">Manage loan phases.</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-pill badge-primary px-3 py-2 shadow-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            {{-- Total Phases --}}
            <div class="col-sm col-6 mb-3">
                <x-stat-card
                    label="Total Phases"
                    :value="$summary['total_phases']"
                    icon="fa-layer-group"
                    color="primary"
                    text="All Phases"
                />
            </div>
            <div class="col-sm col-6 mb-3">
                <x-stat-card
                    label="Pending Submission"
                    :value="$pendingApplications"
                    icon="fa-clock"
                    color="warning"
                    text="Phases Pending Submission to HQ"
                />
            </div>
            <div class="col-sm col-6 mb-3">
                <x-stat-card
                    label="In Processing"
                    :value="$processingPhases"
                    icon="fa-paper-plane"
                    color="info"
                    text="Submitted to HQ through training"
                />
            </div>
            <div class="col-sm col-6 mb-3">
                <x-stat-card
                    label="Disbursed"
                    :value="$disbursed"
                    icon="fa-check-circle"
                    color="success"
                    text="Phases that have been approved and disbursed"
                />
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card h-100 border-dashed shadow-sm mb-0 card-hover" style="border-radius: 12px; border: 2px dashed #dee2e6; background: transparent;">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <h4 class="font-weight-bold mb-0 text-secondary">{{ $pendingApplications }}</h4>
                        <p class="text-muted small text-uppercase font-weight-bold mb-2" style="font-size: 0.65rem;">Pending Phases</p>

                        <x-modal-trigger
                            class="btn-outline-secondary rounded-pill font-weight-bold shadow-sm"
                            target="createNewPhase"
                            label="New Phase"
                            size="xs"
                            title="Add a new phase"
                            icon="fas fa-plus mr-1"
                            variant=""
                            defaultLabel=""
                        />
                    </div>
                </div>
            </div>
        </div>

        <div x-data="{
            activeTab: @entangle('activeTab').live,
            prevIndex: 0,
            tabs: ['pending', 'processing', 'completed'],
            counts: {
                'pending': {{ $this->pendingCount ?? 0 }},
                'processing': {{ $this->processingCount ?? 0 }},
                'completed': {{ $this->completedCount ?? 0 }},
            },
            icons: {
                'pending': 'fas fa-edit',
                'processing': 'fas fa-spinner',
                'copleted': 'fas fa-check-double',
            },
            setActive(tab) {
                this.prevIndex = this.tabs.indexOf(this.activeTab);
                this.activeTab = tab;
            },
            get direction() {
                let newIndex = this.tabs.indexOf(this.activeTab);
                return newIndex > this.prevIndex ? 'forward' : 'backward';
            }
        }" class="w-100">


            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-center">
                    <ul class="nav p-3 nav-pills custom-filament-tabs shadow-sm" id="applicationTabs" role="tablist">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center"
                                :class="{ 'active': activeTab === tab }"
                                @click.prevent="setActive(tab)"
                                href="#">
                                    <i :class="icons[tab] + ' mr-2'"></i>

                                    <span x-text="tab.charAt(0).toUpperCase() + tab.slice(1).replace('-', ' ')"></span>

                                    <span x-show="counts[tab] > 0"
                                        class="badge badge-secondary ml-2"
                                        x-text="counts[tab]">
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="tab-content overflow-hidden">
                <div class="card card-outline premium-summary-card shadow-sm border-0 mt-2">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                            <h3 class="card-title text-gray-900 text-capitalize">
                                <i class="fas fa-list text-primary mr-2"></i> {{$activeTab ?? ''}} Phases
                            </h3>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-md-end flex-md-grow-1" style="gap: 15px;">
                                @if($activeTab === 'completed')
                                <div class="">
                                    <x-dropdown-search
                                        model="statusFilter"
                                        :options="$this->statusOptions"
                                        optionLabel="name"
                                        optionValue="value"
                                        placeholder="All Statuses"
                                        icon="fas fa-clipboard-check"
                                        size="38"
                                    />
                                </div>
                                @endif
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
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="card-body">
                        <div class="position-relative">
                            <div wire:loading.delay.shortest
                                wire:key="loading-overlay-container-pending"
                                wire:target="activeTab, nextPage, previousPage, gotoPage, updatedSearch, refreshData, sortBy, updatedStatusFilter"
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

                            <div class="content" wire:key="content-wrapper">
                                <div x-show="activeTab === 'pending'"
                                    x-transition:enter="animate__animated animate__faster"
                                    :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                                    >
                                    @include('livewire.loan.app.partials.phases.pending')
                                </div>

                                <div x-show="activeTab === 'processing'"
                                    x-transition:enter="animate__animated animate__faster"
                                    :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                                    >
                                    @include('livewire.loan.app.partials.phases.processing')
                                </div>

                                <div x-show="activeTab === 'completed'"
                                    x-transition:enter="animate__animated animate__faster"
                                    :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                                    >
                                    @include('livewire.loan.app.partials.phases.completed')
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer clearfix bg-transparent">
                        <div class="pagination-sm">
                            {{ $phases->links() }}
                        </div>
                    </div>
                </div>
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

    <x-action-modal
            id="create-new-phase-modal"
            title="Create New Phase"
            type="info"
            icon="fa-layer-group"
            size="modal-lg"
            x-on:open-modal.window="if($event.detail.name === 'create-new-phase-modal') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'create-new-phase-modal') $($el).modal('hide')"
        >
            <form wire:submit.prevent="createPhase">
                    <div class="alert alert-light border mb-4 py-2 px-3" style="border-radius: 12px; background-color: #f8f9fa;">
                        <span class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-1 text-info"></i>
                            Phases help organize applications into specific review cycles.
                        </span>
                    </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-4">
                            <label class="text-uppercase text-gray-600 font-weight-black small tracking-wider mb-2">Phase Name <span class="text-danger">*</span></label>
                            <div class="input-group premium-input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tag text-info"></i></span>
                                </div>
                                <input type="text" wire:model="phaseName"
                                    class="form-control form-control-lg"
                                    placeholder="{{ $suggestedPhaseName ?? 'Enter phase name...' }}" required>
                            </div>
                            @error('phaseName') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- Review Date --}}
                    <div class="col-md-6">
                        <div class="form-group mb-4">
                            <label class="text-uppercase text-gray-600 font-weight-black small tracking-wider mb-2">Scheduled Review Date <span class="text-danger">*</span></label>
                            <div class="input-group premium-input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt text-info"></i></span>
                                </div>
                                <input type="date" wire:model="reviewDate"
                                    class="form-control form-control-lg" required>
                            </div>
                            @error('reviewDate') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Venue --}}
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Review Venue (Optional)</label>
                            <div class="input-group premium-input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt text-info"></i></span>
                                </div>
                                <input type="text" wire:model="reviewVenue"
                                    class="form-control form-control-lg"
                                    placeholder="e.g., Constituency Office Hall">
                            </div>
                        </div>
                    </div>
                </div>

                <x-slot name="footer">
                    <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
                    <x-pill-button type="submit" label="Create Phase" color="primary" target="createPhase" icon="fas fa-plus-circle" class="mx-1 shadow-sm btn-refresh-premium" />
                </x-slot>
            </form>
    </x-action-modal>

    <x-action-modal
        id="confirm-deletePhase-modal"
        title="Confirm Phase Removal"
        type="success"
        icon="fas fa-check"
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

            @if($deletingPhaseGroupCount > 0)
                <div class="alert alert-warning text-left mb-3" role="alert">
                    <p class="mb-2 font-weight-bold">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        This phase has linked groups
                    </p>
                    <p class="mb-0 small">
                        Confirming will back up the phase data, then delete
                        <strong>{{ $deletingPhaseGroupCount }}</strong> group(s)
                        @if($deletingPhaseMemberCount > 0)
                            and <strong>{{ $deletingPhaseMemberCount }}</strong> member record(s)
                        @endif
                        linked to this phase. This action cannot be undone.
                    </p>
                </div>
            @else
                <p class="text-muted mb-3">
                    A backup will be created before this phase is removed.
                </p>
            @endif

            <p class="text-muted mb-0 small">
                Deletion is blocked while applications are still assigned to this phase. Remove or reassign them first if needed.
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
                    label="Confirm Delete"
                    target="confirmDeletePhase"
                    color="primary"
                    icon="fas fa-check-double"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    @include('livewire.loan.app.partials.phase-edit-modal')
</section>
