<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            {{ ucwords(strtolower($constituency->constituency_name)) }} Constituency Groups
                        </h3>
                        <p class="text-muted mb-0">Groups Data</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-info px-3 py-2 shadow-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="text-muted font-weight-bold mb-3">Groups Summary (Women, Youth, and PWDs)</h5>

        <div class="row mt-4">
            @foreach($this->demographics as $d)
                <x-stat-card class="col-lg-3 col-6"
                    :label="$d['label']"
                    :value="$d['val']"
                    :icon="$d['icon']"
                    :color="$d['color']"
                >

                    @if($d['cat'])
                        <div class="mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-weight-bold text-muted">Capital Share</span>
                                <span class="text-xs font-weight-bold text-{{ $d['color'] }}">{{ $d['percentage'] }}%</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 4px;">
                                <div class="progress-bar bg-{{ $d['color'] }}" role="progressbar" style="width: {{ $d['percentage'] }}%"></div>
                            </div>
                            @if($d['amount'] !== null)
                                <small class="text-xs text-muted mt-1 d-block font-weight-bold">
                                    KES {{ number_format($d['amount']) }}/=
                                </small>
                            @endif
                        </div>
                    @else
                        <div class="mt-2 pt-1 border-top">
                            <span class="text-xs text-primary font-weight-bold" style="font-size: 0.7rem;">
                                <i class="fas fa-database mr-1"></i> Total Registry
                            </span>
                        </div>
                    @endif

                </x-stat-card>


            @endforeach
        </div>

        <div class="card card-outline card-info shadow-sm border-0 mt-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                    <h3 class="card-title mb-3 mb-md-0 mr-md-4">
                        <i class="fas fa-list text-primary mr-2"></i> Beneficiary groups
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
                                placeholder="Search groups/gfs/identifier..."
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
                                target="exportGroupList"
                                class="btn-outline-dark"
                                icon="fas fa-file-excel"
                                label="Export Data"
                                loadingLabel="..."
                            />
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body">
                {{-- Filters --}}
                <div class="row mb-3">
                    {{-- County Filter --}}
                    {{-- <div class="col-sm-2 col-6">
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
                    </div> --}}
                    <div class="col-sm-3 col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Ward</label>
                            <x-dropdown-search
                                model="wardFilter"
                                :options="$this->wardsOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select"
                                icon="fas fa-landmark"
                            />
                        </div>
                    </div>
                    <div class="col-sm col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Category</label>
                            <x-dropdown-search
                                model="categoryFilter"
                                :options="$this->categoryOptions"
                                icon="fas fa-route"
                                optionLabel="name"
                                optionValue="value"
                                placeholder="Choose Phase"
                            />
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Sector</label>

                            <x-dropdown-search
                                model="sectorFilter"
                                :options="$this->sectorOptions"
                                optionLabel="name"
                                optionValue="value"
                                placeholder="select"
                                icon="fas fa-check-circle"
                            />
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
                            style="max-height: 550px; transition: opacity 0.3s ease;"
                            wire:key="hq-applications">
                        <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                            <thead>
                                <tr>
                                    <x-table-header
                                        field="groupName"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                    Group Data
                                    </x-table-header>
                                    <x-table-header
                                        field="gfs"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                    GFS Code
                                    </x-table-header>
                                    <x-table-header
                                        field="ward"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                    Ward
                                    </x-table-header>
                                    <x-table-header
                                        field="category"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                    Category
                                    </x-table-header>
                                    <x-table-header
                                        field="sector"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                    Sector
                                    </x-table-header>
                                    <x-table-header
                                        field="identifier"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                    Identifier
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Action
                                    </x-table-header>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->loadGroups as $group)
                                    <tr wire:key="app-row-{{ $group->grp_id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                                        <td class="px-4 py-3">
                                            <div style="overflow-wrap: break-word; word-wrap: break-word;">
                                                <span class="d-block mb-1" style="line-height: 1.2;">
                                                    {{ ucwords($group->name) }}
                                                </span>

                                                <div class="d-block">
                                                    <small class="text-gray-600 d-block mb-1">
                                                        <i class="fas fa-stamp mr-1"></i>
                                                        REG #: <span class="font-weight-600">{{ $group->reg_number ?? 'N/A' }}</span>
                                                    </small>
                                                    <small class="text-gray-600 d-block">
                                                        <i class="far fa-calendar-alt mr-1"></i>
                                                        {{ $group->date_registered ? \Carbon\Carbon::parse($group->date_registered)->format('d M Y') : 'Date N/A' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-mono">
                                            <span class="text-sm text-gray-900">{{ $group->gfs_code }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $group->ward_name }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $group->category }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $group->sector }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $group->identifier }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="btn-group">
                                                <x-table-action-btn
                                                    type="view"
                                                    route="const.groups.group"
                                                    :params="[
                                                        'county' => $constituency->county->slug,
                                                        'constituency' => $constituency->slug,
                                                        'group' => $group->slug
                                                    ]"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <h5 class="text-sm text-gray-500">No Grroups found</h5>
                                            @if($search || $wardFilter || $wardFilter || $categoryFilter || $sectorFilter)
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
                    {{ $this->loadGroups->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
