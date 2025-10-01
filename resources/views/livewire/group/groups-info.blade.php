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
              <h3 class="card-title">Groups information data</h3>
            </div>
            <!-- /.card-header -->

            <div class="card-body">
                <div wire:ignore class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                       <thead class="bg-gradient-cyan">
                            <tr>
                                <th>S/No.</th>
                                <th>Group</th>
                                <th>GFS Code</th>
                                <th>Ward</th>
                                <th>Identifier</th>
                                <th>Category</th>
                                <th>Activity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $group)
                                <tr>
                                    <td>{{ $group->code }}</td>
                                    <td class="col-3 text-capitalize">{{ strtolower($group->name) }}</td>
                                    <td class="col-3">{{ $group->gfs_code }}</td>
                                    <td class="text-capitalize">{{ strtolower($group->ward->ward_name) }}</td>
                                    <td>{{ $group->identifier }}</td>
                                    <td>{{ $group->category }}</td>
                                    <td>{{ $group->activity }}</td>
                                    <td><a class="text-bold bg-fuchsia p-2 rounded-lg" href="{{route('const.groups.group', [
                                    'county' => $county->slug,
                                    'constituency' => $constituency->slug,
                                    'group' => $group->slug
                                    ])}}">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>S/No.</th>
                                <th>Group</th>
                                <th>GFS Code</th>
                                <th>Ward</th>
                                <th>Identifier</th>
                                <th>Category</th>
                                <th>Activity</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>



@push('scripts')
    <script src="{{asset('plugins/jszip/jszip.min.js')}}"></script>
    <script src="{{asset('plugins/pdfmake/pdfmake.min.js')}}"></script>
    <script src="{{asset('plugins/pdfmake/vfs_fonts.js')}}"></script>
    <script src="{{asset('plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
    <script src="{{asset('plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>

    <script>
        var text = ' Constituency Members Information Summary';

        /*$(function () {
            $("#example1").DataTable({
                "responsive": true,
                "lengthChane": false,
                "autoWidth": true,
                "buttons": ["copy", "csv", "excel", "pdf", "print"],
            }).buttons().container().appendTo("#example1_wrapper .col-md-6:eq(0)");
        })*/
        $(function () {
            var text = @this.constituency +' Constituency Groups Information Summary';
            const table = new DataTable('#example1', {
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                buttons: [
                        {
                            extend: 'print',
                            title: text,
                            text: 'Print',
                            exportOptions: {
                                columns: [':not(:last-child)']
                            },
                            footer: true
                        },
                        {
                            extend: 'csv',
                            title: text,
                            text: 'CSV',
                            exportOptions: {
                                columns: [':not(:last-child)']
                            },
                            footer: true
                        },
                        {
                            extend: 'pdf',
                            title: text,
                            text: 'Pdf',
                            exportOptions: {
                                columns: [':not(:last-child)']
                            },
                            footer: true
                        },
                        {
                            extend: 'excelHtml5',
                            title: text,
                            text: 'Excel',
                            exportOptions: {
                                columns: [':not(:last-child)']
                            },
                            footer: true
                        },

                    ],
                columnDefs: [
                    {
                        searchable: false,
                        orderable: false,
                        targets: 0
                    }
                ],

                processing: true,
            });

            /*table.on('order.dt search.dt', function () {
                    let i = 1;

                    table
                        .cells(null, 0, { search: 'applied', order: 'applied' })
                        .every(function (cell) {
                            this.data(i++);
                        });
                });*/

            table.draw();

            var buttonContainer = table.buttons().container();
            $('#example1_wrapper .col-md-6:eq(0)').append(buttonContainer);
        });

    </script>
@endpush
