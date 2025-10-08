<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Group Members Summary</h5>
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fa fa-calendar-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Members</span>
                        <span class="info-box-number">{{ number_format($totalMembers) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">
                            All constituency members
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fa fa-female"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Female Members</span>
                        <span class="info-box-number">Total {{ number_format($female) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($female/$totalMembers)*100, 2) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $totalMembers > 0 ? number_format(($female/$totalMembers)*100, 2) : 0 }}% of Total Members
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fa fa-male text-white"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Male Members</span>
                        <span class="info-box-number">Total {{ number_format($male) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($male/$totalMembers)*100, 2) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $totalMembers > 0 ? number_format(($male/$totalMembers)*100, 2) : 0 }}% of total members
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fa fa-wheelchair"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">PWD Members</span>
                        <span class="info-box-number">Total {{ number_format($disabled) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($disabled/$totalMembers)*100, 2) : 0 }}%"></div>
                        </div>
                        <span class="progress-description">
                            {{ $totalMembers > 0 ? number_format(($disabled/$totalMembers)*100, 2) : 0 }}% of total members
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Members Information Data</h3>
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
                    <table id="membersTable" class="table table-bordered" style="min-width: 800px;">
                        <thead class="table-primary">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Member Name</th>
                                <th width="15%">ID Number</th>
                                <th width="15%">Contact</th>
                                <th width="10%">Gender</th>
                                <th width="10%">PWD</th>
                                <th width="5%">Actions</th>
                            </tr>
                        </thead>
                        <!-- MOVED FILTER ROW TO tfoot FOR BETTER EXPORT HANDLING -->
                        <tfoot style="display: table-header-group;">
                            <tr class="filters bg-light">
                                <th></th> <!-- No filter for serial number -->
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Name" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search ID" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Contact" /></th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All Genders</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </th>
                                <th>
                                    <select class="form-control form-control-sm">
                                        <option value="">All</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </th>
                                <th></th> <!-- No filter for actions -->
                            </tr>
                        </tfoot>
                        <tbody>
                            @php
                                // Debug: Check what we're working with
                                $membersType = gettype($members);
                                $isCountable = is_countable($members);
                                $membersCount = $isCountable ? count($members) : 0;
                            @endphp

                            @if($members && $isCountable && $membersCount > 0)
                                @foreach($members as $member)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-capitalize">{{ $member->name ?? 'N/A' }}</td>
                                    <td>{{ $member->member_Id ?? 'N/A' }}</td>
                                    <td>{{ $member->number ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ ($member->gender ?? '') == 'Male' ? 'primary' : 'success' }}">
                                            {{ $member->gender ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ ($member->disabled ?? false) ? 'danger' : 'secondary' }}">
                                            {{ ($member->disabled ?? false) ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($member->member_Id))
                                        <button type="button" class="btn btn-sm btn-primary view-member"
                                                data-member-id="{{ $member->member_Id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                    @if(!$members)
                                        No members data available.
                                    @elseif(!$isCountable)
                                        Invalid members data format (type: {{ $membersType }}).
                                    @else
                                        No members found for this constituency.
                                    @endif
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- View Member Modal -->
    <div class="modal fade" id="view-group-member-modal" tabindex="-1" role="dialog" aria-labelledby="viewMemberModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMemberModalLabel">Member Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @livewire('view-group-member', key('view-group-member'))
                </div>
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

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .table-responsive {
            border: 1px solid #dee2e6;
            -webkit-overflow-scrolling: touch;
        }
        #membersTable {
            min-width: 800px; /* Force horizontal scroll on mobile */
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
    min-width: 800px;
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
// DataTable Manager - Optimized with Card Loading and Fixed Filters
// class DataTableManager {
//     constructor() {
//         this.table = null;
//         this.initToastr();
//     }

//     initToastr() {
//         if (typeof toastr !== 'undefined') {
//             toastr.options = {
//                 "closeButton": true,
//                 "progressBar": true,
//                 "positionClass": "toast-top-right",
//                 "timeOut": "5000",
//                 "extendedTimeOut": "1000"
//             };
//         }
//     }

//     showNotification(message, type = 'success') {
//         if (typeof toastr !== 'undefined') {
//             toastr[type](message);
//         }
//         console.log(`${type.toUpperCase()}: ${message}`);
//     }

//     showLoading() {
//         const $card = $('.card:has(#membersTable)');
//         if ($card.length === 0) return;

//         // Remove existing overlay
//         this.hideLoading();

//         // Create loading overlay specific to the card
//         $card.css('position', 'relative').append(`
//             <div id="datatable-loading-overlay" style="
//                 position: absolute;
//                 top: 0;
//                 left: 0;
//                 width: 100%;
//                 height: 100%;
//                 background: rgba(255,255,255,0.9);
//                 z-index: 999;
//                 display: flex;
//                 align-items: center;
//                 justify-content: center;
//                 border-radius: 0.25rem;
//             ">
//                 <div class="text-center">
//                     <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
//                     <div class="mt-2 text-muted">Loading members data...</div>
//                 </div>
//             </div>
//         `);

//         // Disable interaction with the card content
//         $card.find('.card-body').css('pointer-events', 'none');
//     }

//     hideLoading() {
//         const $card = $('.card:has(#membersTable)');
//         $('#datatable-loading-overlay').fadeOut(300, function() {
//             $(this).remove();
//         });

//         // Re-enable interaction
//         $card.find('.card-body').css('pointer-events', 'auto');
//     }

//     destroyDataTable() {
//         if (!this.table) return;

//         try {
//             if ($.fn.DataTable.isDataTable('#membersTable')) {
//                 this.table.clear().destroy(true);
//             }
//         } catch (e) {
//             console.warn('Error destroying DataTable:', e);
//         } finally {
//             // Cleanup DOM but preserve filter inputs
//             $('#membersTable').removeClass('dataTable no-footer')
//                              .removeAttr('style')
//                              .css('width', '')
//                              .unwrap();
//             $('.dataTables_wrapper').remove();
//             this.table = null;
//         }
//     }

//     restoreTableStructure() {
//         const $table = $('#membersTable');

//         if (!$table.parent().hasClass('table-responsive')) {
//             $table.wrap('<div class="table-responsive" style="overflow-x: auto;"></div>');
//         }

//         $table.attr('id', 'membersTable')
//               .removeClass('dataTable')
//               .addClass('table table-bordered')
//               .css('min-width', '800px');
//     }

//     setupColumnFilters() {
//         if (!this.table) {
//             console.warn('Cannot setup filters: DataTable not initialized');
//             return;
//         }

//         console.log('🔧 Setting up column filters...');

//         // Clear any existing event handlers first
//         $('tfoot .filters input, tfoot .filters select').off('keyup change');

//         // Apply the search for each column
//         this.table.columns().every((index) => {
//             const column = this.table.column(index);

//             // Skip first and last columns (serial number and actions)
//             if (index === 0 || index === 6) return;

//             const filterInput = $('tfoot .filters th').eq(index).find('input, select');

//             if (filterInput.length === 0) {
//                 console.warn(`No filter input found for column ${index}`);
//                 return;
//             }

//             // Set initial value from column search
//             const currentSearch = column.search();
//             if (currentSearch) {
//                 filterInput.val(currentSearch);
//             }

//             // Add event handlers
//             filterInput.on('keyup change', function () {
//                 const value = $(this).val();

//                 // For select elements, search exact match
//                 if ($(this).is('select')) {
//                     column.search(value).draw();
//                 } else {
//                     // For input elements, search contains
//                     column.search(value).draw();
//                 }

//                 console.log(`Filtering column ${index}:`, value);
//             });

//             console.log(`✅ Filter setup for column ${index}:`, filterInput.attr('placeholder') || filterInput.prop('tagName'));
//         });
//     }

//     clearColumnFilters() {
//         console.log('🧹 Clearing all column filters...');

//         // Clear all filter inputs
//         $('tfoot .filters input').val('');
//         $('tfoot .filters select').val('');

//         // Clear DataTable searches if table exists
//         if (this.table) {
//             this.table.columns().search('').draw();
//         }
//     }

//     initializeDataTable() {
//         console.log('🔄 Initializing DataTable...');

//         // Show loading spinner on card only
//         this.showLoading();

//         // Destroy existing instance
//         this.destroyDataTable();

//         // Restore table structure
//         this.restoreTableStructure();

//         // Initialize with delay to ensure DOM is ready
//         setTimeout(() => {
//             try {
//                 this.table = $('#membersTable').DataTable({
//                     "responsive": false,
//                     "lengthChange": true,
//                     "autoWidth": false,
//                     "pageLength": 10,
//                     "pagingType": "simple_numbers",
//                     "ordering": false,
//                     "destroy": true,
//                     "retrieve": true,
//                     "language": {
//                         "search": "Global Search:",
//                         "lengthMenu": "Show _MENU_ members",
//                         "zeroRecords": "No members found",
//                         "info": "Showing _START_ to _END_ of _TOTAL_ members",
//                         "infoEmpty": "No members available",
//                         "infoFiltered": "(filtered from _MAX_ total members)",
//                         "paginate": {
//                             "first": "First",
//                             "last": "Last",
//                             "next": "Next",
//                             "previous": "Previous"
//                         },
//                         "loadingRecords": "Loading...",
//                         "processing": "Processing..."
//                     },
//                     "dom": '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
//                     "buttons": [
//                         {
//                             extend: 'excel',
//                             text: '<i class="fas fa-file-excel"></i> Excel',
//                             title: 'Members Information',
//                             className: 'btn-success btn-sm',
//                             exportOptions: {
//                                 columns: [0, 1, 2, 3, 4, 5],
//                                 format: {
//                                     header: function (data, column, node) {
//                                         return data;
//                                     }
//                                 }
//                             }
//                         },
//                         {
//                             extend: 'pdf',
//                             text: '<i class="fas fa-file-pdf"></i> PDF',
//                             title: 'Members Information',
//                             className: 'btn-danger btn-sm',
//                             exportOptions: {
//                                 columns: [0, 1, 2, 3, 4, 5],
//                                 format: {
//                                     header: function (data, column, node) {
//                                         return data;
//                                     }
//                                 }
//                             },
//                             customize: function (doc) {
//                                 doc.content[1].table.widths = ['5%', '20%', '15%', '15%', '10%', '10%'];
//                                 doc.styles.tableHeader = {
//                                     bold: true,
//                                     fontSize: 10,
//                                     color: 'white',
//                                     fillColor: '#343a40',
//                                     alignment: 'center'
//                                 };
//                                 doc.content.splice(0, 0, {
//                                     text: 'Members Information',
//                                     style: 'header'
//                                 });
//                             }
//                         },
//                         {
//                             extend: 'print',
//                             text: '<i class="fas fa-print"></i> Print',
//                             title: '',
//                             className: 'btn-info btn-sm',
//                             exportOptions: {
//                                 columns: [0, 1, 2, 3, 4, 5],
//                                 format: {
//                                     header: function (data, column, node) {
//                                         return data;
//                                     }
//                                 }
//                             },
//                             customize: function (win) {
//                                 $(win.document.body).find('tfoot').remove();
//                                 $(win.document.body).prepend(
//                                     '<h3 class="text-center">Members Information</h3>'
//                                 );
//                                 $(win.document.body).find('table').addClass('table-bordered').css('font-size', '10px');
//                                 $(win.document.body).find('thead tr:first th').css({
//                                     'background-color': '#343a40',
//                                     'color': 'white',
//                                     'font-weight': 'bold',
//                                     'border': '1px solid #454d55'
//                                 });
//                             }
//                         }
//                     ],
//                     "columnDefs": [
//                         {
//                             "targets": 0, // Serial number
//                             "orderable": false,
//                             "searchable": false
//                         },
//                         {
//                             "targets": 1, // Name
//                             "searchable": true
//                         },
//                         {
//                             "targets": 2, // ID Number
//                             "searchable": true
//                         },
//                         {
//                             "targets": 3, // Contact
//                             "searchable": true
//                         },
//                         {
//                             "targets": 4, // Gender
//                             "searchable": true
//                         },
//                         {
//                             "targets": 5, // PWD
//                             "searchable": true
//                         },
//                         {
//                             "targets": 6, // Actions
//                             "orderable": false,
//                             "searchable": false
//                         }
//                     ],
//                     "initComplete": (settings, json) => {
//                         console.log('✅ DataTable initialized, setting up filters...');
//                         this.setupColumnFilters();
//                         this.updateRowNumbers();

//                         // Hide loading spinner
//                         setTimeout(() => this.hideLoading(), 300);
//                     },
//                     "drawCallback": () => {
//                         this.updateRowNumbers();
//                     }
//                 });

//             } catch (error) {
//                 console.error('❌ Error initializing DataTable:', error);
//                 this.hideLoading();

//                 // Retry once after delay
//                 setTimeout(() => {
//                     if (!$.fn.DataTable.isDataTable('#membersTable')) {
//                         this.initializeDataTable();
//                     }
//                 }, 500);
//             }
//         }, 100);
//     }

//     updateRowNumbers() {
//         if (this.table) {
//             this.table.rows({ search: 'applied' }).every((rowIdx) => {
//                 const node = this.table.row(rowIdx).node();
//                 if (node) {
//                     $(node).find('td:first').html(rowIdx + 1);
//                 }
//             });
//         }
//     }

//     refreshDataTable() {
//         console.log('🔄 Refreshing DataTable...');

//         // Clear filters before refresh to start fresh
//         this.clearColumnFilters();
//         this.initializeDataTable();
//     }

//     // Method to reset filters
//     resetFilters() {
//         this.clearColumnFilters();
//         if (this.table) {
//             this.table.draw();
//         }
//     }

//     // Debug method to check filter status
//     debugFilters() {
//         if (!this.table) {
//             console.log('❌ DataTable not initialized');
//             return;
//         }

//         console.log('🔍 Filter Debug Info:');
//         this.table.columns().every((index) => {
//             const column = this.table.column(index);
//             const filterInput = $('tfoot .filters th').eq(index).find('input, select');
//             console.log(`Column ${index}:`, {
//                 search: column.search(),
//                 filterValue: filterInput.val(),
//                 filterExists: filterInput.length > 0
//             });
//         });
//     }
// }

// // Initialize global instance
// const dataTableManager = new DataTableManager();

// // Document ready handler
// $(document).ready(function() {
//     // Initialize DataTable on page load
//     dataTableManager.initializeDataTable();

//     // View member button handler
//     $(document).on('click', '.view-member', function (e) {
//         const memberId = $(this).data('member-id');
//         $("#view-group-member-modal").modal('show');

//         setTimeout(() => {
//             Livewire.dispatch('loadMemberData', { memberId: memberId });
//         }, 500);
//     });

//     // Add reset filters button handler (optional)
//     $(document).on('click', '#reset-filters', function() {
//         dataTableManager.resetFilters();
//         dataTableManager.showNotification('Filters reset', 'info');
//     });

//     // Modal event handlers
//     $('#view-group-member-modal').on('hidden.bs.modal', function () {
//         Livewire.dispatch('memberModalClosed');
//     });

//     // Test filter functionality
//     $(document).on('click', '#test-filters', function() {
//         dataTableManager.debugFilters();
//     });
// });

// // Event listeners
// document.addEventListener('members-refreshed', function (event) {
//     console.log('📥 Members refreshed event:', event);

//     const message = event.detail?.message && event.detail?.constituency
//         ? `${event.detail.message} - ${event.detail.constituency}`
//         : 'Data refreshed successfully';

//     dataTableManager.showNotification(message, 'success');

//     // Refresh DataTable with delay
//     setTimeout(() => {
//         dataTableManager.refreshDataTable();
//     }, 600);
// });

// document.addEventListener('groupMemberEdited', function (event) {
//     if (!event.detail || typeof toastr === 'undefined') return;

//     const messages = {
//         'error': () => toastr.error('Error occurred please try again!!'),
//         'success': () => {
//             toastr.success('Member Edited Successfully');
//             $('#view-group-member-modal').on('hidden.bs.modal', function () {
//                 window.location.reload();
//             });
//         },
//         'deleted': () => {
//             toastr.success('Member Deleted Successfully');
//             $("#view-group-member-modal").modal('hide');
//             setTimeout(() => window.location.reload(), 1000);
//         }
//     };

//     messages[event.detail.message]?.();
// });

// // Global access for debugging
// window.dataTableManager = dataTableManager;
// window.refreshTable = () => dataTableManager.refreshDataTable();
// window.debugFilters = () => dataTableManager.debugFilters();
// DataTable Manager - Fixed Initialization and Filters
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
                    <div class="mt-2 text-muted">Loading members data...</div>
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
              .css('min-width', '800px');
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

                // Skip serial number (0) and actions (6) columns
                if (index === 0 || index === 6) return;

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

                // Add event handlers
                // filterInput.on('keyup change', function () {
                //     const value = $(this).val();
                //     console.log(`Filtering column ${index} with:`, value);

                //     // Special case for gender column (index 4) - contains HTML badges
                //     if (index === 4) {
                //         // DataTables can usually search HTML content by default
                //         column.search(value).draw();
                //     } else if ($(this).is('select')) {
                //         column.search(value).draw();
                //     } else {
                //         column.search(value).draw();
                //     }
                // });

                filterInput.on('keyup change', function () {
                    const value = $(this).val();
                    console.log(`Filtering column ${index} with:`, value);

                    // Special case for gender column (index 4) - contains HTML badges
                    if (index === 4) {
                        if (value === '') {
                            // Show all when no filter selected
                            column.search('').draw();
                        } else {
                            // Force exact match for gender to avoid "male" matching "female"
                            // Use regex to match the exact gender text within the HTML
                            const exactMatchRegex = `\\b${value}\\b`;
                            column.search(exactMatchRegex, true, false).draw();
                        }
                    } else if ($(this).is('select')) {
                        column.search(value).draw();
                    } else {
                        column.search(value).draw();
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
                    "ordering": false,
                    "destroy": true,
                    "retrieve": true,
                    "language": {
                        "search": "Global Search:",
                        "lengthMenu": "Show _MENU_ members",
                        "zeroRecords": "No members found",
                        "info": "Showing _START_ to _END_ of _TOTAL_ members",
                        "infoEmpty": "No members available",
                        "infoFiltered": "(filtered from _MAX_ total members)",
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
                            "targets": 1, // Name
                            "searchable": true
                        },
                        {
                            "targets": 2, // ID Number
                            "searchable": true
                        },
                        {
                            "targets": 3, // Contact
                            "searchable": true
                        },
                        {
                            "targets": 4, // Gender (HTML content)
                            "searchable": true,
                            "type": "html" // This tells DataTables to search the text content
                        },
                        {
                            "targets": 5, // PWD (HTML content)
                            "searchable": true,
                            "type": "html" // This tells DataTables to search the text content
                        },
                        {
                            "targets": 6, // Actions
                            "orderable": false,
                            "searchable": false
                        }
                    ],
                    "dom": '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"B><"col-sm-12 col-md-4"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "buttons": [
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            title: 'Members Information',
                            className: 'btn-success btn-sm',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            title: 'Members Information',
                            className: 'btn-danger btn-sm',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print"></i> Print',
                            title: '',
                            className: 'btn-info btn-sm',
                            exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                        }
                    ],
                    "initComplete": (settings, json) => {
                        console.log('✅ DataTable initComplete called');
                        this.isInitializing = false;

                        // Setup filters after initialization
                        this.setupColumnFilters();
                        this.updateRowNumbers();

                        setTimeout(() => {
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
                        this.hideLoading();
                    }
                });

                // Double-check initialization
                setTimeout(() => {
                    if (!$.fn.DataTable.isDataTable('#membersTable')) {
                        console.error('❌ DataTable failed to initialize properly');
                        this.isInitializing = false;
                        this.hideLoading();
                    }
                }, 1000);

            } catch (error) {
                console.error('❌ Error initializing DataTable:', error);
                this.isInitializing = false;
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
        console.log('👁️ Viewing member:', memberId);
        $("#view-group-member-modal").modal('show');

        setTimeout(() => {
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
        : 'Data refreshed successfully';

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
            toastr.success('Member Edited Successfully');
            $('#view-group-member-modal').on('hidden.bs.modal', function () {
                window.location.reload();
            });
        },
        'deleted': () => {
            toastr.success('Member Deleted Successfully');
            $("#view-group-member-modal").modal('hide');
            setTimeout(() => window.location.reload(), 1000);
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
