<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Groups Summary <code>(Youth, Women and PWDs)</code></h5>
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fa fa-users"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Total Groups</span>
                    <span class="info-box-number">{{ $totalgroups }}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: 40%"></div>
                    </div>
                    <span class="progress-description">
                         Loan return rate;
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fa fa-user-friends"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Youth Groups</span>
                    <span class="info-box-number">Total {{ $youths }}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalgroups > 0 ? number_format(($youths/$totalgroups)*100, 2) : 0 }}%"></div>
                    </div>
                    <span class="progress-description">
                        {{ $totalgroups > 0 ? number_format(($youths/$totalgroups)*100, 2) : 0}}% of Total Groups
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fa fa-female text-white"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Women Groups</span>
                    <span class="info-box-number">Total {{ $women }}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalgroups > 0 ? number_format(($women/$totalgroups)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description">
                        {{ $totalgroups > 0 ? number_format(($women/$totalgroups)*100, 2) : 0}}% of total groups
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fa fa-wheelchair"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">PWD Groups</span>
                    <span class="info-box-number">Total {{ $pwds }} </span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalgroups > 0 ? number_format(($pwds/$totalgroups)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description">
                        {{ $totalgroups > 0 ? number_format(($pwds/$totalgroups)*100, 2) : 0}}% of total groups
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
        </div>
        <!--/row -->

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Groups Information Data</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- MOBILE-FRIENDLY DATATABLE -->
                <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table id="groupsTable" class="table table-bordered" style="min-width: 800px;">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Group Name</th>
                                <th width="10%">GFS Code</th>
                                <th width="15%">Ward</th>
                                <th width="10%">Category</th>
                                <th width="15%">Activity</th>
                                <th width="10%">Identifier</th>
                                <th width="10%">Actions</th>
                            </tr>
                            <tr class="filters bg-light">
                                <th></th> <!-- No filter for serial number -->
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Group" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search GFS" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Ward" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Category" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Activity" /></th>
                                <th><input type="text" class="form-control form-control-sm" placeholder="Search Identifier" /></th>
                                <th></th> <!-- No filter for actions -->
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $index => $group)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-capitalize">{{ $group->name }}</td>
                                <td>{{ $group->gfs_code ?? '-' }}</td>
                                <td class="text-capitalize">{{ $group->ward->ward_name }}</td>
                                <td>
                                    <span class="badge badge-{{ $group->category == 'Youth' ? 'success' : ($group->category == 'Women' ? 'warning' : 'danger') }}">
                                        {{ $group->category }}
                                    </span>
                                </td>
                                <td>{{ $group->activity }}</td>
                                <td>{{ $group->identifier ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('const.groups.group', [
                                        'county' => $county->slug,
                                        'constituency' => $constituency->slug,
                                        'group' => $group->slug
                                    ]) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
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
        #groupsTable {
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

    .filters input {
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
</style>
@endpush

@push('scripts')
<script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/vfs_fonts.js') }}"></script>



<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#groupsTable').DataTable({
            "responsive": false,
            "lengthChange": true,
            "autoWidth": false,
            "pageLength": 10,
            "pagingType": "simple_numbers",
            "ordering": false,
            "language": {
                "search": "Global Search:",
                "lengthMenu": "Show _MENU_ groups",
                "zeroRecords": "No groups found",
                "info": "Showing _START_ to _END_ of _TOTAL_ groups",
                "infoEmpty": "No groups available",
                "infoFiltered": "(filtered from _MAX_ total groups)",
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
                    title: 'Groups Information - {{ $constituency->constituency_name }}',
                    className: 'btn-success btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    title: 'Groups Information - {{ $constituency->constituency_name }}',
                    className: 'btn-danger btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    customize: function (doc) {
                        doc.content[1].table.widths = ['5%', '20%', '10%', '15%', '10%', '15%', '10%'];
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
                    title: '<h3>Groups Information - {{ $constituency->constituency_name }}</h3>',
                    className: 'btn-info btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    customize: function (win) {
                        $(win.document.body).find('table').addClass('table-bordered').css('font-size', '10px');
                        $(win.document.body).find('h1').css('text-align','center');
                        $(win.document.body).find('tr.filters').remove();
                        $(win.document.body).find('thead tr:first th').css({
                            'background-color': '#343a40',
                            'color': 'white',
                            'font-weight': 'bold'
                        });
                    }
                }
            ],
            "columnDefs": [
                {
                    "targets": 0,
                    "orderable": false,
                    "searchable": false
                },
                {
                    "targets": -1,
                    "orderable": false,
                    "searchable": false
                }
            ],
            "initComplete": function () {
                // Apply individual column filtering
                this.api().columns().every(function () {
                    var column = this;

                    // Skip first and last columns
                    if (column.index() === 0 || column.index() === 7) {
                        return;
                    }

                    // Create input for filtering
                    $('input', $('.filters th').eq(column.index()))
                        .on('keyup change clear', function () {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                });

                // Initialize sequential numbering - SAFE VERSION
                updateRowNumbers();
            }
        });

        // Update row numbers when table is filtered/paged - SAFE VERSION
        table.on('search.dt page.dt draw.dt', function () {
            updateRowNumbers();
        });

        function updateRowNumbers() {
            // Check if table and column exist before accessing
            if (table && table.column) {
                try {
                    table.column(0, { search: 'applied' }).nodes().each(function (cell, i) {
                        if (cell) {
                            cell.innerHTML = i + 1;
                        }
                    });
                } catch (error) {
                    console.log('Error updating row numbers:', error);
                }
            }
        }

        // Add buttons container with mobile-friendly layout
        table.buttons().container()
            .addClass('btn-group btn-group-sm')
            .appendTo('#groupsTable_wrapper .col-md-4:eq(1)');

        // Mobile-specific adjustments
        if ($(window).width() < 768) {
            $('.dataTables_length, .dataTables_filter').addClass('mb-2');
        }
    });
</script>
@endpush
