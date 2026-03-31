<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card-body">
                    {{-- filters --}}
                    <div class="p-4 bg-light border rounded mb-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 text-primary font-weight-bold">
                                <i class="fas fa-sliders-h mr-2"></i> Global Report Filters
                            </h5>
                            <x-loading-button
                                title="Clear All Filters"
                                target="clearFilters"
                                class="btn-outline-secondary"
                                icon="fas fa-sync-alt"
                                label="Clear All Filters"
                            />
                        </div>

                        <div class="row">
                            <div class="col-sm-2 col-6">
                                <label class="small font-weight-bold text-muted ml-1">COUNTY</label>
                                <x-dropdown-search
                                    wire:key="dropdown-n-countyFilter-{{ count($countyOptions) }}"
                                    model="filterCounty"
                                    :options="$countyOptions"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select County"
                                    icon="fas fa-map-marker-alt"
                                />
                            </div>
                            <div class="col-sm-2 col-6 form-group" wire:key="constituency-n-wrapper-{{ $filterCounty }}">
                                @if(!empty($constituencyOptions))
                                    <label class="small font-weight-bold text-muted ml-1">CONSTITUENCY</label>
                                    <x-dropdown-search
                                        wire:key="dropdown-{{ $filterCounty }}-{{ count($constituencyOptions) }}"
                                        model="filterConstituency"
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
                            <div class="col-sm-2 col-6 form-group" wire:key="wards-n-wrapper-{{ $filterConstituency }}">
                                @if(!empty($wardsOptions))
                                    <label class="small font-weight-bold text-muted ml-1">WARD</label>
                                    <x-dropdown-search
                                        wire:key="dropdown-{{ $filterConstituency }}-{{ count($wardsOptions) }}"
                                        model="filterWard"
                                        :options="$wardsOptions"
                                        optionLabel="name"
                                        optionValue="id"
                                        placeholder="Select Ward"
                                        icon="fas fa-map-signs"
                                    />
                                @else
                                    <label class="small text-muted ml-1 opacity-50">WARD</label>
                                    <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                        <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                        <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a constituency first...</small>
                                        <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading wards...</small>
                                    </div>
                                @endif
                            </div>
                            <div class="col-sm-2 col-6 form-group" wire:key="phase-n-wrapper-{{ $filterConstituency }}">
                                @if(!empty($phaseOptions))
                                    <label class="small font-weight-bold text-muted ml-1">PHASE</label>
                                    <x-dropdown-search
                                        wire:key="dropdown-{{ $filterConstituency }}-{{ count($phaseOptions) }}"
                                        model="filterLoanPhase"
                                        :options="$phaseOptions"
                                        optionLabel="phase"
                                        optionValue="slug"
                                        placeholder="Select Phase"
                                        icon="fas fa-route"
                                    />
                                @else
                                    <label class="small text-muted ml-1 opacity-50">PHASE</label>
                                    <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                        <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                        <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a constituency first...</small>
                                        <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading phases...</small>
                                    </div>
                                @endif
                            </div>

                            <div class="col-sm col-3">
                                <div class="form-group">
                                    <label class="small font-weight-bold text-uppercase text-muted">
                                        <i class="fas fa-users mr-1 text-info"></i>
                                        Filter by Group Category
                                    </label>

                                    <x-dropdown-search
                                        model="filterGroupCategory"
                                        :options="$groupCategories"
                                        icon="fas fa-users"
                                        optionLabel="category"
                                        optionValue="category"
                                        placeholder="All Categories"
                                    />
                                </div>
                            </div>
                            <div class="col-sm col-3">
                                <div class="form-group">
                                    <label class="small font-weight-bold text-uppercase text-muted">
                                        <i class="fas fa-tools mr-1 text-warning"></i> Group Sector
                                    </label>
                                    <x-dropdown-search model="filterGroupActivity" :options="$groupActivities" icon="fas fa-briefcase" optionLabel="activity" optionValue="activity" placeholder="All Sectors" />
                                </div>
                            </div>
                        </div>

                        <div wire:ignore class="row border-top pt-3">
                            <div class="col-sm col-3">
                                <label class="small font-weight-bold text-uppercase text-muted">Disbursement From</label>
                                <div class="position-relative">
                                    {{-- Slate Icon --}}
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

                            <div class="col-sm col-3">
                                <label class="small font-weight-bold text-uppercase text-muted">Disbursement To</label>
                                <div class="position-relative">
                                    {{-- Slate Icon --}}
                                    <div class="position-absolute d-flex align-items-center justify-content-center"
                                        style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                        <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                                    </div>

                                    <input type="date"
                                        wire:model.live="dateTo"
                                        class="form-control shadow-none border-light-subtle"
                                        style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                                </div>
                            </div>

                            <div class="col-sm col-3">
                                <div class="form-group">
                                    <label class="small font-weight-bold text-uppercase text-muted">
                                        <i class="fas fa-exclamation-circle mr-1 text-danger"></i> Status Check
                                    </label>
                                    <div class="custom-control custom-switch custom-switch-off-light custom-switch-on-danger mt-1">
                                        <input type="checkbox" class="custom-control-input" id="filterDefaulter" wire:model.live="filterDefaulter">
                                        <label class="custom-control-label font-weight-normal" for="filterDefaulter">
                                            Only Defaulters
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- select columns --}}
                    <div class="row">
                        @php
                            $categories = [
                                ['key' => 'groups', 'title' => 'Groups', 'icon' => 'fa-layer-group', 'color' => 'success', 'columns' => $availableColumns['groups']],
                                ['key' => 'loans', 'title' => 'Loans', 'icon' => 'fa-money-bill-wave', 'color' => 'warning', 'columns' => $availableColumns['loans']],
                                ['key' => 'members', 'title' => 'Members', 'icon' => 'fa-users', 'color' => 'primary', 'columns' => $availableColumns['members']],
                                ['key' => 'filters', 'title' => 'Loan Status', 'icon' => 'fa-filter', 'color' => 'info', 'columns' => $filterOptions, 'isSingleSelect' => true],
                            ];
                        @endphp

                        @foreach($categories as $cat)
                        <div class="col-md-6 col-xl-3 mb-3">
                            <div class="card card-outline card-{{ $cat['color'] }} h-100 shadow-sm">
                                <div class="card-header py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h3 class="card-title font-weight-bold text-dark mb-0" style="font-size: 1rem;">
                                            <i class="fas {{ $cat['icon'] }} mr-2 text-{{ $cat['color'] }}"></i>
                                            {{ $cat['title'] }}
                                            <span class="badge badge-{{ $cat['color'] }}-soft ml-1 px-2">
                                                {{ count($cat['columns']) }}
                                            </span>
                                        </h3>

                                        <!-- Select All / Deselect All for multi-select categories only -->
                                        @if (!isset($cat['isSingleSelect']) || !$cat['isSingleSelect'])
                                        <div x-data="{
                                            allKeys: {{ json_encode(array_keys($cat['columns'])) }},
                                            selectedColumns: @entangle('selectedColumns').live,
                                            get allSelected() { return this.allKeys.every(key => this.selectedColumns.includes(key)); },
                                            toggle() {
                                                if (this.allSelected) {
                                                    this.selectedColumns = this.selectedColumns.filter(id => !this.allKeys.includes(id));
                                                } else {
                                                    let current = new Set(this.selectedColumns);
                                                    this.allKeys.forEach(key => current.add(key));
                                                    this.selectedColumns = Array.from(current);
                                                }
                                            }
                                        }">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                    class="custom-control-input"
                                                    id="select_all_{{ $cat['key'] }}"
                                                    @change="toggle()"
                                                    :checked="allSelected">
                                                <label class="custom-control-label text-muted font-weight-normal cursor-pointer"
                                                    for="select_all_{{ $cat['key'] }}">
                                                </label>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                    @if (isset($cat['isSingleSelect']) && $cat['isSingleSelect'])
                                        <!-- Radio buttons for Loan Status (single select) -->
                                        @foreach($cat['columns'] as $key => $label)
                                        <div class="custom-control custom-radio mb-1">
                                            <input type="radio"
                                                class="custom-control-input"
                                                id="{{ $cat['key'] }}_{{ $key }}"
                                                name="loan_status_filter"
                                                value="{{ $key }}"
                                                wire:model.live="filterLoanStatus">
                                            <label class="custom-control-label font-weight-normal small cursor-pointer"
                                                for="{{ $cat['key'] }}_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                        @endforeach
                                    @else
                                        <!-- Checkboxes for other categories (multi-select) -->
                                        @foreach($cat['columns'] as $key => $label)
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input class="custom-control-input"
                                                type="checkbox"
                                                id="{{ $cat['key'] }}_{{ $key }}"
                                                value="{{ $key }}"
                                                wire:model.live="selectedColumns">
                                            <label class="custom-control-label font-weight-normal small"
                                                for="{{ $cat['key'] }}_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- filtered columns --}}
                    @if(count($this->selectedColumnLabels) > 0)
                        <div class="card card-primary card-outline mt-4 shadow-sm" style="border-radius: 12px;">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="card-title text-dark font-weight-bold mb-0" style="font-size: 1.1rem;">
                                            <i class="fas fa-th-list mr-2 text-success"></i>
                                            Selected Report Columns {{ $isDirty ? '(Unsaved Changes)' : '' }}
                                        </h3> <br>
                                        <p class="text-muted small mb-0 mt-1">
                                            Drag handles to reorder. These fields will appear as table headers from left to right.
                                        </p>
                                    </div>

                                    <div class="card-tools d-flex align-items-center">
                                        <span class="badge mr-3 py-2 px-3" style="background: #f0fdf4; color: #166534; border-radius: 10px; font-weight: 600;">
                                            {{ count($this->selectedColumnLabels) }} Fields Active
                                        </span>

                                        <x-loading-button
                                            style="border-radius: 10px; transition: 0.3s;"
                                            title="reset all selections"
                                            target="clearAllColumns"
                                            class="btn-light text-danger border px-3"
                                            icon="fas fa-undo-alt"
                                            label="Reset"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="card-body bg-light p-3"
                                x-data="{
                                    selected: @entangle('selectedColumns').live,
                                    init() {
                                        new Sortable($refs.sortContainer, {
                                            animation: 200,
                                            handle: '.drag-handle', // Restricted drag to the icon
                                            ghostClass: 'sortable-ghost',
                                            onEnd: (evt) => {
                                                let newOrder = Array.from($refs.sortContainer.querySelectorAll('[data-id]'))
                                                                    .map(el => el.getAttribute('data-id'));

                                                // We must preserve the 'hidden' filter columns that weren't in the UI
                                                let hiddenFilters = this.selected.filter(id => !newOrder.includes(id));
                                                this.selected = [...newOrder, ...hiddenFilters];
                                            }
                                        });
                                    }
                                }">

                                <div class="d-flex flex-wrap" x-ref="sortContainer">
                                    @foreach($this->selectedColumnLabels as $id => $label)
                                        <div data-id="{{ $id }}"
                                            class="badge badge-white border p-0 mr-2 mb-2 shadow-sm d-flex align-items-center bg-white"
                                            style="font-size: 14px; border-radius: 8px; overflow: hidden; transition: 0.2s;">

                                            <div class="drag-handle px-2 py-2 bg-light border-right" style="cursor: grab;">
                                                <i class="fas fa-grip-vertical text-muted opacity-50"></i>
                                            </div>

                                            <span class="px-3 text-dark font-weight-bold">
                                                {{ $label }}
                                            </span>

                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 pr-2 text-muted hover-danger border-0 shadow-none"
                                                    wire:click="removeColumn('{{ $id }}')">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="card-footer bg-white border-top">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <x-input-search
                                            model="reportName"
                                            label="Report Name"
                                            icon="fas fa-tag"
                                            placeholder="e.g., Annual Assessment Report - 2026"
                                        />
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <x-loading-button
                                            title="Generate Report with current column selection"
                                            target="generateReport"
                                            class="btn-primary"
                                            icon="fas fa-file-download"
                                            label="Generate Report"
                                        />
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endif

                    {{-- data --}}
                    @if($reportGenerated)
                        <div class="card card-primary card-outline shadow-sm border-0"
                            style="border-radius: 16px; overflow: hidden;"
                            x-data="{ isDirty: @entangle('isDirty') }">

                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="card-title text-dark font-weight-bold mb-0">
                                        <i class="fas fa-table mr-2 text-success"></i>
                                        {{ $reportName ?? 'Data Preview' }}
                                        <span class="badge badge-warning-soft ml-2 py-1 px-2" style="background: #fff3e0; color: #ef6c00; font-size: 0.75rem; border-radius: 6px;">
                                            <i class="fas fa-eye mr-1"></i> Preview Mode
                                        </span>
                                    </h3>
                                    <div class="card-tools">
                                        <span class="text-muted small font-weight-bold bg-light px-3 py-2 rounded-pill border">
                                            <i class="fas fa-database mr-1 text-primary"></i>
                                            {{ number_format($totalRecords) }} / {{ number_format($totalActualRecords) }} Records Loaded
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-0 position-relative">

                                <div x-show="isDirty"
                                    x-cloak
                                    x-transition.opacity.duration.300ms
                                    :class="isDirty ? 'd-flex' : 'd-none'"
                                    class="position-absolute w-100 h-100 align-items-center justify-content-center"
                                    style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(4px); z-index: 100; top: 0; left: 0;">

                                    <div class="text-center p-4 shadow-lg bg-white border" style="border-radius: 15px; max-width: 400px; z-index: 101;">
                                        <div class="mb-3 text-warning">
                                            <span class="fa-stack fa-lg">
                                                <i class="fas fa-table fa-stack-1x"></i>
                                                <i class="fas fa-sync fa-stack-2x opacity-25 fa-spin"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-weight-bold text-dark">Selection Changed</h5>
                                        <p class="text-muted small">Your column selection has changed. Please regenerate the report to see the updated data structure.</p>
                                        <button class="btn btn-success btn-block rounded-pill font-weight-bold shadow-sm" wire:click="generateReport">
                                            <i class="fas fa-sync-alt mr-2"></i> Update Report Results
                                        </button>
                                    </div>
                                </div>

                                @if(count($reportData) > 0)
                                    <div class="table-responsive custom-scrollbar shadow-inset"
                                        :class="isDirty ? 'opacity-25' : ''"
                                        style="max-height: 650px; border-radius: 0; background: #ffffff; transition: opacity 0.3s ease;">

                                        <table class="table table-hover mb-0" style="border-collapse: separate; border-spacing: 0; min-width: 1000px;">
                                            <thead>
                                                <tr class="bg-light sticky-top shadow-sm" style="z-index: 10; top: 0;">
                                                    @foreach($this->selectedColumnLabels as $id => $label)
                                                        <th class="py-3 px-4 border-bottom border-top-0 text-uppercase tracking-wider"
                                                            style="background: #f8f9fa; font-size: 0.8rem; letter-spacing: 0.05em; color: #4a5568;">
                                                            <div class="d-flex align-items-center {{ str_contains($id, 'amount') || is_numeric($id) ? 'justify-content-end' : '' }}">
                                                                {{ $label }}
                                                                <i class="fas fa-sort ml-2 opacity-25" style="font-size: 0.7rem;"></i>
                                                            </div>
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach($reportData as $row)
                                                    <tr class="transition-all hover-bg-soft-success">
                                                        @foreach($this->selectedColumnLabels as $id => $label)
                                                            <td class="py-3 px-4 align-middle border-bottom-light {{ str_contains($id, 'amount') ? 'text-right font-family-mono' : '' }}"
                                                                style="color: #2d3748; font-size: 0.9rem;">
                                                                @if(isset($row[$id]))
                                                                    @if(is_numeric($row[$id]) && str_contains($id, 'amount'))
                                                                        <span class="font-weight-bold">{{ number_format($row[$id], 2) }}</span>
                                                                    @elseif(str_contains($id, 'status'))
                                                                        <span class="badge badge-pill {{ $row[$id] == 'Paid' ? 'badge-success' : 'badge-light border' }} px-2 py-1" style="font-size: 0.75rem;">
                                                                            {{ $row[$id] }}
                                                                        </span>
                                                                    @else
                                                                        {{ $row[$id] }}
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted opacity-50">—</span>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach

                                                @if($totalActualRecords > $displayLimit)
                                                    <tr>
                                                        <td colspan="{{ count($this->selectedColumnLabels) }}" class="p-4" style="background: linear-gradient(to bottom, #ffffff, #fffbeb);">
                                                            <div class="d-flex align-items-center justify-content-center text-warning-emphasis">
                                                                <div class="bg-warning text-white rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                                    <i class="fas fa-exclamation"></i>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0 font-weight-bold text-dark">Data Truncated for Performance</h6>
                                                                    <p class="mb-0 small text-muted">Showing {{ $displayLimit }} of {{ number_format($totalActualRecords) }} rows.</p>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-5 bg-light">
                                        <div class="mb-3">
                                            <i class="fas fa-filter fa-3x text-muted opacity-25"></i>
                                        </div>
                                        <h5 class="text-muted font-weight-light">No Data to Display</h5>
                                        <p class="small text-muted px-5">Adjust your filters or select columns above to generate a report preview.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="card-footer bg-white py-3 border-top">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <small class="text-muted">
                                            <i class="fas fa-clock mr-1"></i>
                                            Generated {{ now()->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="col-md-8 text-right">
                                        <div class="btn-group shadow-sm">
                                            <x-loading-button
                                                title="export current report data to Excel file"
                                                target="exportToExcel"
                                                class="btn-outline-success"
                                                icon="fas fa-file-excel"
                                                label="Excel"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script src="{{asset('plugins/sortableJs/sortable.min.js')}}"></script>
<script>
    $(document).ready(function() {

        function initializeColumnSorting() {
            const selectedColumnsContainer = document.getElementById('selectedColumnsContainer');

            console.log('🔧 Initializing SortableJS...', selectedColumnsContainer);

            if (selectedColumnsContainer) {
                const dragHandles = selectedColumnsContainer.querySelectorAll('.drag-handle');
                console.log('🎯 Found drag handles:', dragHandles.length);

                // Destroy any existing instance
                if (selectedColumnsContainer.sortableInstance) {
                    console.log('♻️ Destroying existing Sortable instance');
                    selectedColumnsContainer.sortableInstance.destroy();
                }

                try {
                    // Create new Sortable instance with more aggressive settings
                    selectedColumnsContainer.sortableInstance = Sortable.create(selectedColumnsContainer, {
                        handle: '.drag-handle',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        animation: 150,
                        forceFallback: true,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        onStart: function(evt) {
                            console.log('🚀 Drag started', evt);
                            evt.item.style.cursor = 'grabbing';
                            // Force styles
                            evt.item.classList.add('sortable-chosen');
                        },
                        onEnd: function(evt) {
                            console.log('✅ Drag ended', evt);
                            evt.item.style.cursor = 'grab';
                            evt.item.classList.remove('sortable-chosen');

                            // Get all column values in new order
                            const newOrder = Array.from(selectedColumnsContainer.querySelectorAll('.selected-column-item'))
                                .map(item => item.getAttribute('data-column'))
                                .filter(column => column);

                            console.log('📋 New order:', newOrder);

                            // Update Livewire component with new order
                            if (newOrder.length > 0) {
                                @this.reorderColumns(newOrder);
                            }
                        },
                        onChoose: function(evt) {
                            console.log('👆 Element chosen', evt);
                        }
                    });

                    console.log('🎉 SortableJS initialized successfully');

                    // Test if drag handles are working
                    dragHandles.forEach(handle => {
                        handle.addEventListener('mousedown', function(e) {
                            console.log('🖱️ Drag handle mousedown', e);
                        });
                    });

                } catch (error) {
                    console.error('❌ SortableJS initialization failed:', error);
                }
            } else {
                console.log('❌ Selected columns container not found');
                // Try again in a bit
                setTimeout(initializeColumnSorting, 500);
            }
        }
    });
</script>
@endpush
