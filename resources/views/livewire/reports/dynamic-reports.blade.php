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

                        <!-- Report Configuration -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-cog mr-2"></i>
                                            Report Configuration
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="reportName">
                                                        <i class="fas fa-file-signature mr-1"></i>
                                                        Report Name
                                                    </label>
                                                    <input type="text"
                                                        class="form-control"
                                                        id="reportName"
                                                        placeholder="Enter report name"
                                                        wire:model="reportName">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="startDate">
                                                        <i class="fas fa-calendar-alt mr-1"></i>
                                                        Start Date
                                                    </label>
                                                    <input type="date"
                                                        class="form-control"
                                                        id="startDate"
                                                        wire:model="startDate">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="endDate">
                                                        <i class="fas fa-calendar-alt mr-1"></i>
                                                        End Date
                                                    </label>
                                                    <input type="date"
                                                        class="form-control"
                                                        id="endDate"
                                                        wire:model="endDate">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Select Fields to Include -->
<!-- Select Fields to Include -->
<div class="row">
    <div class="col-12">
        <div class="card card-success">
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
                <div class="p-3 row">
                    <!-- Phase Section -->
                    <div class="col-md-6 col-xl mb-3 d-flex">
                        <div class="card card-outline card-secondary w-100" style="height: 320px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    Phase
                                    <span class="badge badge-light ml-1">{{ count($availableColumns['phase']) }}</span>
                                </h3>
                                <div class="card-tools">
                                    <input type="checkbox"
                                        class="category-select-all"
                                        data-category="phase"
                                        wire:click="toggleCategory('phase')"
                                        wire:model="categoryPhaseSelected">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="overflow-y: auto;">
                                @if(count($availableColumns['phase']) > 0)
                                    @foreach($availableColumns['phase'] as $key => $label)
                                        <div class="form-group mb-1">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input column-checkbox"
                                                    type="checkbox"
                                                    id="phase_{{ $key }}"
                                                    value="{{ $key }}"
                                                    data-category="phase"
                                                    wire:model.live="selectedColumns">
                                                <label for="phase_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <small>No phases found</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Ward Section -->
                    <div class="col-md-6 col-xl mb-3 d-flex">
                        <div class="card card-outline card-info w-100" style="height: 320px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    Ward
                                    <span class="badge badge-light ml-1">{{ count($availableColumns['ward']) }}</span>
                                </h3>
                                <div class="card-tools">
                                    <input type="checkbox"
                                        class="category-select-all"
                                        data-category="ward"
                                        wire:click="toggleCategory('ward')"
                                        wire:model="categoryWardSelected">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="overflow-y: auto;">
                                @if(count($availableColumns['ward']) > 0)
                                    @foreach($availableColumns['ward'] as $key => $label)
                                        <div class="form-group mb-1">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input column-checkbox"
                                                    type="checkbox"
                                                    id="ward_{{ $key }}"
                                                    value="{{ $key }}"
                                                    data-category="ward"
                                                    wire:model.live="selectedColumns">
                                                <label for="ward_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <small>No wards found</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Groups Section -->
                    <div class="col-md-6 col-xl mb-3 d-flex">
                        <div class="card card-outline card-success w-100" style="height: 320px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-layer-group mr-2"></i>
                                    Groups
                                    <span class="badge badge-light ml-1">{{ count($availableColumns['group']) }}</span>
                                </h3>
                                <div class="card-tools">
                                    <input type="checkbox"
                                        class="category-select-all"
                                        data-category="group"
                                        wire:click="toggleCategory('group')"
                                        wire:model="categoryGroupSelected">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="overflow-y: auto;">
                                @foreach($availableColumns['group'] as $key => $label)
                                    <div class="form-group mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input column-checkbox"
                                                type="checkbox"
                                                id="group_{{ $key }}"
                                                value="{{ $key }}"
                                                data-category="group"
                                                wire:model.live="selectedColumns">
                                            <label for="group_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Loans Section -->
                    <div class="col-md-6 col-xl mb-3 d-flex">
                        <div class="card card-outline card-warning w-100" style="height: 320px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-money-bill-wave mr-2"></i>
                                    Loans
                                    <span class="badge badge-light ml-1">{{ count($availableColumns['loan']) }}</span>
                                </h3>
                                <div class="card-tools">
                                    <input type="checkbox"
                                        class="category-select-all"
                                        data-category="loan"
                                        wire:click="toggleCategory('loan')"
                                        wire:model="categoryLoanSelected">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="overflow-y: auto;">
                                @foreach($availableColumns['loan'] as $key => $label)
                                    <div class="form-group mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input column-checkbox"
                                                type="checkbox"
                                                id="loan_{{ $key }}"
                                                value="{{ $key }}"
                                                data-category="loan"
                                                wire:model.live="selectedColumns">
                                            <label for="loan_{{ $key }}" class="custom-control-label font-weight-normal small">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Members Section -->
                    <div class="col-md-6 col-xl mb-3 d-flex">
                        <div class="card card-outline card-primary w-100" style="height: 320px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-users mr-2"></i>
                                    Members
                                    <span class="badge badge-light ml-1">{{ count($availableColumns['member']) }}</span>
                                </h3>
                                <div class="card-tools">
                                    <input type="checkbox"
                                        class="category-select-all"
                                        data-category="member"
                                        wire:click="toggleCategory('member')"
                                        wire:model="categoryMemberSelected">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="overflow-y: auto;">
                                @foreach($availableColumns['member'] as $key => $label)
                                    <div class="form-group mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input column-checkbox"
                                                type="checkbox"
                                                id="member_{{ $key }}"
                                                value="{{ $key }}"
                                                data-category="member"
                                                wire:model.live="selectedColumns">
                                            <label for="member_{{ $key }}" class="custom-control-label font-weight-normal small">
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
@if(count($selectedColumns) > 0)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-success">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-check-circle mr-2"></i>
                    Selected Fields
                    <span class="badge badge-light ml-2">{{ count($selectedColumns) }}</span>
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
                        <strong>Tip:</strong> Click the <i class="fas fa-times text-danger mx-1"></i>
                        icon next to any field to remove it from your report.
                    </div>
                </div>

                <div class="row">
                    @foreach($selectedColumns as $column)
                        @php
                            $columnLabel = collect($availableColumns)
                                ->flatMap(fn($cols) => $cols)
                                ->get($column, $column);
                            $category = $this->getColumnCategory($column);
                            $categoryColors = [
                                'member' => 'primary',
                                'group' => 'success',
                                'loan' => 'warning',
                                'repayment' => 'info'
                            ];
                            $color = $categoryColors[$category] ?? 'secondary';
                            $categoryIcons = [
                                'member' => 'users',
                                'group' => 'layer-group',
                                'loan' => 'money-bill-wave',
                                'repayment' => 'hand-holding-usd'
                            ];
                            $icon = $categoryIcons[$category] ?? 'cube';
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <div class="card card-outline card-{{ $color }} h-75">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-{{ $icon }} text-{{ $color }} mr-2"></i>
                                                <h6 class="mb-0 text-dark font-weight-bold">{{ $columnLabel }}</h6>
                                            </div>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-tag mr-1"></i>
                                                {{ ucfirst($category) }}
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-code mr-1"></i>
                                                {{ $column }}
                                            </small>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-{{ $color }} ml-2"
                                                wire:click="removeColumn('{{ $column }}')"
                                                title="Remove {{ $columnLabel }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
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
                                You've selected {{ count($selectedColumns) }} field(s) for your report
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
                                    {{ count($selectedColumns) === 0 ? 'disabled' : '' }}>
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
                @if($reportGenerated && !$loading)
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>
                            Report Results - {{ $this->getUserConstituencyName() }}
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-light">
                                <i class="fas fa-chart-bar mr-1"></i>
                                {{ number_format($totalRecords) }} records
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($totalRecords > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mb-0">
                                    <thead class="thead-dark">
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
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($reportData, 0, 100) as $index => $row)
                                            <tr>
                                                @foreach($selectedColumns as $column)
                                                    <td>{{ $row[$column] ?? 'N/A' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        @if($totalRecords > 100)
                                            <tr class="table-info">
                                                <td colspan="{{ count($selectedColumns) }}" class="text-center font-weight-bold">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Showing first 100 of {{ number_format($totalRecords) }} records.
                                                    Use export features to download complete data.
                                                </td>
                                            </tr>
                                        @endif
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
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Generated on: {{ now()->format('M j, Y g:i A') }}
                                </small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-success btn-sm" wire:click="exportToExcel">
                                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" wire:click="exportToPDF">
                                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm" wire:click="refreshReport">
                                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                                </button>
                            </div>
                        </div>
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
            ['phase', 'ward', 'group', 'loan', 'member'].forEach(category => {
                updateCategorySelectAllState(category);
            });
        }, 100);

        // Livewire event listeners
        document.addEventListener('livewire:init', function () {
            Livewire.on('report-generated', (event) => {
                toastr.success(event.message);
            });

            Livewire.on('report-error', (event) => {
                toastr.error(event.message);
            });

            Livewire.on('export-error', (event) => {
                toastr.error(event.message);
            });

            // Update category states after Livewire updates
            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                succeed(() => {
                    setTimeout(() => {
                        ['phase', 'ward', 'group', 'loan', 'member'].forEach(category => {
                            updateCategorySelectAllState(category);
                        });
                    }, 50);
                });
            });
        });
    });
</script>
@endpush

