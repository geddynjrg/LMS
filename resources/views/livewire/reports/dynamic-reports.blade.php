<section class="content">
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-chart-bar mr-2"></i>Dynamic Member Reports Generator
            </h3>
            <p class="mb-0 mt-1 small opacity-75">Generate custom reports by selecting constituencies and columns</p>
        </div>
        <div class="card-body">
            <!-- Constituency Selection -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="constituency" class="form-label fw-bold">Select Constituency *</label>
                    <select wire:model="selectedConstituency" id="constituency" class="form-select" wire:loading.attr="disabled">
                        <option value="">-- Select Constituency --</option>
                        @foreach($constituencies as $constituency)
                            <option value="{{ $constituency->constituency_code }}">
                                {{ $constituency->constituency_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('selectedConstituency')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-end h-100">
                        @if($selectedConstituency)
                            <div class="text-muted">
                                <small>Selected: <strong>{{ $constituencies->firstWhere('constituency_code', $selectedConstituency)->constituency_name ?? 'Unknown' }}</strong></small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Column Selection -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3 border-bottom pb-2">
                        <i class="fas fa-columns mr-2"></i>Select Columns to Display
                        <small class="text-muted">(Select at least one column)</small>
                    </h5>

                    @foreach($availableColumns as $category => $columns)
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <div class="form-check">
                                    <input
                                        class="form-check-input category-select-all"
                                        type="checkbox"
                                        data-category="{{ $category }}"
                                        id="select_all_{{ $category }}"
                                    >
                                    <label class="form-check-label fw-bold text-uppercase" for="select_all_{{ $category }}">
                                        {{ ucfirst($category) }} Information
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($columns as $key => $label)
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input
                                                    wire:model="selectedColumns"
                                                    class="form-check-input column-checkbox"
                                                    type="checkbox"
                                                    value="{{ $key }}"
                                                    id="col_{{ $key }}"
                                                    data-category="{{ $category }}"
                                                >
                                                <label class="form-check-label" for="col_{{ $key }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @error('selectedColumns')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            wire:click="generateReport"
                            wire:loading.attr="disabled"
                            class="btn btn-primary btn-lg px-4"
                        >
                            <i class="fas fa-play mr-2" wire:loading.class="d-none"></i>
                            <i class="fas fa-spinner fa-spin mr-2" wire:loading></i>
                            Generate Report
                        </button>

                        @if($reportGenerated)
                            <button wire:click="exportToExcel" class="btn btn-success" wire:loading.attr="disabled">
                                <i class="fas fa-file-excel mr-2"></i>Export to Excel
                            </button>
                            <button wire:click="exportToPDF" class="btn btn-danger" wire:loading.attr="disabled">
                                <i class="fas fa-file-pdf mr-2"></i>Export to PDF
                            </button>
                            <button wire:click="refreshReport" class="btn btn-outline-secondary" wire:loading.attr="disabled">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            @if($loading)
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Generating report... This may take a moment for large constituencies.</p>
                </div>
            @endif

            <!-- Report Results -->
            @if($reportGenerated && !$loading)
                <div class="row">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-table mr-2"></i>
                                    Report Results
                                </h5>
                                <span class="badge bg-light text-dark fs-6">{{ number_format($totalRecords) }} records</span>
                            </div>
                            <div class="card-body">
                                @if($totalRecords > 0)
                                    <div class="table-responsive" style="max-height: 600px;">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead class="table-primary sticky-top">
                                                <tr>
                                                    @foreach($selectedColumns as $column)
                                                        <th class="bg-primary text-white">
                                                            {{ $availableColumns[array_key_first(array_filter($availableColumns, fn($cols) => array_key_exists($column, $cols)))][$column] ?? $column }}
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(array_slice($reportData, 0, 100) as $index => $row)
                                                    <tr>
                                                        @foreach($selectedColumns as $column)
                                                            <td>{{ $row[$column] }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                                @if($totalRecords > 100)
                                                    <tr class="table-info">
                                                        <td colspan="{{ count($selectedColumns) }}" class="text-center fw-bold">
                                                            ... and {{ number_format($totalRecords - 100) }} more records (use export to see all data)
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No records found for the selected criteria.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
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

        // Category select all functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('category-select-all')) {
                const category = e.target.dataset.category;
                const checkboxes = document.querySelectorAll(`.column-checkbox[data-category="${category}"]`);
                const isChecked = e.target.checked;

                check.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    checkbox.dispatchEvent(new Event('change'));
                });
            }
        });

        // Update category select all when individual checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('column-checkbox')) {
                const category = e.target.dataset.category;
                const categoryCheckboxes = document.querySelectorAll(`.column-checkbox[data-category="${category}"]`);
                const selectAll = document.querySelector(`.category-select-all[data-category="${category}"]`);

                const allChecked = Array.from(categoryCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(categoryCheckboxes).some(cb => cb.checked);

                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .table-responsive {
        border: 1px solid #dee2e6;
    }
    .category-select-all:indeterminate {
        background-color: #0d6efd;
    }
</style>
@endpush
</section>
