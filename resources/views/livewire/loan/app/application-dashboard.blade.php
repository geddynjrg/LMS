<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-0 font-bold text-3xl text-gray-900">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            {{ ucwords(strtolower($constituency->constituency_name)) }} Constituency
                        </h1>
                        <p class="mt-1 text-gray-600">Manage loan applications.</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-info px-3 py-2 shadow-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            @foreach($this->demographics as $index => $d)
                <x-stat-card
                    class="col-sm col-6 animate__animated animate__fadeIn"
                    style="animation-delay: {{ $index * 0.1 }}s;"
                    :label="$d['label']"
                    :value="$d['val']"
                    :icon="$d['icon']"
                    :color="$d['color']"
                    :type="$d['cat'] === 'total' ? 'total' : ($d['showProgress'] ? 'progress' : 'default')"
                    :distribution="$d['cat'] === 'total' ? $this->categoryDistribution : null"
                    :percentage="$d['percentage'] ?? null"
                    :action="$d['action']"
                />
            @endforeach

            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-dashed shadow-sm mb-0 card-hover" style="border-radius: 12px; border: 2px dashed #dee2e6; background: transparent;">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <h4 class="font-weight-bold mb-0 text-secondary">{{ $activePhases->count() }}</h4>
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

        <div class="card card-outline card-info shadow-sm border-0 mt-2" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                    <h3 class="card-title font-weight-bold text-dark mb-3 mb-md-0 mr-md-4">
                        <i class="fas fa-list text-primary mr-2"></i> Applications List
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

                            <x-loading-button
                                target="exportApplications"
                                class="btn-dark"
                                icon="fas fa-file-excel"
                                label="Export Data"
                                loadingLabel="..."
                            />
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body">
                <!-- Filter Controls -->
                <div class="row mb-3">
                    <div class="col-sm-3 col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Application Status</label>

                            <x-dropdown-search
                                model="statusFilter"
                                :options="$this->statusOptions"
                                optionLabel="name"
                                optionValue="value"
                                placeholder="All Statuses"
                                icon="fas fa-clipboard-check"
                            />
                        </div>
                    </div>

                    <div class="col-sm col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Loan Phase</label>

                            <x-dropdown-search
                                model="phaseFilter"
                                :options="$allPhases"
                                icon="fas fa-route"
                                optionLabel="phase"
                                optionValue="slug"
                                placeholder="Choose Phase"
                            />
                        </div>
                    </div>

                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">From Date</label>
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

                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">To Date</label>
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

                <div class="mb-3" x-data="{ selected: @entangle('selectedApplications').live }">
                    {{-- The "Controller" Div (No flex here, just Alpine logic) --}}
                    <div x-show="Object.values(selected).length > 0"
                        x-transition:enter="animate__animated animate__fadeInUp animate__fast"
                        x-transition:leave="animate__animated animate__fadeOutDown animate__fast"
                        x-transition:leave-start="opacity-100 max-h-40"
                        x-transition:leave-end="opacity-0 max-h-0"
                        x-cloak>

                        {{-- The "Visual" Div (Your d-flex styling lives here) --}}
                        <div class="alert alert-primary d-flex align-items-center justify-content-between shadow-sm"
                            style="border-radius: 12px; background-color: #f0f7ff; border: 1px solid #cfe2ff;">

                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle mr-3 text-primary"></i>
                                <span class="text-dark">
                                    You have selected <strong x-text="Object.values(selected).length"></strong> applications.
                                </span>
                            </div>

                            <div class="btn-group">
                                <button class="btn btn-primary btn-sm rounded-pill px-3 mr-2" wire:click="assignToPhaseModal">
                                    <i class="fas fa-layer-group mr-1"></i> Bulk Assign
                                </button>

                                <button wire:click="clearSelection" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Clear</button>


                            </div>
                        </div>
                    </div>
                </div>

                <div class="position-relative">
                    <div wire:loading.delay.shortest
                        wire:target="nextPage, previousPage, gotoPage, refreshData, perPage, statusFilter, phasefilter, dateFrom, dateTo, search sortBy"
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
                    <!-- Applications Table -->
                    <div class="table-responsive p-0"
                        style="max-height: 550px; transition: opacity 0.3s ease;"
                        wire:key="const-applications">
                        <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                            <thead>
                                <tr>
                                    <th width="50" class="px-4 py-3 align-middle text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                class="custom-control-input"
                                                id="selectAllCheck"
                                                wire:model.live="selectAll"
                                                wire:key="app-row-select-all">
                                            <label class="custom-control-label" for="selectAllCheck"></label>
                                        </div>
                                    </th>
                                    <x-table-header
                                        field="applicationId"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Application ID
                                    </x-table-header>

                                    <x-table-header
                                        field="created"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Submited On
                                    </x-table-header>
                                    <x-table-header
                                        field="groupName"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Group Name
                                    </x-table-header>

                                    <x-table-header
                                        field="applied"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Amount Applied
                                    </x-table-header>
                                    <x-table-header
                                        field="status"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Status
                                    </x-table-header>

                                    <x-table-header
                                        field="phase"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        phase
                                    </x-table-header>

                                    <x-table-header :sortable="false">
                                        Action
                                    </x-table-header>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                <tr wire:key="app-row-{{ $app->id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                                    <td class="px-4 py-3">
                                        <input type="checkbox"
                                            wire:model.live="selectedApplications"
                                            value="{{ (string)$app->id }}"
                                            {{ !is_null($app->phaseApplication) ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-3 font-mono">
                                        {{ $app->application_id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="d-bock text-gray-900">{{ $app->created_at->format('M d, Y') }}</span>
                                        <span class="text-gray-900">{{ $app->created_at->format('h:i A') }}</span>
                                        <span class="text-xs d-block text-gray-500">{{ $app->owner_identifier }}</span>

                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-900">{{ $app->group_name }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="whitespace-nowrap">KES&nbsp;{{ number_format($app->loan_amount, 2) }}</span>
                                    </td>

                                    <td class="px-4 py-3">
                                        @if(!is_null($app->phaseApplication))
                                            <span class="{{ $this->getStatusClasses($app->phaseApplication->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">

                                                {{ $this->getStatusLabel($app->phaseApplication->status) }}
                                            </span>
                                        @else
                                            <span class="{{ $this->getStatusClasses($app->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                @if($app->status === 'in_training' && $app->is_trained)
                                                    Pending Disbursement
                                                @else
                                                    {{ $this->getStatusLabel($app->status) }}
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($app->phaseApplication)
                                            <div class="d-flex align-items-center">
                                                <div class="d-inline-flex align-items-center bg-red-50 border border-red-200 rounded-lg overflow-hidden">

                                                    <span class="text-red-700 font-medium p-1 py-1 text-xs whitespace-nowrap">
                                                        {{ ucwords($app->phaseApplication->reviewPhase->phase) }}
                                                    </span>

                                                    @if($app->phaseApplication->status === 'pending')
                                                        <button
                                                            wire:click="confirmRemoveFromPhase({{ $app->phaseApplication->reviewPhase->id }}, {{ $app->id }})"
                                                            class="align-items-center btn d-flex justify-content-center text-amber-700"
                                                            style="height: 24px; width: 24px; padding: 0; font-size: 0.7rem; background: transparent;"
                                                            title="Remove from phase">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-600">
                                                <i class="fas fa-minus mr-1 opacity-50"></i> Not assigned
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="d-flex flex-row">
                                            <x-table-action-btn
                                                type="view"
                                                route="const.app.application-view"
                                                :params="[
                                                    'county' => $constituency->county->slug,
                                                    'constituency' => $constituency->slug,
                                                    'applicationId' => $app->application_id
                                                ]"
                                            />

                                            @if($this->canEditApplication($app))
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary border shadow-sm ml-1"
                                                        wire:click="openEditApplicationModal({{ $app->id }})"
                                                        title="Edit application details">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif

                                            @if(is_null($app->phaseApplication))
                                                <x-modal-trigger
                                                    target="singleAssignToPhaseModal({{ $app->id }})"
                                                    label=""
                                                    title="Assign to Phase"
                                                    icon="fas fa-layer-group"
                                                    variant="outline-success"
                                                    defaultLabel=""
                                                />
                                            @endif

                                            @if($this->canDeleteApplication($app))
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger border shadow-sm ml-1"
                                                        wire:click="confirmDeleteApplication({{ $app->id }})"
                                                        title="Delete Application">
                                                    <i class="fas fa-trash-alt"></i>
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

            <div class="card-footer clearfix bg-transparent">
                <div class="pagination-sm">
                    {{ $applications->links() }}
                </div>
            </div>
        </div>

        <!-- Create Phase Modal -->
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
                    <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-window-close" class="mx-1 shadow-sm btn-refresh-premium" />
                    <x-pill-button type="submit" label="Create Phase" color="primary" target="createPhase" icon="fas fa-plus-circle" class="mx-1 shadow-sm btn-refresh-premium" />
                </x-slot>
            </form>
        </x-action-modal>

        <!-- Assign Phase -->
        <x-action-modal
            id="assign-to-phase-modal"
            title="Quick Phase Assignment"
            x-on:open-modal.window="if($event.detail.name === 'assign-to-phase-modal') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'assign-to-phase-modal') $($el).modal('hide')"
         >
            <div class="form-group mb-4">
                <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Target Review Phase</label>

                <x-dropdown-search
                    model="selectedPhaseForQuickAssign"
                    :options="$activePhases"
                    optionLabel="phase"
                    optionValue="id"
                    placeholder="Choose Phase"
                    :teleport="true"
                />

                <div class="mt-2 d-flex align-items-start">
                    <i class="fas fa-info-circle text-info mt-1 mr-2" style="font-size: 0.8rem;"></i>
                    <p class="text-muted">
                        Only phases in <span class="badge badge-light border">Draft</span> or <span class="badge badge-light border">In Review</span> status are eligible.
                    </p>
                </div>
            </div>

            <hr class="my-4">

            {{-- Summary Card --}}
            <div class="card border-0 bg-light rounded-lg mb-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 font-weight-bold">
                            <i class="fas fa-list-check mr-2 text-primary"></i>
                            Selection Summary
                        </h6>
                        <span class="badge badge-primary badge-pill px-3 py-2">
                            {{ $this->selectedAppsCount }} Apps Selected
                        </span>
                    </div>

                    {{-- Error Handling for Skipped Apps --}}
                    @error('applications')
                        <div class="alert alert-warning border-0 shadow-sm small py-2">
                            <strong class="d-block mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Assignment Warnings:</strong>
                            <ul class="mb-0 pl-3">
                                @foreach($errors->get('applications') as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @enderror

                    {{-- App Preview List --}}
                    @if($this->selectedAppsCount > 0)
                        <div class="bg-white rounded border p-2" style="max-height: 150px; overflow-y: auto;">
                            <ul class="list-unstyled mb-0">
                                @foreach($this->selectedAppsDisplayList as $app)
                                    <li class="pb-1 mb-1 border-bottom last-child-border-0 d-flex justify-content-between align-items-center">
                                        <small class="font-weight-bold text-dark">{{ $app['group_name'] }}</small>
                                        <code class="x-small text-muted">{{ $app['application_id'] }}</code>
                                    </li>
                                @endforeach

                                @if($this->selectedAppsExtraCount > 0)
                                    <li class="pt-1 text-center">
                                        <small class="text-muted italic">+ {{ $this->selectedAppsExtraCount }} more applications...</small>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <p class="text-danger small mb-0 font-weight-bold">No applications selected for assignment.</p>
                        </div>
                    @endif
                </div>
            </div>

            <x-slot name="footer">
                    <x-pill-button label="Cancel" variant="outline" color="dark" data-dismiss="modal" class="mx-1" />
                    <x-pill-button
                        type="submit"
                        label="Assign to Phase"
                        color="primary"
                        target="quickAssignToPhase"
                        icon="fas fa-layer-group"
                        class="mx-1 shadow-sm"
                        :disabled="!$selectedPhaseForQuickAssign || $this->selectedAppsCount == 0"
                        />
                </x-slot>
        </x-action-modal>

        <x-action-modal
            id="modal-remove-from-phase"
            title="Remove from Phase"
            type="info"
            icon="fas fa-times"
            size="modal-md"
            x-on:open-modal.window="if($event.detail.name === 'modal-remove-from-phase') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'modal-remove-from-phase') $($el).modal('hide')"
        >
            <div class="text-center">
                <div class="mb-4 d-flex justify-content-center align-items-center">
                    <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0;">
                        <span class="text-muted small d-block mb-1">Current Phase</span>
                        <span class="text-primary font-weight-bold h6 mb-0">{{ $currentPhaseName ?? 'Standard Processing' }}</span>
                    </div>
                    <i class="fas fa-arrow-right mx-3 text-muted"></i>
                    <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 1px dashed #cbd5e1;">
                        <span class="text-muted small d-block mb-1">Destination</span>
                        <span class="text-secondary font-weight-bold h6 mb-0">Pending Queue</span>
                    </div>
                </div>

                <p class="mb-1 text-dark font-weight-bold">Confirm Phase Removal?</p>
                <p class="text-muted small">
                    This will remove the application from the current processing track.
                    All <span class="text-info font-weight-bold">assessment data</span> will be preserved, but the phase status will reset to 'Unassigned'.
                </p>
            </div>

            <x-slot name="footer">
                <div class="d-flex justify-content-center w-100">
                    {{-- Cancel Button --}}
                    <x-pill-button
                        label="Keep in Phase"
                        variant="outline"
                        color="secondary"
                        data-dismiss="modal"
                        icon="fas fa-times"
                        class="mx-1"
                    />

                    {{-- Action Button --}}
                    <x-pill-button
                        label="Confirm Removal"
                        target="removeFromPhase"
                        color="info"
                        icon="fas fa-undo-alt"
                        class="mx-1"
                    />
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
                    <p class="mb-2 small">
                        A SQL backup is saved automatically before deletion. The action is logged in the county audit trail.
                    </p>
                    @if($this->gracePeriodActive)
                        <p class="mb-0 small">
                            Extended delete access is active until <strong>{{ $this->gracePeriodEndsAt }}</strong>, including applications in draft, processing, HQ, or completed phases.
                        </p>
                    @else
                        <p class="mb-0 small">
                            Only unassigned applications can be deleted after the grace period ends.
                        </p>
                    @endif
                </div>
            </div>

            <x-slot name="footer">
                <div class="d-flex justify-content-center w-100">
                    <x-pill-button
                        label="Cancel"
                        variant="outline"
                        color="secondary"
                        data-dismiss="modal"
                        icon="fas fa-times"
                        class="mx-1"
                    />

                    <x-pill-button
                        label="Confirm Delete"
                        target="deleteApplication"
                        color="danger"
                        icon="fas fa-trash-alt"
                        class="mx-1"
                    />
                </div>
            </x-slot>
        </x-action-modal>

        @include('livewire.loan.app.partials.application-edit-modal')

    </div>
</section>
