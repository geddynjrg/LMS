<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-table mr-2"></i>M&E Data View & Export
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters and Controls -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div wire:ignore class="form-group">
                                    <label for="financialYear">Financial Year</label>
                                    <select class="form-control select2" id="financialYear">
                                        @foreach($financialYears as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Financial Year runs from July 1 to June 30
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="search">Search</label>
                                    <input type="text" wire:model.live="search" class="form-control"
                                           placeholder="Search data or remarks...">
                                </div>
                            </div>
                            {{-- <div class="col-md-3">
                                <div class="form-group">
                                    <label for="perPage">Records Per Page</label>
                                    <select wire:model.live="perPage" class="form-control" id="perPage">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div> --}}
                        </div>

                        <!-- Summary Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-database"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Records</span>
                                        <span class="info-box-number">{{ $summaryStats['total_records'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Quarters Covered</span>
                                        <span class="info-box-number">{{ $summaryStats['quarters_covered'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-chart-bar"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Data Indicators</span>
                                        <span class="info-box-number">{{ $summaryStats['data_entries'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-purple">
                                    <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Annual Value</span>
                                        <span class="info-box-number">{{ number_format($summaryStats['total_annual_value']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Button -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg"
                                        wire:click="exportToExcel"
                                        wire:loading.attr="disabled"
                                        wire:target="exportToExcel">
                                    <span wire:loading.remove wire:target="exportToExcel">
                                        <i class="fas fa-file-excel mr-2"></i>Export to Excel
                                    </span>
                                    <span wire:loading wire:target="exportToExcel">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> processing...
                                    </span>
                                </button>
                                <span class="ml-2 text-muted">
                                    Exports filtered data in Excel format
                                </span>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="25%">Data Required</th>
                                        <th width="15%">Unit of Measure</th>
                                        <th width="8%">Q1 Achieved</th>
                                        <th width="8%">Q2 Achieved</th>
                                        <th width="8%">Q3 Achieved</th>
                                        <th width="8%">Q4 Achieved</th>
                                        <th width="8%">Annual Achieved</th>
                                        <th width="20%">Remarks/Comments</th>
                                        <th width="8%">Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($indicators as $indicator)
                                        <tr>
                                            <td class="font-weight-bold">
                                                {{ $indicator->data_required }}
                                            </td>
                                            <td class="small text-muted">
                                                {{ $indicator->unit_of_measure }}
                                            </td>
                                            <td class="text-right">
                                                @if(is_numeric($indicator->q1_achieved))
                                                    {{ number_format($indicator->q1_achieved) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if(is_numeric($indicator->q2_achieved))
                                                    {{ number_format($indicator->q2_achieved) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if(is_numeric($indicator->q3_achieved))
                                                    {{ number_format($indicator->q3_achieved) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if(is_numeric($indicator->q4_achieved))
                                                    {{ number_format($indicator->q4_achieved) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-weight-bold text-primary">
                                                @if(is_numeric($indicator->annual_achieved))
                                                    {{ number_format($indicator->annual_achieved) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="small">
                                                @if($indicator->remarks_comments)
                                                    <span title="{{ $indicator->remarks_comments }}">
                                                        {{ Str::limit($indicator->remarks_comments, 50) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">No remarks</span>
                                                @endif
                                            </td>
                                            <td class="small text-muted">
                                                {{ $indicator->updated_at->format('M j, Y') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                                    <h5>No M&E Data Found</h5>
                                                    <p>No data available for the selected filters.</p>
                                                    @if($search)
                                                        <p>Try adjusting your search criteria.</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mt-3">
                            <div class="mr-auto ml-auto">
                                <div class="text-muted">
                                    Showing {{ $indicators->firstItem() ?? 0 }} to {{ $indicators->lastItem() ?? 0 }}
                                    of {{ $indicators->total() }} records
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="pagination pagination-sm m-0 float-right">
                                    {{ $indicators->links() }}
                                </div>
                            </div> --}}
                        </div>

                        <!-- Data Quality Notes -->
                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-info-circle mr-2"></i>Data View Information</h6>
                            <ul class="mb-0">
                                <li>Data is shown for <strong>{{ $constituency->constituency_name }}</strong> constituency</li>
                                <li>Annual totals are calculated from quarterly figures</li>
                                <li>Use the search function to find specific data or remarks</li>
                                <li>Export feature includes all filtered data in Excel format</li>
                                <li>Empty cells indicate no data was entered for that period</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
<style>
    .table td {
        vertical-align: middle;
    }

    .info-box {
        cursor: default;
    }

    .badge {
        font-size: 0.75em;
    }

    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }

        .btn-lg {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
    }
</style>
<link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
<link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush
@push('scripts')
<script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
<script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Select2 handling
        $(document).on('change', '.select2', function() {
            const elementName = $(this).attr('id');
            const value = $(this).val();
            if (elementName === 'financialYear') {
                @this.set('financialYear', value);
            }
        });

        $(document).on('select2:open', () => {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) searchField.focus();
        });
    });
</script>
@endpush
