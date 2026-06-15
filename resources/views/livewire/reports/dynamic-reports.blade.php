<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            {{ ucwords(strtolower($constituency->constituency_name)) }} Constituency
                        </h3>
                        <p class="text-muted mb-0">Generate Dynamic Report.</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-info px-3 py-2 shadow-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-alt mr-2"></i>
                            Create Dynamic Reports
                        </h3>
                    </div>

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

                            <div wire:ignore class="row">

                                {{-- <div class="col-md-3 mb-3">
                                    <div class="form-group">
                                        <label class="small font-weight-bold text-uppercase text-muted">
                                            <i class="fas fa-map mr-1 text-danger"></i> County
                                        </label>
                                        <x-dropdown-search
                                            model="filterCounty"
                                            :options="$allCounties"
                                            icon="fas fa-map-marked-alt"
                                            optionLabel="county_name"
                                            optionValue="id"
                                            placeholder="Select County" />
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <div class="form-group">
                                        <label class="small font-weight-bold text-uppercase text-muted">
                                            <i class="fas fa-map-pin mr-1 text-danger"></i> Constituency
                                        </label>
                                        <x-dropdown-search
                                            model="filterConstituency"
                                            :options="$constituencies"
                                            icon="fas fa-map-marker-alt"
                                            optionLabel="constituency_name"
                                            optionValue="id"
                                            placeholder="Select Constituency" />
                                    </div>
                                </div> --}}

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="small font-weight-bold text-uppercase text-muted">
                                            <i class="fas fa-draw-polygon mr-1 text-success"></i> Ward
                                        </label>
                                        <x-dropdown-search model="filterWard" :options="$allWards" icon="fas fa-map-marker-alt" optionLabel="ward_name" optionValue="id" placeholder="All Wards" />
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="small font-weight-bold text-uppercase text-muted">
                                            <i class="fas fa-business-time mr-1 text-info"></i> Loan Phase
                                        </label>
                                        <x-dropdown-search model="filterLoanPhase" :options="$loanPhases" icon="fas fa-clipboard-check" optionLabel="phase" optionValue="id" placeholder="All Phases" />
                                    </div>
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
                                                    <label class="custom-control-label small text-muted font-weight-normal cursor-pointer"
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
                            <div class="card card-success card-outline mt-4 shadow-sm" style="border-radius: 12px;">
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
                            <div class="card card-success card-outline shadow-sm border-0"
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
                                                    label="Export to Excel"
                                                />
                                                {{-- <x-loading-button
                                                    title="export current report data to PDF file"
                                                    target="exportToPDF"
                                                    class="btn-outline-success btn-sm"
                                                    icon="fas fa-file-pdf"
                                                    label="PDF"
                                                    loadingLabel="Generating..."
                                                /> --}}
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
    </div>
</section>
@push('styles')
<link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
<link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
<style>
.card-outline .card-header {
    background-color: transparent;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}
.category-select-all {
    transform: scale(1.1);
}
.custom-control-input:checked ~ .custom-control-label::before {
    border-color: #007bff;
    background-color: #007bff;
}
.table th {
    position: sticky;
    top: 0;
    background: #007bff;
    color: white;
}
.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: .25rem;
    background: #fff;
    display: -ms-flexbox;
    display: flex;
    margin-bottom: 1rem;
    min-height: 80px;
    padding: .5rem;
    position: relative;
}

/* Uniform card heights with fixed height */
.col-md-6.col-xl.d-flex {
    min-height: auto;
}
.card-body.p-2 {
    padding: 0.5rem !important;
}
.form-group.mb-1 {
    margin-bottom: 0.25rem !important;
}
.custom-control-label.small {
    font-size: 0.875rem;
    line-height: 1.3;
}
.card-header .card-title {
    font-size: 1rem;
    margin-bottom: 0;
}
.card-header .badge {
    font-size: 0.7rem;
}

/* DRAG AND DROP STYLES - UPDATED */
.sortable-ghost {
    opacity: 0.6;
    background: #f8f9fa !important;
    border: 2px dashed #28a745 !important;
}

.sortable-chosen {
    background: #f8f9fa !important;
    border: 2px dashed #007bff !important;
    transform: scale(1.02);
}

.sortable-drag {
    transform: rotate(3deg) !important;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
    z-index: 9999 !important;
}

.draggable-column {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    position: relative;
}

.draggable-column:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

/* DRAG HANDLE - CRITICAL FIXES */
.drag-handle {
    opacity: 0.7;
    transition: all 0.2s ease;
    padding: 6px 8px;
    border-radius: 4px;
    cursor: grab !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1000 !important;
}

.drag-handle:hover {
    opacity: 1;
    background: rgba(0,0,0,0.08);
    cursor: grab !important;
}

.drag-handle:active {
    cursor: grabbing !important;
}

.drag-handle i {
    pointer-events: none !important;
}

/* Ensure the drag handle container is clickable */
.selected-column-item {
    cursor: grab;
    position: relative;
}

.selected-column-item:active {
    cursor: grabbing;
}

/* Make sure the card header doesn't block events */
.card-header {
    position: relative;
    z-index: 1;
}

.card-header .drag-handle {
    position: relative;
    z-index: 1001 !important;
}

/* Force pointer events on drag handle */
.drag-handle,
.drag-handle * {
    pointer-events: auto !important;
    cursor: grab !important;
}

/* Remove any potential event blocking from parent elements */
.selected-column-item,
.draggable-column,
.card {
    pointer-events: auto !important;
}

/* Specific colors for each category header */
.card-outline.card-primary .card-header {
    background-color: rgba(13, 110, 253, 0.1) !important;
    border-bottom: 1px solid rgba(13, 110, 253, 0.2);
}

.card-outline.card-success .card-header {
    background-color: rgba(25, 135, 84, 0.1) !important;
    border-bottom: 1px solid rgba(25, 135, 84, 0.2);
}

.card-outline.card-warning .card-header {
    background-color: rgba(255, 193, 7, 0.1) !important;
    border-bottom: 1px solid rgba(255, 193, 7, 0.2);
}

/* Responsive layout for 5 columns */
@media (min-width: 1200px) {
    .col-xl {
        flex: 0 0 20%;
        max-width: 20%;
    }
}
@media (max-width: 1199.98px) {
    .col-xl {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
}
@media (max-width: 767.98px) {
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{asset('plugins/sortableJs/sortable.min.js')}}"></script>
<script>
    $(document).ready(function() {

        // SortableJS initialization with multiple attempts
        function initializeColumnSorting() {
            const selectedColumnsContainer = document.getElementById('selectedColumnsContainer');

            console.log('🔧 Initializing SortableJS...', selectedColumnsContainer);

            if (selectedColumnsContainer) {
                // Check if drag handles exist
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

        // Multiple initialization attempts
        // setTimeout(initializeColumnSorting, 300);
        // setTimeout(initializeColumnSorting, 1000);
        // setTimeout(initializeColumnSorting, 2000);
    });
</script>
@endpush
