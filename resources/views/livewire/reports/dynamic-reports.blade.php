<section class="content">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h5 class="m-0 text-dark">
                    <i class="fas fa-chart-bar mr-2"></i>Custom Report Generator
                </h5>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Main Card -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-alt mr-2"></i>
                            Create Dynamic Reports
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Create custom reports by selecting specific fields and date ranges</p>


                        <!-- Select Fields to Include -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-check-square mr-2"></i>
                                            Select Fields to Include
                                        </h3>
                                        <div class="card-tools">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                    type="checkbox"
                                                    id="selectAllFields"
                                                    wire:click="toggleAllColumns">
                                                <label class="form-check-label small font-weight-normal">
                                                    Select All
                                                </label>
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <!-- Custom Filters Section -->
                                        <div class="p-3 bg-light border-bottom">
                                            <h5 class="mb-3">
                                                <i class="fas fa-filter mr-2 text-primary"></i>
                                                Custom Filters
                                                <small class="text-muted">(These filters won't appear in the exported report)</small>
                                            </h5>
                                            <div wire:ignore class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="filterLoanPhase" class="font-weight-bold">
                                                            <i class="fas fa-calendar-alt mr-1 text-info"></i>
                                                            Filter by Loan Phase
                                                        </label>
                                                        <select class="form-control select2"
                                                                id="filterLoanPhase"
                                                                wire:model.live="filterLoanPhase">
                                                            <option value="">All Phases</option>
                                                            @foreach($loanPhases as $phase)
                                                                <option value="{{ $phase->phase }}">{{ $phase->phase }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="filterWard" class="font-weight-bold">
                                                            <i class="fas fa-map-marker-alt mr-1 text-success"></i>
                                                            Filter by Ward
                                                        </label>
                                                        <select class="form-control select2"
                                                                id="filterWard"
                                                                wire:model.live="filterWard">
                                                            <option value="">All Wards</option>
                                                            @foreach($allWards as $ward)
                                                                <option value="{{ $ward->ward_name }}">{{ $ward->ward_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="filterGroupCategory" class="font-weight-bold">
                                                            <i class="fas fa-users mr-1 text-info"></i>
                                                            Filter by Group Category
                                                        </label>
                                                        <select class="form-control select2"
                                                                id="filterGroupCategory"
                                                                wire:model.live="filterGroupCategory">
                                                            <option value="">All Categories</option>
                                                            @foreach($groupCategories as $category)
                                                                <option value="{{ $category }}">{{ $category }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="filterGroupActivity" class="font-weight-bold">
                                                            <i class="fas fa-briefcase mr-1 text-info"></i>
                                                            Filter by Group Activity
                                                        </label>
                                                        <select class="form-control select2"
                                                                id="filterGroupActivity"
                                                                wire:model.live="filterGroupActivity">
                                                            <option value="">All Activities</option>
                                                            @foreach($groupActivities as $activity)
                                                                <option value="{{ $activity }}">{{ $activity }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold d-block">
                                                            <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
                                                            Additional Filters
                                                        </label>
                                                        <div class="custom-control custom-checkbox mt-2">
                                                            <input class="custom-control-input"
                                                                type="checkbox"
                                                                id="filterDefaulter"
                                                                wire:model.live="filterDefaulter">
                                                            <label for="filterDefaulter" class="custom-control-label font-weight-normal">
                                                                Show Defaulters Only
                                                            </label>
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="reportName">
                                                            <i class="fas fa-file-signature mr-1"></i>
                                                            Report Name
                                                        </label>
                                                        <input type="text"
                                                            class="form-control"
                                                            id="reportName"
                                                            placeholder="Enter report name"
                                                            wire:model="reportName"
                                                            required>
                                                        <small class="form-text text-muted">This will be the name of the report file.</small>
                                                        {{-- error handler --}}
                                                        @error('reportName')
                                                            <span class="text-sm text-danger font-weight-bold">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                                {{-- clear filters --}}
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <div class="form-group ml-auto">
                                                        <button type="button"
                                                                class="btn btn-outline-secondary"
                                                                wire:click="clearFilters">
                                                            <i class="fas fa-broom mr-1"></i> Clear All Filters
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-3 row">
                                            <!-- Groups Section -->
                                            <div class="col-md-6 col-xl-3 mb-3 d-flex">
                                                <div class="card card-outline card-success w-100" style="height: 320px;">
                                                    <div class="card-header">
                                                        <h3 class="card-title">
                                                            <i class="fas fa-layer-group mr-2"></i>
                                                            Groups
                                                            <span class="badge badge-light ml-1">{{ count($availableColumns['groups']) }}</span>
                                                        </h3>
                                                        <div class="card-tools">
                                                            <input type="checkbox"
                                                                class="category-select-all"
                                                                data-category="groups"
                                                                wire:click="toggleCategory('groups')"
                                                                wire:model="categoryGroupsSelected">
                                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2" style="overflow-y: auto;">
                                                        @foreach($availableColumns['groups'] as $key => $label)
                                                            <div class="form-group mb-1">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input class="custom-control-input column-checkbox"
                                                                        type="checkbox"
                                                                        id="groups_{{ $key }}"
                                                                        value="{{ $key }}"
                                                                        data-category="groups"
                                                                        wire:model.live="selectedColumns">
                                                                    <label for="groups_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                                        {{ $label }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Loans Section -->
                                            <div class="col-md-6 col-xl-3 mb-3 d-flex">
                                                <div class="card card-outline card-warning w-100" style="height: 320px;">
                                                    <div class="card-header">
                                                        <h3 class="card-title">
                                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                                            Loans
                                                            <span class="badge badge-light ml-1">{{ count($availableColumns['loans']) }}</span>
                                                        </h3>
                                                        <div class="card-tools">
                                                            <input type="checkbox"
                                                                class="category-select-all"
                                                                data-category="loans"
                                                                wire:click="toggleCategory('loans')"
                                                                wire:model="categoryLoansSelected">
                                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2" style="overflow-y: auto;">
                                                        @foreach($availableColumns['loans'] as $key => $label)
                                                            <div class="form-group mb-1">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input class="custom-control-input column-checkbox"
                                                                        type="checkbox"
                                                                        id="loans_{{ $key }}"
                                                                        value="{{ $key }}"
                                                                        data-category="loans"
                                                                        wire:model.live="selectedColumns">
                                                                    <label for="loans_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                                        {{ $label }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Members Section -->
                                            <div class="col-md-6 col-xl-3 mb-3 d-flex">
                                                <div class="card card-outline card-primary w-100" style="height: 320px;">
                                                    <div class="card-header">
                                                        <h3 class="card-title">
                                                            <i class="fas fa-users mr-2"></i>
                                                            Members
                                                            <span class="badge badge-light ml-1">{{ count($availableColumns['members']) }}</span>
                                                        </h3>
                                                        <div class="card-tools">
                                                            <input type="checkbox"
                                                                class="category-select-all"
                                                                data-category="members"
                                                                wire:click="toggleCategory('members')"
                                                                wire:model="categoryMembersSelected">
                                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2" style="overflow-y: auto;">
                                                        @foreach($availableColumns['members'] as $key => $label)
                                                            <div class="form-group mb-1">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input class="custom-control-input column-checkbox"
                                                                        type="checkbox"
                                                                        id="members_{{ $key }}"
                                                                        value="{{ $key }}"
                                                                        data-category="members"
                                                                        wire:model.live="selectedColumns">
                                                                    <label for="members_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                                        {{ $label }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- loans status filters --}}
                                            <div class="col-md-6 col-xl-3 mb-3 d-flex">
                                                <div class="card card-outline card-info w-100" style="height: 320px;">
                                                    <div class="card-header">
                                                        <h3 class="card-title">
                                                            <i class="fas fa-filter mr-2"></i>
                                                            Loan Status Filters
                                                            <span class="badge badge-light ml-1">{{ count($filterOptions) }}</span>
                                                        </h3>
                                                        <div class="card-tools">
                                                            <input type="checkbox"
                                                                class="category-select-all"
                                                                data-category="filters"
                                                                wire:click="toggleCategory('filters')"
                                                                wire:model="categoryFiltersSelected">
                                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2" style="overflow-y: auto;">
                                                        @foreach($filterOptions as $key => $label)
                                                            <div class="form-group mb-1">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input class="custom-control-input column-checkbox"
                                                                        type="checkbox"
                                                                        id="filters_{{ $key }}"
                                                                        value="{{ $key }}"
                                                                        data-category="filters"
                                                                        wire:model.live="selectedColumns">
                                                                    <label for="filters_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                                        {{ $label }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Selected Columns Preview --}}
                        {{-- Selected Columns Preview --}}
                        {{-- Selected Columns Preview --}}
                        @if(count(array_filter($selectedColumns, function($col) use ($availableColumns, $filterOptions) { return !array_key_exists($col, $filterOptions); })) > 0)
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card card-success card-outline">
                                    <div class="card-header bg-success text-white">
                                        <h3 class="card-title mb-0">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Selected Fields
                                            <span class="badge badge-light ml-2">{{ count(array_filter($selectedColumns, function($col) use ($filterOptions) { return !array_key_exists($col, $filterOptions); })) }}</span>
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool btn-light"
                                                    wire:click="clearAllColumns"
                                                    title="Clear All Fields">
                                                <i class="fas fa-times"></i> Clear All
                                            </button>
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info d-flex align-items-center mb-3">
                                            <i class="fas fa-info-circle mr-2 fa-lg"></i>
                                            <div>
                                                <strong>Tip:</strong> Drag fields using the <i class="fas fa-arrows-alt text-primary mx-1"></i> icon to reorder them. Click the <i class="fas fa-times text-danger mx-1"></i>
                                                icon to remove fields from your report.
                                            </div>
                                        </div>

                                        <div class="row" id="selectedColumnsContainer">
                                            @foreach($selectedColumns as $column)
                                                @php
                                                    // Skip filter options in the display
                                                    if (array_key_exists($column, $filterOptions)) {
                                                        continue;
                                                    }

                                                    $columnLabel = collect($availableColumns)
                                                        ->flatMap(fn($cols) => $cols)
                                                        ->get($column, $column);
                                                    $category = $this->getColumnCategory($column);
                                                    $categoryColors = [
                                                        'members' => 'primary',
                                                        'groups' => 'success',
                                                        'loans' => 'warning'
                                                    ];
                                                    $color = $categoryColors[$category] ?? 'secondary';
                                                    $categoryIcons = [
                                                        'members' => 'users',
                                                        'groups' => 'layer-group',
                                                        'loans' => 'money-bill-wave'
                                                    ];
                                                    $icon = $categoryIcons[$category] ?? 'cube';
                                                @endphp
                                                <div class="col-xl-3 col-lg-4 col-md-6 mb-3 selected-column-item" data-column="{{ $column }}">
                                                    <div class="card card-outline card-{{ $color }} h-75 draggable-column">
                                                        <div class="card-header bg-{{ $color }} bg-opacity-10 py-2 px-3 d-flex justify-content-between align-items-center">
                                                            <span class="drag-handle mr-auto text-{{ $color }}" style="cursor: grab;" title="Drag to reorder">
                                                                <i class="fas fa-arrows-alt"></i>
                                                            </span>
                                                            <small class="text-muted">
                                                                <i class="fas fa-{{ $icon }} mr-1"></i>
                                                                {{ ucfirst($category) }}
                                                            </small>
                                                            <button type="button"
                                                                    class="btn btn-xs btn-outline-{{ $color }} py-0 px-1 ml-auto"
                                                                    wire:click="removeColumn('{{ $column }}')"
                                                                    title="Remove {{ $columnLabel }}">
                                                                <i class="fas fa-times fa-xs"></i>
                                                            </button>
                                                        </div>
                                                        <div class="card-body mb-3">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <h6 class="mb-0 text-dark font-weight-bold flex-grow-1">{{ $columnLabel }}</h6>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="fas fa-code mr-1"></i>
                                                                {{ $column }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Quick Actions -->
                                        <div class="mt-3 pt-3 border-top">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-lightbulb mr-1"></i>
                                                        You've selected {{ count(array_filter($selectedColumns, function($col) use ($filterOptions) { return !array_key_exists($col, $filterOptions); })) }} field(s) for your report
                                                    </small>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                            wire:click="clearAllColumns">
                                                        <i class="fas fa-broom mr-1"></i> Clear All
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                            wire:click="resetToDefault">
                                                        <i class="fas fa-undo mr-1"></i> Reset to Default
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-success btn-sm"
                                                            wire:click="generateReport"
                                                            wire:loading.attr="disabled"
                                                            {{ count(array_filter($selectedColumns, function($col) use ($filterOptions) { return !array_key_exists($col, $filterOptions); })) === 0 ? 'disabled' : '' }}>
                                                        <i class="fas fa-play mr-2" wire:loading.class="d-none"></i>
                                                        <i class="fas fa-spinner fa-spin mr-2" wire:loading></i>
                                                        Generate Report
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        {{-- End Selected Columns Preview --}}
                    </div>
                </div>

                <!-- Loading State -->
                @if($loading)
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">
                            <i class="fas fa-cog fa-spin mr-2"></i>
                            Generating report... This may take a moment for large constituencies.
                        </p>
                    </div>
                </div>
                @endif

                <!-- Report Results -->
                <!-- Report Results -->
                <!-- Loading State with Progress -->
@if($loading)
<div class="card">
    <div class="card-body text-center py-5">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-3 text-muted">
            <i class="fas fa-cog fa-spin mr-2"></i>
            Generating report... This may take a moment for large constituencies.
        </p>
        @if($totalAvailableRecords > 1000)
        <div class="progress mt-3" style="height: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: 50%">
            </div>
        </div>
        <small class="text-muted mt-2">
            Processing large dataset: {{ number_format($totalAvailableRecords) }} records
        </small>
        @endif
    </div>
</div>
@endif
@if($reportGenerated && !$loading)
<div class="card card-success">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-table mr-2"></i>
            Report Results - {{ $reportName ?? 'unknown' }}


            <span class="badge badge-warning ml-2">
                <i class="fas fa-info-circle mr-1"></i>
                Preview Mode
            </span>
        </h3>
        <div class="card-tools">
            <span class="badge badge-light">
                <i class="fas fa-chart-bar mr-1"></i>
                {{ number_format($totalRecords) }}/{{ number_format($totalActualRecords) }} records

            </span>
        </div>
    </div>

    <div class="card-body p-0">
        @if($totalRecords > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-responsive table-striped table-hover mb-0">
                    {{-- <thead class="thead-dark">
                        <tr>
                            @foreach($selectedColumns as $column)
                                @php
                                    $columnLabel = collect($availableColumns)
                                        ->flatMap(fn($cols) => $cols)
                                        ->get($column, $column);
                                @endphp
                                <th class="bg-primary">
                                    {{ $columnLabel }}
                                </th>
                            @endforeach
                        </tr>
                    </thead> --}}
                    <!-- In the Report Results section -->
                    <thead class="thead-dark">
                        <tr>
                            @foreach($selectedColumns as $column)
                                @php
                                    // Skip filter options in the table headers
                                    if (array_key_exists($column, $filterOptions)) {
                                        continue;
                                    }

                                    $columnLabel = collect($availableColumns)
                                        ->flatMap(fn($cols) => $cols)
                                        ->get($column, $column);
                                @endphp
                                <th class="bg-primary">
                                    {{ $columnLabel }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    {{-- <tbody>
                        @foreach($reportData as $index => $row)
                            <tr>
                                @foreach($selectedColumns as $column)
                                    <td>{{ $row[$column] ?? 'N/A' }}</td>
                                @endforeach
                            </tr>
                        @endforeach

                        @if($showingLimitedData)
                            <tr class="table-warning">
                                <td colspan="{{ count($selectedColumns) }}" class="text-center font-weight-bold">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    📊 Showing first {{ $displayLimit }} of {{ number_format($totalAvailableRecords) }} records.
                                    <br>
                                    <small class="mt-1 d-block">
                                        <i class="fas fa-download mr-1"></i>
                                        Use the export buttons below to download all {{ number_format($totalActualRecords) }} records.
                                    </small>
                                </td>
                            </tr>
                        @elseif($totalRecords > $displayLimit)
                            <tr class="table-info">
                                <td colspan="{{ count($selectedColumns) }}" class="text-center font-weight-bold">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Showing first {{ $displayLimit }} of {{ number_format($totalActualRecords) }} records.
                                    Use export features to download complete data.
                                </td>
                            </tr>
                        @endif
                    </tbody> --}}
                    <tbody>
                        @foreach($reportData as $index => $row)
                            <tr>
                                @foreach($selectedColumns as $column)
                                    @php
                                        // Skip filter options in the table cells
                                        if (array_key_exists($column, $filterOptions)) {
                                            continue;
                                        }
                                    @endphp
                                    <td>{{ $row[$column] ?? 'N/A' }}</td>
                                @endforeach
                            </tr>

                        @endforeach
                        <tr class="table-warning">
                                <td colspan="{{ count($selectedColumns) }}" class="text-center font-weight-bold">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    📊 Showing first {{ $totalAvailableRecords <= $displayLimit ? $totalAvailableRecords :  $displayLimit }} of {{ number_format($totalAvailableRecords) }} records.
                                    <br>
                                    <small class="mt-1 d-block">
                                        <i class="fas fa-download mr-1"></i>
                                        Use the export buttons below to download all {{ number_format($totalActualRecords) }} records.
                                    </small>
                                </td>
                            </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No records found</h5>
                <p class="text-muted">No data matches your selected criteria.</p>
            </div>
        @endif
    </div>

    <!-- Enhanced Footer with Export Options -->
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">
                    <i class="fas fa-clock mr-1"></i>
                    Generated on: {{ now()->format('M j, Y g:i A') }}
                    {{-- @if($showingLimitedData) --}}

                    {{-- @endif --}}
                </small>
            </div>
            <div class="btn-group">
                @if($showingLimitedData)
                    <button type="button" class="btn btn-warning btn-sm"
                            title="Export all {{ number_format($totalAvailableRecords) }} records">
                        <i class="fas fa-download mr-2"></i>Export All Data
                    </button>
                @endif
                <button type="button"
                        class="btn btn-warning btn-sm"
                        wire:click="exportToExcel"
                        wire:target="exportToExcel"
                        wire:loading.attr="disabled"
                        >
                    <i class="fas fa-download mr-2" wire:loading.class="d-none"></i>
                    <i class="fas fa-spinner fa-spin mr-2" wire:target="exportToExcel" wire:loading></i>
                    Export Excel
                </button>
                <button type="button"
                        class="btn btn-danger btn-sm"
                        wire:click="exportToPDF"
                        wire:target="exportToPDF"
                        wire:loading.attr="disabled"
                        >
                    <i class="fas fa-file-pdf mr-2" wire:loading.class="d-none"></i>
                    <i class="fas fa-spinner fa-spin mr-2" wire:target="exportToPDF" wire:loading></i>
                    Export PDF
                </button>
                <button type="button" class="btn btn-info btn-sm" wire:click="refreshReport">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        {{-- @if($showingLimitedData) --}}
        <div class="mt-2 pt-2 border-top">
            <div class="text-center">
                <small class="text-muted">
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Tip:</strong> For better performance with large datasets, use the export feature to download and analyze data in Excel.
                </small>
            </div>
        </div>
        {{-- @endif --}}
    </div>
</div>
@endif
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
<script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script>
<script src="{{asset('plugins/sortableJs/sortable.min.js')}}"></script>
<script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Update category select all visual state when individual checkboxes change
        $(document).on('change', '.column-checkbox', function() {
            const category = $(this).data('category');
            updateCategorySelectAllState(category);
        });

        // Function to update category select all state
        function updateCategorySelectAllState(category) {
            const categoryCheckboxes = $(`.column-checkbox[data-category="${category}"]`);
            const selectAll = $(`.category-select-all[data-category="${category}"]`);

            if (categoryCheckboxes.length > 0 && selectAll.length > 0) {
                const allChecked = categoryCheckboxes.length === categoryCheckboxes.filter(':checked').length;
                const someChecked = categoryCheckboxes.filter(':checked').length > 0;

                selectAll.prop('checked', allChecked);
                selectAll.prop('indeterminate', someChecked && !allChecked);
            }
        }

        // Initialize category select all states on page load
        setTimeout(() => {
            ['members', 'groups', 'loans', 'filters'].forEach(category => {
                updateCategorySelectAllState(category);
            });
        }, 100);

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
        setTimeout(initializeColumnSorting, 300);
        setTimeout(initializeColumnSorting, 1000);
        setTimeout(initializeColumnSorting, 2000);

        // Livewire event listeners
        document.addEventListener('livewire:init', function () {
            Livewire.on('report-generated', (event) => {
                if (event.limited) {
                    toastr.warning(event.message, 'Limited Preview', { timeOut: 10000 });
                } else {
                    toastr.success(event.message);
                }
            });

            Livewire.on('export-success', (event) => {
                toastr.success(event.message, 'Export Complete');
            });

            Livewire.on('report-error', (event) => {
                toastr.error(event.message);
            });

            Livewire.on('columns-reordered', (event) => {
                toastr.info(event.message);
            });

            // Update category states after Livewire updates
            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                succeed(() => {
                    setTimeout(() => {
                        ['members', 'groups', 'loans', 'filters'].forEach(category => {
                            updateCategorySelectAllState(category);
                        });
                        // Re-initialize SortableJS after Livewire updates
                        setTimeout(initializeColumnSorting, 100);
                    }, 50);
                });
            });

            // Handle filter column selections
            Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                if (payload.serverMemo.data.selectedColumns) {
                    const selectedColumns = payload.serverMemo.data.selectedColumns;
                    const filterMappings = {
                        'financed_loans_filter': 'filterFinancedLoans',
                        'approved_loans_filter': 'filterApprovedLoans',
                        'deferred_loans_filter': 'filterDeferredLoans',
                        'cancelled_loans_filter': 'filterCancelledLoans',
                        'pending_loans_filter': 'filterPendingLoans',
                    };

                    Object.keys(filterMappings).forEach(column => {
                        const filterProperty = filterMappings[column];
                        const isSelected = selectedColumns.includes(column);
                        if (payload.serverMemo.data[filterProperty] !== isSelected) {
                            payload.serverMemo.data[filterProperty] = isSelected;
                        }
                    });
                }
            });
        });

        // Re-initialize when Livewire updates
        document.addEventListener('livewire:update', function () {
            setTimeout(initializeColumnSorting, 150);
            $('.select2').select2({ theme: 'bootstrap4' });
        });

        // Add progress indicators for long-running operations
        Livewire.on('export-progress', (event) => {
            const progress = event.progress;
            const progressBar = document.getElementById('export-progress-bar');
            const progressText = document.getElementById('export-progress-text');

            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
            if (progressText) {
                progressText.textContent = `Exporting... ${progress}%`;
            }
        });

        // Add confirmation for large exports
        function confirmLargeExport() {
            const totalRecords = @json($totalAvailableRecords);
            if (totalRecords > 10000) {
                return confirm(`This export will process ${totalRecords.toLocaleString()} records. This may take several minutes. Continue?`);
            }
            return true;
        }

        // Listen for the filters-cleared event
        Livewire.on('filters-cleared', (event) => {
            toastr.success("Filters have been cleared");
            // Reset Select2 elements
            ['filterLoanPhase', 'filterWard', 'filterGroupCategory', 'filterGroupActivity'].forEach(id => {
                const selectElement = $(`#${id}`);
                if (selectElement.length) {
                    selectElement.val(null).trigger('change');
                }
            });
        });

        Livewire.on('export-error', (event) => {
            toastr.error(event.message, 'Export Failed!');
        });

        // Select2 handling
        $(document).on('change', '.select2', function() {
            const elementName = $(this).attr('id');
            const value = $(this).val();
            if (elementName === 'filterLoanPhase') {
                @this.set('filterLoanPhase', value);
            } else if (elementName === 'filterWard') {
                @this.set('filterWard', value);
            } else if (elementName === 'filterGroupCategory') {
                @this.set('filterGroupCategory', value);
            } else if (elementName === 'filterGroupActivity') {
                @this.set('filterGroupActivity', value);
            }
        });

        $(document).on('select2:open', () => {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) searchField.focus();
        });
    });
</script>
@endpush
