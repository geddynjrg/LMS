<section class="content">
    <div class="container-fluid">

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
                    label="Processing at HQ"
                    :value="$processingPhases"
                    icon="fa-paper-plane"
                    color="info"
                    text="Phases being processed under HQ"
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
        </div>

        <div class="card card-outline premium-summary-card shadow-sm border-0 mt-2">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                    <h3 class="card-title text-gray-900 text-capitalize">
                        <i class="fas fa-list text-primary mr-2"></i> Loan Phases
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
                                target="exportPhases"
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
                {{-- Filters --}}
                <div class="row mb-3">
                    {{-- County Filter --}}
                    <div class="col-sm col-6">
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
                    <div class="col-sm col-6" wire:key="constituency-n-wrapper-{{ $countyFilter }}">
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
                    <div class="col-sm col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Status</label>

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
                    {{-- <div class="col-sm col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">From Date</label>
                        <div class="position-relative">
                            <div class="position-absolute d-flex align-items-center justify-content-center"
                                style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                            </div>

                            <input type="date"
                                wire:model.live="dateFrom"
                                class="form-control shadow-none border-light-subtle"
                                style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                        </div>
                    </div>
                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">To Date</label>
                        <div class="position-relative">
                            <div class="position-absolute d-flex align-items-center justify-content-center"
                                style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                            </div>

                            <input type="date"
                                wire:model.live="dateTo"
                                class="form-control shadow-none border-light-subtle"
                                style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                        </div>
                    </div> --}}
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
                        wire:target="activeTab, nextPage, previousPage, gotoPage, updatedSearch, refreshData, sortBy"
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
                </div>

                <div class="table-responsive p-0"
                        style="max-height: 550px; transition: opacity 0.3s ease;"
                        wire:key="hq-applications">
                    <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                        <thead>
                            <x-table-header
                                field="county_code"
                                :sortField="$sortField"
                                :sortDirection="$sortDirection"
                            >
                                Location
                            </x-table-header>
                            <x-table-header
                                field="phase"
                                :sortField="$sortField"
                                :sortDirection="$sortDirection"
                            >
                                Phase Data
                            </x-table-header>
                            <x-table-header
                                field="applied"
                                :sortField="$sortField"
                                :sortDirection="$sortDirection"
                            >
                                Cumulative Requested
                            </x-table-header>
                            <x-table-header
                                field="approved"
                                :sortField="$sortField"
                                :sortDirection="$sortDirection"
                            >
                                Cumulative Approved
                            </x-table-header>
                            <x-table-header
                                field="status"
                                :sortField="$sortField"
                                :sortDirection="$sortDirection"
                            >
                                Status
                            </x-table-header>
                            <x-table-header
                                field="submission"
                                :sortField="$sortField"
                                :sortDirection="$sortDirection"
                            >
                                Submission
                            </x-table-header>
                            <x-table-header :sortable="false">
                                Action
                            </x-table-header>
                        </thead>
                        <tbody>
                            @forelse($phases as $phase)
                            <tr wire:key="phase-row-{{ $phase->id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <span class="d-block text-gray-900 text-sm">{{ $phase->constituency->county->county_name }}</span>
                                            <span class="text-gray-500 text-xs text-capitalize">{{ $phase->constituency->constituency_name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <span class="d-block text-sm text-gray-900">{{ ucwords($phase->phase) }}</span>
                                            @if ($phase->total_apps > 0)
                                                <span class="text-gray-500 text-xs">Applications: {{ $phase->total_apps }}</span><br>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="whitespace-nowrap">KES&nbsp;{{ number_format($phase->total_requested, 2) ?? 0 }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="whitespace-nowrap">KES&nbsp;{{ number_format($phase->total_approved, 2) ?? 0 }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $this->getStatusClasses($phase->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                        {{ $this->getStatusName($phase->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <span class="d-block text-gray-900 text-sm"><i class="far fa-calendar-alt mr-1"></i> {{ $phase->submitted_to_hq_at?->format('M d, Y') ?? 'N/A'}}</span>
                                            <span class="text-gray-500 text-xs">{{ $phase->submitted_to_hq_at?->format('h:i A') ?? 'N/A'}}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-table-action-btn
                                        type="view"
                                        route="hq.submissions.phase.review"
                                        :params="[
                                            'phase' => $phase->slug
                                        ]"
                                    />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <h5 class="text-sm text-gray-500">No Phases found</h5>
                                    @if($search || $statusFilter || $countyFilter || $constituencyFilter || $dateFrom || $dateTo)
                                        <p class="text-sm text-gray-500">Try adjusting your filters</p>
                                        <x-pill-button
                                            target="resetFilters"
                                            label="Clear Filters"
                                            icon="fas fa-eraser"
                                            size="sm"
                                            class="px-4 mr-1 shadow-sm btn-refresh-premium"
                                        />
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                    </table>
                </div>

            </div>

            <div class="card-footer clearfix bg-transparent">
                <div class="pagination-sm">
                    {{ $phases->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
