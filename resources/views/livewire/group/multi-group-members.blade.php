<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Multi-Group Members Risk Assessment</h5>

        <!-- Risk Summary Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">High Risk Members</span>
                        <span class="info-box-number">{{ number_format($riskStats['high_risk']) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $riskStats['total_multi_group'] > 0 ? number_format(($riskStats['high_risk']/$riskStats['total_multi_group'])*100, 2) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $riskStats['total_multi_group'] > 0 ? number_format(($riskStats['high_risk']/$riskStats['total_multi_group'])*100, 2) : 0 }}% of multi-group members
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Medium Risk Members</span>
                        <span class="info-box-number">{{ number_format($riskStats['medium_risk']) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $riskStats['total_multi_group'] > 0 ? number_format(($riskStats['medium_risk']/$riskStats['total_multi_group'])*100, 2) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $riskStats['total_multi_group'] > 0 ? number_format(($riskStats['medium_risk']/$riskStats['total_multi_group'])*100, 2) : 0 }}% of multi-group members
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Low Risk Members</span>
                        <span class="info-box-number">{{ number_format($riskStats['low_risk']) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $riskStats['total_multi_group'] > 0 ? number_format(($riskStats['low_risk']/$riskStats['total_multi_group'])*100, 2) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $riskStats['total_multi_group'] > 0 ? number_format(($riskStats['low_risk']/$riskStats['total_multi_group'])*100, 2) : 0 }}% of multi-group members
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Multi-Group</span>
                        <span class="info-box-number">{{ number_format($riskStats['total_multi_group']) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">
                            Members in multiple groups
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Multi-Group Members Risk Analysis</h3>
                <div class="card-tools">
                    <!-- Cache Status & Refresh Button -->
                    <div class="d-inline-flex align-items-center mr-2">
                        <small class="text-muted mr-2">
                            <i class="fas fa-{{ $cacheHit ? 'bolt' : 'database' }} mr-1"></i>
                            {{ $cacheHit ? 'Cached' : 'Fresh' }}
                            @if($lastUpdated)
                                | {{ \Carbon\Carbon::parse($lastUpdated)->diffForHumans() }}
                            @endif
                        </small>
                        <button wire:click="refreshMembers"
                                wire:loading.attr="disabled"
                                class="btn btn-sm btn-outline-primary"
                                title="Refresh Data">
                            <i class="fas fa-sync-alt" wire:loading.class="fa-spin"></i>
                            <span wire:loading.remove>Refresh</span>
                            <span wire:loading>Refreshing...</span>
                        </button>
                    </div>

                    <!-- Reset Filters Button -->
                    <button id="reset-filters" class="btn btn-sm btn-outline-secondary mr-2" title="Reset Filters">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>

                    <!-- Collapse Button -->
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($loading)
                <div class="row">
                    @for($i = 0; $i < 4; $i++)
                    <div class="col-md-3 col-sm-6 col-12 mb-3">
                        <div class="info-box bg-gradient-secondary">
                            <span class="info-box-icon"><i class="fas fa-spinner fa-spin"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Loading...</span>
                                <span class="info-box-number">-</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-description">Please wait</span>
                            </div>
                        </div>
                    </div>
                    @endfor
                </div>
                @else
                <!-- MOBILE-FRIENDLY DATATABLE -->
                <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table id="membersTable" class="table table-bordered" style="min-width: 1000px; display: none;">
                        <thead class="table-primary">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Member Details</th>
                                <th width="20%">Groups</th>
                                <th width="10%">Total Groups</th>
                                <th width="15%">Active Loans</th>
                                <th width="15%">Total Balances</th>
                                <th width="15%">Risk Level</th>
                            </tr>
                        </thead>
                        <!-- MOVED FILTER ROW TO tfoot FOR BETTER EXPORT HANDLING -->
                        <tfoot style="display: table-header-group;">
                            <tr class="filters bg-light">
                                <th></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Member" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Groups" /></th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All</option>
                                        <option value="2">2 Groups</option>
                                        <option value="3">3 Groups</option>
                                        <option value="4">4+ Groups</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All</option>
                                        <option value="0">No Loans</option>
                                        <option value="1">1 Loan</option>
                                        <option value="2">2 Loans</option>
                                        <option value="3">3+ Loans</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All Balances</option>
                                        <option value="high">High (>100K)</option>
                                        <option value="medium">Medium (50K-100K)</option>
                                        <option value="low">Low (<50K)</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All Risks</option>
                                        <option value="high">High Risk</option>
                                        <option value="medium">Medium Risk</option>
                                        <option value="low">Low Risk</option>
                                    </select>
                                </th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @php
                                $membersType = gettype($members);
                                $isCountable = is_countable($members);
                                $membersCount = $isCountable ? count($members) : 0;
                            @endphp

                            @if($members && $isCountable && $membersCount > 0)
                                @foreach($members as $member)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="member-details">
                                            <div class="member-name text-capitalize font-weight-bold mb-1">
                                                {{ $member->name ?? 'N/A' }}
                                            </div>
                                            <div class="member-contact-info d-flex flex-wrap align-items-center gap-2">
                                                @if(isset($member->number) && $member->number !== 'N/A')
                                                <span class="contact-item d-flex align-items-center text-muted small">
                                                    <i class="fas fa-phone-alt mr-1 text-primary"></i>
                                                    {{ $member->number }}
                                                </span>
                                                @endif

                                                @if(isset($member->member_Id) && $member->member_Id !== 'N/A')
                                                <span class="contact-item d-flex align-items-center text-muted small">
                                                    <i class="fas fa-id-card mr-1 text-info"></i>
                                                    {{ $member->member_Id }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="groups-container">
                                            @if(isset($member->groups) && is_array($member->groups))
                                                @foreach($member->groups as $group)
                                                    <span class="group-badge badge
                                                        {{ isset($group->is_defaulter) && $group->is_defaulter ? 'badge-danger' : 'badge-secondary' }}
                                                        mr-1 mb-1">
                                                        {{ $group->name ?? 'Unknown Group' }}
                                                        @if(isset($group->balance))
                                                            (KSh {{ number_format($group->balance) }})
                                                        @endif
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No group data available</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $totalGroups = $member->group_count ?? (isset($member->groups) ? count($member->groups) : 0);
                                            $groupBadgeClass = $totalGroups >= 4 ? 'badge-warning' : ($totalGroups == 3 ? 'badge-info' : 'badge-primary');
                                        @endphp
                                        <span class="badge {{ $groupBadgeClass }} p-2">
                                            <i class="fas fa-layer-group mr-1"></i>
                                            {{ $totalGroups }} Group(s)
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $activeLoans = $member->active_loans ?? 0;
                                            $loanBadgeClass = $activeLoans > 0 ? 'badge-warning' : 'badge-success';
                                        @endphp
                                        <span class="badge {{ $loanBadgeClass }} p-2">
                                            <i class="fas fa-file-invoice-dollar mr-1"></i>
                                            {{ $activeLoans }} Active Loan(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div class="balance-info">
                                            <strong class="text-primary">KSh {{ number_format($member->total_balance ?? 0) }}</strong>
                                            @if($member->high_risk_groups > 0)
                                                <div class="mt-1">
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        {{ $member->high_risk_groups }} high-risk group(s)
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $riskLevel = $member->risk_level ?? 'low';
                                            $riskBadgeClass = [
                                                'high' => 'danger',
                                                'medium' => 'warning',
                                                'low' => 'success'
                                            ][$riskLevel] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $riskBadgeClass }} p-2">
                                            <i class="fas fa-{{ $riskLevel === 'high' ? 'exclamation-triangle' : ($riskLevel === 'medium' ? 'exclamation-circle' : 'check-circle') }} mr-1"></i>
                                            {{ ucfirst($riskLevel) }} Risk
                                        </span>
                                        @if(isset($member->risk_score))
                                            <div class="mt-1">
                                                <small class="text-muted">Score: {{ $member->risk_score }}/10</small>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                    @if(!$members)
                                        No multi-group members data available.
                                    @elseif(!$isCountable)
                                        Invalid members data format.
                                    @else
                                        No members found in multiple groups for this constituency.
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>

                    <!-- Loading placeholder that shows until DataTable is ready -->
                    <div id="table-loading-placeholder" class="text-center py-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                        <div class="mt-3 text-muted">Loading multi-group members data...</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

@push('styles')
<link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
<style>
    /* Remove hover effects */
    .table-hover tbody tr:hover {
        background-color: transparent !important;
    }

    /* Add padding to table cells */
    .table td, .table th {
        padding: 12px 8px !important;
        vertical-align: middle;
    }

    /* Better badge styling */
    .badge {
        font-size: 0.85em;
        padding: 6px 10px;
    }

    /* Member details styling */
    .member-details .member-name {
        font-size: 1.1rem;
        margin-bottom: 4px;
    }

    .member-contact-info {
        font-size: 0.85rem;
    }

    .contact-item {
        background: #f8f9fa;
        padding: 2px 8px;
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }

    /* Groups container styling */
    .groups-container {
        display: flex;
        flex-wrap: wrap;
        max-height: 100px;
        overflow-y: auto;
    }

    .group-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        margin-bottom: 5px;
    }

    /* Balance info styling */
    .balance-info {
        text-align: center;
    }

    /* Loading placeholder styling */
    #table-loading-placeholder {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 0.25rem;
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .table-responsive {
            border: 1px solid #dee2e6;
            -webkit-overflow-scrolling: touch;
        }
        #membersTable {
            min-width: 1000px; /* Force horizontal scroll on mobile */
        }
        .dataTables_wrapper .dataTables_filter input {
            width: 100% !important;
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            width: 100% !important;
            margin-bottom: 10px;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }
        .btn-group .btn {
            flex: 1;
            min-width: 80px;
            font-size: 0.8rem;
        }
        .dataTables_wrapper .dataTables_info {
            padding-left: 10px;
        }
        .dataTables_wrapper .dataTables_paginate {
            padding-right: 10px;
        }

        /* Adjust padding for mobile */
        .table td, .table th {
            padding: 8px 6px !important;
        }

        /* Adjust font sizes for mobile */
        .member-details .member-name {
            font-size: 1rem;
        }

        .member-contact-info {
            font-size: 0.8rem;
        }

        .contact-item {
            padding: 1px 6px;
        }

        .group-badge {
            font-size: 0.7rem;
        }

        .badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
    }

    /* Better table styling */
    .table th {
        border-top: none;
        font-weight: 600;
        white-space: nowrap;
    }

    .table td {
        white-space: nowrap;
    }

    .filters input, .filters select {
        font-size: 0.8rem;
    }

    /* Ensure table headers don't overlap on mobile */
    .table-responsive::-webkit-scrollbar {
        -webkit-appearance: none;
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        border-radius: 4px;
        background-color: rgba(0,0,0,.5);
    }

    /* Add to your existing styles */
    .dataTables_wrapper {
        position: relative;
        clear: both;
    }

    #membersTable_wrapper .row {
        margin-left: -15px;
        margin-right: -15px;
    }

    #membersTable_wrapper .col-md-4 {
        padding-left: 15px;
        padding-right: 15px;
    }

    /* Ensure proper spacing after refresh */
    .card-body {
        min-height: 200px;
    }

    /* Fix for cut-off "Showing" text */
    .dataTables_wrapper .dataTables_info {
        min-width: 150px; /* Ensure enough space for the text */
        padding-left: 15px;
        white-space: nowrap; /* Keep it on one line */
        overflow: visible !important; /* Prevent cutting */
    }

    /* Ensure the container has enough space */
    .dataTables_wrapper .row:last-child .col-md-5 {
        flex: 0 0 auto;
        width: auto;
        min-width: 200px;
    }

    .dataTables_wrapper .row:last-child .col-md-7 {
        margin-left: auto;
        flex: 1;
    }

    .dataTables_wrapper .row:last-child {
        width: 100%;
        min-width: 1000px;
    }

    /* Flex utilities for contact info */
    .d-flex {
        display: flex !important;
    }

    .flex-wrap {
        flex-wrap: wrap !important;
    }

    .align-items-center {
        align-items: center !important;
    }

    .gap-2 {
        gap: 0.5rem !important;
    }

    .mr-1 {
        margin-right: 0.25rem !important;
    }

    .mb-1 {
        margin-bottom: 0.25rem !important;
    }

    .mt-1 {
        margin-top: 0.25rem !important;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
<script>
// DataTable Manager - Configured for Multi-Group Members
class DataTableManager {
    constructor() {
        this.table = null;
        this.isInitializing = false;
        this.initToastr();
    }

    initToastr() {
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "5000",
                "extendedTimeOut": "1000"
            };
        }
    }

    showNotification(message, type = 'success') {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        }
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    showLoading() {
        const $card = $('.card:has(#membersTable)');
        if ($card.length === 0) return;

        this.hideLoading();

        $card.css('position', 'relative').append(`
            <div id="datatable-loading-overlay" style="
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255,255,255,0.9);
                z-index: 999;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.25rem;
            ">
                <div class="text-center">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <div class="mt-2 text-muted">Loading multi-group members data...</div>
                </div>
            </div>
        `);

        $card.find('.card-body').css('pointer-events', 'none');
    }

    hideLoading() {
        const $card = $('.card:has(#membersTable)');
        $('#datatable-loading-overlay').remove();
        $card.find('.card-body').css('pointer-events', 'auto');
    }

    destroyDataTable() {
        if (this.table && $.fn.DataTable.isDataTable('#membersTable')) {
            try {
                this.table.destroy(true);
            } catch (e) {
                console.warn('Error destroying DataTable:', e);
            }
        }

        // Clean up DOM
        $('#membersTable').removeClass('dataTable no-footer')
                         .removeAttr('style')
                         .css('width', '')
                         .unwrap();
        $('.dataTables_wrapper').remove();
        this.table = null;
    }

    restoreTableStructure() {
        const $table = $('#membersTable');

        // Ensure table responsive wrapper
        if (!$table.parent().hasClass('table-responsive')) {
            $table.wrap('<div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;"></div>');
        }

        // Reset table attributes
        $table.attr('id', 'membersTable')
              .removeClass('dataTable')
              .addClass('table table-bordered')
              .css('min-width', '1000px');
    }

    setupColumnFilters() {
        // Wait a bit to ensure DataTable is fully initialized
        setTimeout(() => {
            if (!this.table || !$.fn.DataTable.isDataTable('#membersTable')) {
                console.error('❌ Cannot setup filters: DataTable not properly initialized');
                return;
            }

            console.log('🔧 Setting up column filters for', this.table.columns().count(), 'columns');

            // Clear existing event handlers
            $('tfoot .filters input, tfoot .filters select').off('keyup change');

            // Setup filters for each column
            this.table.columns().every((index) => {
                const column = this.table.column(index);

                // Skip serial number column
                if (index === 0) return;

                const filterCell = $('tfoot .filters th').eq(index);
                const filterInput = filterCell.find('input, select');

                if (filterInput.length === 0) {
                    console.warn(`No filter input found for column ${index}`);
                    return;
                }

                console.log(`Setting up filter for column ${index}:`, filterInput.attr('placeholder') || filterInput.attr('name'));

                // Set initial value
                const currentSearch = column.search();
                if (currentSearch) {
                    filterInput.val(currentSearch);
                }

                // Add event handlers with custom logic for different column types
                filterInput.on('keyup change', function () {
                    const value = $(this).val();
                    console.log(`Filtering column ${index} with:`, value);

                    // Special handling for different column types
                    switch (index) {
                        case 3: // Total Groups column
                            if (value === '') {
                                column.search('').draw();
                            } else if (value === '4') {
                                // For 4+ groups, search for numbers >= 4
                                column.search('[4-9]|\\d{2,}', true, false).draw();
                            } else {
                                column.search(`^${value}$`, true, false).draw();
                            }
                            break;

                        case 4: // Active Loans column
                            if (value === '') {
                                column.search('').draw();
                            } else if (value === '3') {
                                // For 3+ loans, search for numbers >= 3
                                column.search('[3-9]|\\d{2,}', true, false).draw();
                            } else {
                                column.search(`^${value}$`, true, false).draw();
                            }
                            break;

                        case 5: // Total Balances column
                            if (value === '') {
                                column.search('').draw();
                            } else if (value === 'high') {
                                // Search for balances > 100,000
                                column.search('1[0-9]{5,}|[2-9]\\d{5,}', true, false).draw();
                            } else if (value === 'medium') {
                                // Search for balances between 50,000 and 100,000
                                column.search('[5-9]\\d{4}|100000', true, false).draw();
                            } else if (value === 'low') {
                                // Search for balances < 50,000
                                column.search('^[0-4]\\d{0,4}$|^[0-9]{1,4}$', true, false).draw();
                            } else {
                                column.search(value).draw();
                            }
                            break;

                        case 6: // Risk Level column
                            if (value === '') {
                                column.search('').draw();
                            } else {
                                // Search for the specific risk level in the HTML
                                const riskRegex = `badge-${value}`;
                                column.search(riskRegex, true, false).draw();
                            }
                            break;

                        default:
                            // Default behavior for other columns
                            if ($(this).is('select')) {
                                column.search(value).draw();
                            } else {
                                column.search(value).draw();
                            }
                    }
                });
            });

            console.log('✅ Column filters setup complete');
        }, 200);
    }

    clearColumnFilters() {
        console.log('🧹 Clearing all column filters...');

        $('tfoot .filters input').val('');
        $('tfoot .filters select').val('');

        if (this.table) {
            this.table.columns().search('').draw();
        }
    }

    initializeDataTable() {
        if (this.isInitializing) {
            console.log('⚠️ DataTable initialization already in progress');
            return;
        }

        console.log('🔄 Initializing DataTable...');
        this.isInitializing = true;

        // Hide the table and show loading placeholder
        $('#membersTable').hide();
        $('#table-loading-placeholder').show();

        this.showLoading();
        this.destroyDataTable();
        this.restoreTableStructure();

        // Ensure DOM is ready
        setTimeout(() => {
            try {
                const tableElement = $('#membersTable');

                if (tableElement.length === 0) {
                    throw new Error('Members table not found in DOM');
                }

                console.log('📊 Creating DataTable instance...');

                this.table = tableElement.DataTable({
                    "responsive": false,
                    "lengthChange": true,
                    "autoWidth": false,
                    "pageLength": 10,
                    "pagingType": "simple_numbers",
                    "ordering": true, // Enable ordering for better user experience
                    "order": [[3, 'desc'], [5, 'desc']], // Default sort by total groups desc, then balance desc
                    "destroy": true,
                    "retrieve": true,
                    "language": {
                        "search": "Global Search:",
                        "lengthMenu": "Show _MENU_ members",
                        "zeroRecords": "No multi-group members found",
                        "info": "Showing _START_ to _END_ of _TOTAL_ multi-group members",
                        "infoEmpty": "No multi-group members available",
                        "infoFiltered": "(filtered from _MAX_ total multi-group members)",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next",
                            "previous": "Previous"
                        }
                    },
                    "columnDefs": [
                        {
                            "targets": 0, // Serial number
                            "orderable": false,
                            "searchable": false
                        },
                        {
                            "targets": 1, // Member Details
                            "searchable": true
                        },
                        {
                            "targets": 2, // Groups
                            "searchable": true,
                            "orderable": false
                        },
                        {
                            "targets": 3, // Total Groups
                            "searchable": true,
                            "type": "num", // Treat as numeric for proper sorting
                            "orderable": true,
                            "render": function (data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    // Extract numeric value for sorting
                                    return $(data).find('.badge').text().match(/\d+/) || 0;
                                }
                                return data;
                            }
                        },
                        {
                            "targets": 4, // Active Loans
                            "searchable": true,
                            "type": "num", // Treat as numeric for proper sorting
                            "orderable": true,
                            "render": function (data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    // Extract numeric value for sorting
                                    return $(data).find('.badge').text().match(/\d+/) || 0;
                                }
                                return data;
                            }
                        },
                        {
                            "targets": 5, // Total Balances
                            "searchable": true,
                            "type": "num", // Treat as numeric for proper sorting
                            "orderable": true,
                            "render": function (data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    // Extract numeric value for sorting
                                    return data.replace(/[^0-9]/g, '') || 0;
                                }
                                return data;
                            }
                        },
                        {
                            "targets": 6, // Risk Level (HTML content)
                            "searchable": true,
                            "type": "html",
                            "orderable": true
                        }
                    ],
                    "dom": '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "buttons": [
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            title: 'Multi-Group Members Risk Analysis',
                            className: 'btn-success btn-sm',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6],
                                format: {
                                    body: function (data, row, column, node) {
                                        // Clean HTML from exported data
                                        if (column === 1) {
                                            // Extract text from member details
                                            return $(data).find('.member-name').text().trim() || data;
                                        }
                                        if (column === 2) {
                                            // Extract group names
                                            const groups = [];
                                            $(data).find('.group-badge').each(function() {
                                                groups.push($(this).text().trim());
                                            });
                                            return groups.join(', ');
                                        }
                                        if (column === 3 || column === 4) {
                                            // Extract numeric count
                                            return $(data).find('.badge').text().match(/\d+/) || '0';
                                        }
                                        if (column === 6) {
                                            // Extract risk level
                                            return $(data).find('.badge').first().text().trim() || data;
                                        }
                                        return data;
                                    }
                                }
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            title: 'Multi-Group Members Risk Analysis',
                            className: 'btn-danger btn-sm',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6],
                                format: {
                                    body: function (data, row, column, node) {
                                        if (column === 1) {
                                            return $(data).find('.member-name').text().trim() || data;
                                        }
                                        if (column === 2) {
                                            const groups = [];
                                            $(data).find('.group-badge').each(function() {
                                                groups.push($(this).text().trim());
                                            });
                                            return groups.join(', ');
                                        }
                                        if (column === 3 || column === 4) {
                                            return $(data).find('.badge').text().match(/\d+/) || '0';
                                        }
                                        if (column === 6) {
                                            return $(data).find('.badge').first().text().trim() || data;
                                        }
                                        return data;
                                    }
                                }
                            },
                            customize: function (doc) {
                                doc.content[1].table.widths =
                                    ['5%', '20%', '20%', '10%', '15%', '15%', '15%'];
                                doc.styles.tableHeader = {
                                    bold: true,
                                    fontSize: 10,
                                    color: 'white',
                                    fillColor: '#343a40',
                                    alignment: 'center'
                                };
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print"></i> Print',
                            title: '',
                            className: 'btn-info btn-sm',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6],
                                format: {
                                    body: function (data, row, column, node) {
                                        if (column === 1) {
                                            return $(data).find('.member-name').text().trim() || data;
                                        }
                                        if (column === 2) {
                                            const groups = [];
                                            $(data).find('.group-badge').each(function() {
                                                groups.push($(this).text().trim());
                                            });
                                            return groups.join(', ');
                                        }
                                        if (column === 3 || column === 4) {
                                            return $(data).find('.badge').text().match(/\d+/) || '0';
                                        }
                                        if (column === 6) {
                                            return $(data).find('.badge').first().text().trim() || data;
                                        }
                                        return data;
                                    }
                                }
                            },
                            customize: function (win) {
                                $(win.document.body).find('tfoot').remove();
                                $(win.document.body).prepend(
                                    '<h3 class="text-center">Multi-Group Members Risk Analysis</h3>'
                                );
                                $(win.document.body).find('table').addClass('table-bordered').css('font-size', '9px');
                                $(win.document.body).find('thead tr:first th').css({
                                    'background-color': '#343a40',
                                    'color': 'white',
                                    'font-weight': 'bold',
                                    'border': '1px solid #454d55'
                                });
                            }
                        }
                    ],
                    "initComplete": (settings, json) => {
                        console.log('✅ DataTable initComplete called');
                        this.isInitializing = false;

                        // Setup filters after initialization
                        this.setupColumnFilters();
                        this.updateRowNumbers();

                        setTimeout(() => {
                            // Hide loading placeholder and show the initialized table
                            $('#table-loading-placeholder').hide();
                            $('#membersTable').show();
                            this.hideLoading();
                            console.log('🎉 DataTable fully initialized and ready');
                        }, 300);
                    },
                    "drawCallback": () => {
                        this.updateRowNumbers();
                    },
                    "error": (settings, techNote, message) => {
                        console.error('❌ DataTable error:', message);
                        this.isInitializing = false;
                        // Show table even if there's an error
                        $('#table-loading-placeholder').hide();
                        $('#membersTable').show();
                        this.hideLoading();
                    }
                });

                // Double-check initialization
                setTimeout(() => {
                    if (!$.fn.DataTable.isDataTable('#membersTable')) {
                        console.error('❌ DataTable failed to initialize properly');
                        this.isInitializing = false;
                        $('#table-loading-placeholder').hide();
                        $('#membersTable').show();
                        this.hideLoading();
                    }
                }, 1000);

            } catch (error) {
                console.error('❌ Error initializing DataTable:', error);
                this.isInitializing = false;
                // Show table even if there's an error
                $('#table-loading-placeholder').hide();
                $('#membersTable').show();
                this.hideLoading();

                // Retry after delay
                setTimeout(() => {
                    console.log('🔄 Retrying DataTable initialization...');
                    this.initializeDataTable();
                }, 1000);
            }
        }, 150);
    }

    updateRowNumbers() {
        if (this.table) {
            try {
                this.table.rows({ search: 'applied' }).every((rowIdx, tableLoop, rowLoop) => {
                    const node = this.table.row(rowIdx).node();
                    if (node) {
                        $(node).find('td:first').html(rowIdx + 1);
                    }
                });
            } catch (error) {
                console.warn('Error updating row numbers:', error);
            }
        }
    }

    refreshDataTable() {
        console.log('🔄 Refreshing DataTable...');
        this.clearColumnFilters();

        // Hide table and show loading placeholder during refresh
        $('#membersTable').hide();
        $('#table-loading-placeholder').show();

        this.initializeDataTable();
    }

    resetFilters() {
        this.clearColumnFilters();
        this.showNotification('Filters reset', 'info');
    }

    // Check DataTable status
    checkStatus() {
        const status = {
            tableInstance: !!this.table,
            isDataTable: $.fn.DataTable.isDataTable('#membersTable'),
            isInitializing: this.isInitializing,
            tableElement: $('#membersTable').length,
            filterInputs: $('tfoot .filters input, tfoot .filters select').length
        };

        console.log('📊 DataTable Status:', status);
        return status;
    }
}

