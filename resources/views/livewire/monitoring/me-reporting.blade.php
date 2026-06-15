<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>Monitoring & Evaluation Reporting
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Financial Year and Quarter Selection -->
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
                        </div>

                        <!-- Current Selection Info -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle mr-2"></i>Reporting Period</h6>
                            <p class="mb-0">
                                Constituency: <strong>{{ $constituency->constituency_name }}</strong> |
                                Financial Year: <strong>{{ $financialYear }}</strong> |
                                Current Quarter: <strong>{{ $quarter }}</strong>

                            </p>
                        </div>

                        @if(session()->has('message'))
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-2"></i>{{ session('message') }}
                            </div>
                        @endif

                        <!-- M&E Form -->
                        <form wire:submit.prevent="saveIndicators">
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($indicators as $indicatorKey => $indicator)
                                            <tr>
                                                <td class="font-weight-bold">
                                                    {{ $indicator['data_required'] }}
                                                </td>
                                                <td class="small text-muted">
                                                    {{ $indicator['unit_of_measure'] }}
                                                </td>


                                                <!-- Quarterly Inputs -->
                                                @foreach(['q1_achieved', 'q2_achieved', 'q3_achieved', 'q4_achieved'] as $quarterField)
                                                    <td>
                                                        @if(isset($indicator['input_type']) && $indicator['input_type'] === 'text_area')
                                                            <span class="text-muted">N/A</span>
                                                        @else
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                wire:model.lazy="indicators.{{ $indicatorKey }}.{{ $quarterField }}"
                                                                wire:change="calculateAnnualTotal('{{ $indicatorKey }}')"
                                                                class="form-control form-control-sm"
                                                                placeholder="0"
                                                            >
                                                        @endif
                                                    </td>
                                                @endforeach

                                                <!-- Annual Total -->
                                                <td class="font-weight-bold text-primary">
                                                    @if($indicator['input_type'] !== 'text_area')
                                                        {{ number_format($indicator['annual_achieved'] ?? 0) }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                <!-- Remarks -->
                                                <td>
                                                    @if($indicator['input_type'] === 'text_area')
                                                        <textarea
                                                            wire:model.lazy="indicators.{{ $indicatorKey }}.remarks_comments"
                                                            class="form-control form-control-sm"
                                                            rows="2"
                                                            placeholder="Enter narrative description..."
                                                        ></textarea>
                                                    @else
                                                        <input
                                                            type="text"
                                                            wire:model.lazy="indicators.{{ $indicatorKey }}.remarks_comments"
                                                            class="form-control form-control-sm"
                                                            placeholder="Enter remarks..."
                                                        >
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Special Input Sections -->
                            <div class="row mt-4">
                                <!-- Repayment Status Section -->
                                <div class="col-md-6">
                                    <div class="card card-warning">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-chart-pie mr-1"></i>Repayment Status Breakdown
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $repaymentKey = 'Repayment status: Red (0–40%), Yellow (41–70%), Green (71–100%)';
                                                $repaymentIndicator = $indicators[$repaymentKey] ?? null;
                                            @endphp
                                            @if($repaymentIndicator)
                                                <div class="form-group">
                                                    <label class="text-danger">Red (0-40%)</label>
                                                    <input type="number" class="form-control"
                                                           wire:model.lazy="indicators.{{ $repaymentKey }}.q1_achieved"
                                                           placeholder="No. of groups">
                                                </div>
                                                <div class="form-group">
                                                    <label class="text-warning">Yellow (41-70%)</label>
                                                    <input type="number" class="form-control"
                                                           wire:model.lazy="indicators.{{ $repaymentKey }}.q2_achieved"
                                                           placeholder="No. of groups">
                                                </div>
                                                <div class="form-group">
                                                    <label class="text-success">Green (71-100%)</label>
                                                    <input type="number" class="form-control"
                                                           wire:model.lazy="indicators.{{ $repaymentKey }}.q3_achieved"
                                                           placeholder="No. of groups">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Disbursement Section -->
                                <div class="col-md-6">
                                    <div class="card card-success">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-money-bill-wave mr-1"></i>Disbursement Details
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $disbursementKey = 'Number & Amount disbursed to groups';
                                                $disbursementIndicator = $indicators[$disbursementKey] ?? null;
                                            @endphp
                                            @if($disbursementIndicator)
                                                <div class="form-group">
                                                    <label>Number of Groups</label>
                                                    <input type="number" class="form-control"
                                                           wire:model.lazy="indicators.{{ $disbursementKey }}.q1_achieved"
                                                           placeholder="No. of groups">
                                                </div>
                                                <div class="form-group">
                                                    <label>Amount Disbursed (Ksh)</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                           wire:model.lazy="indicators.{{ $disbursementKey }}.q2_achieved"
                                                           placeholder="Amount in Ksh">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-save mr-2"></i>Save M&E Data
                                    </button>

                                    <button type="button" class="btn btn-info btn-lg ml-2" onclick="window.print()">
                                        <i class="fas fa-print mr-2"></i>Print Report
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Help Section -->
                        <div class="alert alert-default mt-4">
                            <h6><i class="fas fa-question-circle mr-2"></i>Reporting Guidelines</h6>
                            <ul class="mb-0">
                                <li>Report all figures for the selected quarter only</li>
                                <li>Annual totals are automatically calculated from quarterly figures</li>
                                <li>Use remarks column to provide context or explanations</li>
                                <li>Save your progress regularly - data is auto-saved per constituency</li>
                                <li>Financial Year runs from <strong>July 1 to June 30</strong></li>
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
    @media print {
        .card-header, .alert, button {
            display: none !important;
        }
        .table th {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }
    }

    .table td {
        vertical-align: middle;
    }

    .form-control-sm {
        min-height: calc(1.5em + 0.5rem + 2px);
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
