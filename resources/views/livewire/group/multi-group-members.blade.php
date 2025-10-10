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
                                <th width="10%">Member Details</th>
                                <th width="10%">Groups</th>
                                <th width="10%">Total Groups</th>
                                <th width="15%">Active Loans</th>
                                <th width="15%">Total Arrears</th>
                                <th width="15%">Risk Level</th>
                            </tr>
                        </thead>
                        <!-- MOVED FILTER ROW TO tfoot FOR BETTER EXPORT HANDLING -->
                        {{-- <tfoot style="display: table-header-group;">
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
                                        <option value="">All Arrears</option>
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
                        </tfoot> --}}
                        <tfoot style="display: table-header-group;">
                            <tr class="filters bg-light">
                                <th></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Member" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Groups" /></th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All Groups</option>
                                        <option value="2">2 Groups</option>
                                        <option value="3">3 Groups</option>
                                        <option value="4">4+ Groups</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All Loans</option>
                                        <option value="0">No Loans</option>
                                        <option value="1">1 Loan</option>
                                        <option value="2">2 Loans</option>
                                        <option value="3">3+ Loans</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All Arrears</option>
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
                                                        {{ $group->risk_level === 'high' ? 'badge-danger' : ($group->risk_level === 'medium' ? 'badge-warning' : ($group->risk_level === 'low' ? 'badge-info' : 'badge-secondary')) }}
                                                        mr-1 mb-1">
                                                        {{ $group->name ?? 'Unknown Group' }}
                                                        @if($group->arrears > 0)
                                                            <br><small>KSh {{ number_format($group->arrears) }}</small>
                                                        @endif
                                                        @if($group->is_defaulter)
                                                            <br><small class="text-warning">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                                {{ $group->default_days }} days default
                                                            </small>
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
                                            $totalGroups = $member->group_count ?? 0;
                                            $groupBadgeClass = $totalGroups >= 4 ? 'badge-warning' : ($totalGroups == 3 ? 'badge-info' : 'badge-primary');
                                        @endphp
                                        <span class="badge {{ $groupBadgeClass }} p-2">
                                            <i class="fas fa-layer-group mr-1"></i>
                                            {{ $totalGroups }} Group(s)
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $activeLoans = $member->active_loans_count ?? 0;
                                            $loanBadgeClass = $activeLoans > 0 ? 'badge-warning' : 'badge-success';
                                        @endphp
                                        <span class="badge {{ $loanBadgeClass }} p-2">
                                            <i class="fas fa-file-invoice-dollar mr-1"></i>
                                            {{ $activeLoans }} Active Loan(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div class="arrears-info">
                                            <strong class="text-danger">KSh {{ number_format($member->total_arrears ?? 0) }}</strong>
                                            @if($member->high_risk_groups > 0)
                                                <div class="mt-1">
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        {{ $member->high_risk_groups }} high-risk group(s)
                                                    </small>
                                                </div>
                                            @endif
                                            @if($member->max_default_days > 0)
                                                <div class="mt-1">
                                                    <small class="text-warning">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        Max {{ $member->max_default_days }} days default
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

<script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
<script>
    // DataTable Manager - Fixed for Multi-Group Members
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

    // Extract numeric value from Total Groups HTML
    extractTotalGroups(html) {
        const match = html.match(/(\d+)\s*Group/);
        return match ? parseInt(match[1]) : 0;
    }

    // Extract numeric value from Active Loans HTML
    extractActiveLoans(html) {
        const match = html.match(/(\d+)\s*Active/);
        return match ? parseInt(match[1]) : 0;
    }

    // Extract numeric value from Total Arrears HTML
    extractTotalArrears(html) {
        const match = html.match(/KSh\s*([\d,]+)/);
        return match ? parseInt(match[1].replace(/,/g, '')) : 0;
    }

    // Extract risk level from Risk Level HTML
    extractRiskLevel(html) {
        const riskMatch = html.match(/(High|Medium|Low)\s*Risk/i);
        return riskMatch ? riskMatch[1].toLowerCase() : 'unknown';
    }

    // Extract risk score from Risk Level HTML
    extractRiskScore(html) {
        const scoreMatch = html.match(/Score:\s*(\d+)\/10/);
        return scoreMatch ? parseInt(scoreMatch[1]) : 0;
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

            // Store reference to this for use in event handlers
            const self = this;

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

                // Add event handlers with proper context
                filterInput.on('keyup change', function () {
                    const value = $(this).val();
                    console.log(`Filtering column ${index} with:`, value);

                    // Use the stored reference to access the table
                    if (self.table) {
                        // Custom filtering logic for different column types
                        switch (index) {
                            case 3: // Total Groups - Exact match
                                if (value === '') {
                                    self.table.column(index).search('').draw();
                                } else if (value === '2') {
                                    //self.table.column(index).search('\\b2\\b', true, false).draw();
                                    self.table.column(index).search('2\\s*Group\\(s\\)', true, false).draw();
                                } else if (value === '3') {
                                    // search for exactly 3 groups
                                    //self.table.column(index).search('\\b3\\b', true, false).draw();
                                    self.table.column(index).search('3\\s*Group\\(s\\)', true, false).draw();
                                }else if (value === '4') {
                                    // For 4+ groups, search for numbers >= 4
                                    self.table.column(index).search('([4-9]|\\d{2,})\\s*Group\\(s\\)', true, false).draw();
                                    //self.table.column(index).search('^[4-9]|\\d{2,}', true, false).draw();
                                } else {
                                    // Exact match for specific numbers
                                    self.table.column(index).search( value + '$', true, false).draw();
                                }
                                break;

                            case 4: // Active Loans - Exact match
                                if (value === '') {
                                    self.table.column(index).search('').draw();
                                } else if (value === '0') {
                                    self.table.column(index).search('0\\s*Active\\s*Loan\\(s\\)', true, false).draw();
                                } else if (value === '1') {
                                    self.table.column(index).search('1\\s*Active\\s*Loan\\(s\\)', true, false).draw();
                                } else if (value === '2') {
                                    self.table.column(index).search('2\\s*Active\\s*Loan\\(s\\)', true, false).draw();
                                } else if (value === '3') {
                                    // For 3+ loans, search for numbers >= 3
                                    self.table.column(index).search('([3-9]|\\d{2,})\\s*Active\\s*Loan\\(s\\)', true, false).draw();
                                } else {
                                    // Exact match for specific numbers
                                    self.table.column(index).search(value + '$', true, false).draw();
                                }
                                break;

                            case 5: // Total Arrears - Range matching
                                if (value === '') {
                                    self.table.column(index).search('').draw();
                                } else if (value === 'high') {
                                    self.table.column(index).search('KSh[^\\d]*(1\\d{2},?\\d{3}|[2-9]\\d{2},?\\d{3}|\\d{6,})', true, false).draw();
                                } else if (value === 'medium') {
                                    // Search for arrears between 50,000 and 100,000
                                    self.table.column(index).search('KSh[^\\d]*([5-9]\\d,?\\d{3})', true, false).draw();
                                } else if (value === 'low') {
                                    // Search for arrears < 50,000
                                    self.table.column(index)
                                    .search('KSh\\s*(?:(?:[0-9]{1,4})|(?:[1-4][0-9]?,\\d{3}))(?![\\d,])', true, false)
                                    .draw();
                                } else {
                                    self.table.column(index).search(value).draw();
                                }
                                break;

                            case 6: // Risk Level - Exact match
                                if (value === '') {
                                    self.table.column(index).search('').draw();
                                } else {
                                    self.table.column(index)
                                        .search('\\b' + value.replace(/\s+/g, '\\s*') + '\\b', true, false)
                                        .draw();

                                }
                                break;

                            default:
                                // Default behavior for other columns
                                self.table.column(index).search(value).draw();
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

                // Check if there are any data rows
                const hasData = $('#membersTable tbody tr:not(.dataTables_empty)').length > 0;

                if (!hasData) {
                    console.log('📊 No data rows found, initializing empty DataTable');
                    $('#table-loading-placeholder').hide();
                    $('#membersTable').show();
                    this.hideLoading();
                    this.isInitializing = false;
                    return;
                }

                console.log('📊 Creating DataTable instance...');

                this.table = tableElement.DataTable({
                    "responsive": false,
                    "lengthChange": true,
                    "autoWidth": false,
                    "pageLength": 10,
                    "pagingType": "simple_numbers",
                    "ordering": true,
                    "order": [[3, 'desc'], [5, 'desc']], // Sort by Total Groups desc, then Total Arrears desc
                    "destroy": true,
                    "retrieve": true,
                    "language": {
                        "search": "Global Search:",
                        "lengthMenu": "Show _MENU_ members",
                        "zeroRecords": "No multi-group members found matching your filters",
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
                    "dom": '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "buttons": [
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            title: 'Multi-Group Members Risk Analysis',
                            className: 'btn-success btn-sm',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6],
                                // format: {
                                //     body: function (data, row, column, node) {
                                //         // Extract clean text for export
                                //         if (column === 1) {
                                //             const $cell = $(data);
                                //             const name = $cell.find('.member-name').text().trim();
                                //             const phone = $cell.find('.fa-phone-alt').parent().text().trim();
                                //             const id = $cell.find('.fa-id-card').parent().text().trim();
                                //             return `${name}\n${phone}\n${id}`;
                                //         }
                                //         if (column === 2) {
                                //             const groups = [];
                                //             $(data).find('.group-badge').each(function() {
                                //                 groups.push($(this).text().trim());
                                //             });
                                //             return groups.join('\n');
                                //         }
                                //         if (column === 3) {
                                //             const match = $(data).find('.badge').text().match(/(\d+)\s*Group/);
                                //             return match ? match[1] : '0';
                                //         }
                                //         if (column === 4) {
                                //             const match = $(data).find('.badge').text().match(/(\d+)\s*Active/);
                                //             return match ? match[1] : '0';
                                //         }
                                //         if (column === 5) {
                                //             const match = $(data).find('.text-danger').first().text().match(/KSh\s*([\d,]+)/);
                                //             return match ? `KSh ${match[1]}` : 'KSh 0';
                                //         }
                                //         if (column === 6) {
                                //             const riskLevel = $(data).find('.badge').text().trim();
                                //             const scoreMatch = $(data).find('.text-muted').text().match(/(\d+)\/10/);
                                //             const score = scoreMatch ? scoreMatch[1] : '';
                                //             return score ? `${riskLevel} (${score}/10)` : riskLevel;
                                //         }
                                //         return $(data).text().trim();
                                //     }
                                // }
                                customizeData: function (data) {
                                    // Process data for Excel export to show actual values
                                    for (let i = 0; i < data.body.length; i++) {
                                        const row = data.body[i];

                                        // Column 1: Member Details - extract text
                                        if (row[1] && typeof row[1] === 'object') {
                                            const $cell = $('<div>').html(row[1].display);
                                            const name = $cell.find('.member-name').text().trim();
                                            const phone = $cell.find('.fa-phone-alt').parent().text().trim();
                                            const id = $cell.find('.fa-id-card').parent().text().trim();
                                            row[1] = `${name}\n${phone}\n${id}`;
                                        }

                                        // Column 2: Groups - extract group names
                                        if (row[2] && typeof row[2] === 'object') {
                                            const $cell = $('<div>').html(row[2].display);
                                            const groups = [];
                                            $cell.find('.group-badge').each(function() {
                                                groups.push($(this).text().trim());
                                            });
                                            row[2] = groups.join('\n');
                                        }

                                        // Column 3: Total Groups - extract number
                                        if (row[3] && typeof row[3] === 'object') {
                                            row[3] = row[3].sort; // Use the numeric value
                                        } else if (row[3]) {
                                            row[3] = dataTableManager.extractTotalGroups(row[3]);
                                        }

                                        // Column 4: Active Loans - extract number
                                        if (row[4] && typeof row[4] === 'object') {
                                            row[4] = row[4].sort;
                                        } else if (row[4]) {
                                            row[4] = dataTableManager.extractActiveLoans(row[4]);
                                        }

                                        // Column 5: Total Arrears - extract number
                                        if (row[5] && typeof row[5] === 'object') {
                                            row[5] = row[5].sort;
                                        } else if (row[5]) {
                                            row[5] = dataTableManager.extractTotalArrears(row[5]);
                                        }

                                        // Column 6: Risk Level - extract text and score
                                        if (row[6] && typeof row[6] === 'object') {
                                            const riskLevel = dataTableManager.extractRiskLevel(row[6].display);
                                            const riskScore = row[6].sort;
                                            row[6] = `${riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1)} Risk (${riskScore}/10)`;
                                        } else if (row[6]) {
                                            const riskLevel = dataTableManager.extractRiskLevel(row[6]);
                                            const riskScore = dataTableManager.extractRiskScore(row[6]);
                                            row[6] = `${riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1)} Risk (${riskScore}/10)`;
                                        }
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
                                            const $cell = $(data);
                                            const name = $cell.find('.member-name').text().trim();
                                            const phone = $cell.find('.fa-phone-alt').parent().text().trim();
                                            const id = $cell.find('.fa-id-card').parent().text().trim();
                                            return `${name}\n${phone}\n${id}`;
                                        }
                                        if (column === 2) {
                                            const groups = [];
                                            $(data).find('.group-badge').each(function() {
                                                groups.push($(this).text().trim());
                                            });
                                            return groups.join(', ');
                                        }
                                        if (column === 3) {
                                            const match = $(data).find('.badge').text().match(/(\d+)\s*Group/);
                                            return match ? match[1] : '0';
                                        }
                                        if (column === 4) {
                                            const match = $(data).find('.badge').text().match(/(\d+)\s*Active/);
                                            return match ? match[1] : '0';
                                        }
                                        if (column === 5) {
                                            const match = $(data).find('.text-danger').first().text().match(/KSh\s*([\d,]+)/);
                                            return match ? `KSh ${match[1]}` : 'KSh 0';
                                        }
                                        if (column === 6) {
                                            const riskLevel = $(data).find('.badge').text().trim();
                                            const scoreMatch = $(data).find('.text-muted').text().match(/(\d+)\/10/);
                                            const score = scoreMatch ? scoreMatch[1] : '';
                                            return score ? `${riskLevel} (${score}/10)` : riskLevel;
                                        }
                                        return $(data).text().trim();
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
                                doc.styles.tableBody = {
                                    fontSize: 9
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
                                            const $cell = $(data);
                                            const name = $cell.find('.member-name').text().trim();
                                            const phone = $cell.find('.fa-phone-alt').parent().text().trim();
                                            const id = $cell.find('.fa-id-card').parent().text().trim();
                                            return `${name}\n${phone}\n${id}`;
                                        }
                                        if (column === 2) {
                                            const groups = [];
                                            $(data).find('.group-badge').each(function() {
                                                groups.push($(this).text().trim());
                                            });
                                            return groups.join(', ');
                                        }
                                        if (column === 3) {
                                            const match = $(data).find('.badge').text().match(/(\d+)\s*Group/);
                                            return match ? match[1] : '0';
                                        }
                                        if (column === 4) {
                                            const match = $(data).find('.badge').text().match(/(\d+)\s*Active/);
                                            return match ? match[1] : '0';
                                        }
                                        if (column === 5) {
                                            const match = $(data).find('.text-danger').first().text().match(/KSh\s*([\d,]+)/);
                                            return match ? `KSh ${match[1]}` : 'KSh 0';
                                        }
                                        if (column === 6) {
                                            const riskLevel = $(data).find('.badge').text().trim();
                                            const scoreMatch = $(data).find('.text-muted').text().match(/(\d+)\/10/);
                                            const score = scoreMatch ? scoreMatch[1] : '';
                                            return score ? `${riskLevel} (${score}/10)` : riskLevel;
                                        }
                                        return $(data).text().trim();
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
    }, 500);

    // Reset filters button
    $(document).on('click', '#reset-filters', function() {
        dataTableManager.resetFilters();
    });

    // Debug button
    $(document).on('click', '#debug-status', function() {
        dataTableManager.checkStatus();
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
    }, 1000);
});

// Global access for debugging
window.dataTableManager = dataTableManager;
window.refreshTable = () => dataTableManager.refreshDataTable();
window.checkStatus = () => dataTableManager.checkStatus();
</script>
@endpush