// Initialize global instance
const dataTableManager = new DataTableManager();

// Document ready with better error handling
$(document).ready(function() {
    console.log('📄 Document ready, initializing DataTable...');

    // Wait a bit for Livewire to settle
    setTimeout(() => {
        dataTableManager.initializeDataTable();
    }, 300);

    // View member button handler
    $(document).on('click', '.view-member', function (e) {
        const memberId = $(this).data('member-id');
        console.log('👁️ Viewing multi-group member:', memberId);
        $("#view-group-member-modal").modal('show');

        setTimeout(() => {
            console.log('Dispatching loadMemberData with:', memberId);
            Livewire.dispatch('loadMemberData', { memberId: memberId });
        }, 500);
    });

    // Reset filters button
    $(document).on('click', '#reset-filters', function() {
        dataTableManager.resetFilters();
    });

    // Debug button
    $(document).on('click', '#debug-status', function() {
        dataTableManager.checkStatus();
    });

    // Modal event handlers
    $('#view-group-member-modal').on('hidden.bs.modal', function () {
        Livewire.dispatch('memberModalClosed');
    });
});

// Livewire event listeners
document.addEventListener('livewire:init', function () {
    console.log('⚡ Livewire initialized');
});

document.addEventListener('members-refreshed', function (event) {
    console.log('📥 Members refreshed event received');

    const message = event.detail?.message && event.detail?.constituency
        ? `${event.detail.message} - ${event.detail.constituency}`
        : 'Multi-group members data refreshed successfully';

    dataTableManager.showNotification(message, 'success');

    // Refresh DataTable with sufficient delay
    setTimeout(() => {
        dataTableManager.refreshDataTable();
    }, 800);
});

document.addEventListener('groupMemberEdited', function (event) {
    if (!event.detail || typeof toastr === 'undefined') return;

    const messages = {
        'error': () => toastr.error('Error occurred please try again!!'),
        'success': () => {
            // Show success message first
            toastr.success('Member Edited Successfully');
            // Close modal and refresh after user sees the message
            setTimeout(() => {
                $("#view-group-member-modal").modal('hide');
                Livewire.dispatch('refresh-members');
            }, 1000);
        },
        'deleted': () => {
            // Show success message first
            toastr.success('Member Deleted Successfully');
            // Close modal and refresh after user sees the message
            setTimeout(() => {
                $("#view-group-member-modal").modal('hide');
                Livewire.dispatch('refresh-members');
            }, 1000);
        }
    };

    messages[event.detail.message]?.();
});

// Global access for debugging
window.dataTableManager = dataTableManager;
window.refreshTable = () => dataTableManager.refreshDataTable();
window.checkStatus = () => dataTableManager.checkStatus();
</script>
@endpush
