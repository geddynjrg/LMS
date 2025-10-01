<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Members loans Summary </h5>
        <div class="row">
            <div class="col-md-4 col-sm-6 col-12">
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fa fa-calendar-check"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Total Members</span>
                    <span class="info-box-number">{{ $totalMembers }}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">

                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-4 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fa fa-female"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Female</span>
                    <span class="info-box-number">{{ number_format(($female),2)}} </span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($female/$totalMembers)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description">
                        {{ $totalMembers > 0 ?  number_format(($female/$totalMembers)*100, 2) : 0}}% of total members.
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-4 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fa fa-male text-white"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Male</span>
                    <span class="info-box-number">{{ number_format(($male),2)}}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalMembers > 0 ?  number_format(($male/$totalMembers)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description text-sm">
                        {{ $totalMembers > 0 ?  number_format(($male/$totalMembers)*100, 2) : 0}}% of total members.
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
        </div>
        <!-- row -->

        <!-- card -->
        <div class="card">
            <div class="card-header">
              <h3 class="card-title">Members Loans information data</h3>
            </div>
            <!-- /.card-header -->

            <div class="card-body">
                <div wire:ignore class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead class="bg-gradient-cyan">
                             <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>ID No.</th>
                                <th>Contact</th>
                                <th>Gender</th>
                                <th>Group</th>
                                <th>Role</th>
                                <th>Phase</th>
                                <th>Loan Issued</th>
                                <th>Balance</th>
                             </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = 1;
                            @endphp
                            @foreach ($members as $member)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td class="col-3 text-capitalize">{{ strtolower($member->member->name) }}</td>
                                <td>{{ $member->member->member_Id }}</td>
                                <td>{{ $member->member->number }}</td>
                                <td>{{ $member->member->gender }}</td>
                                <td class="col-3 text-capitalize">{{ strtolower($member->grouploan->group->name) }}</td>
                                <td>{{ strtolower($member->role) }} </td>
                                <td>{{ $member->grouploan->loanPhase->phase }}</td>
                                <td class="text-bold">{{ number_format($member->grouploan->amount_issued) }}</td>
                                @if (($member->grouploan->amount_issued - $member->grouploan->amount_repaid) <= 0)
                                    <td class="text-bold text-success">{{ number_format($member->grouploan->amount_issued - $member->grouploan->amount_repaid) }}</td>
                                    @else
                                    <td class="text-bold">{{ number_format($member->grouploan->amount_issued - $member->grouploan->amount_repaid) }}</td>
                                @endif


                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>ID No.</th>
                                <th>Contact</th>
                                <th>Gender</th>
                                <th>Group</th>
                                <th>Role</th>
                                <th>Phase</th>
                                <th>Loan Issued</th>
                                <th>Balance</th>
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

        $(function () {
            var text = ' Constituency Group Loans Information Summary';
            const table = new DataTable('#example1', {
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                buttons: [
                        {
                            extend: 'print',
                            title: text,
                            text: 'Print',

                            footer: true
                        },
                        {
                            extend: 'csv',
                            title: text,
                            text: 'CSV',

                            footer: true
                        },
                        {
                            extend: 'pdf',
                            title: text,
                            text: 'Pdf',

                            footer: true
                        },
                        {
                            extend: 'excelHtml5',
                            title: text,
                            text: 'Excel',

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

            table.draw();

            var buttonContainer = table.buttons().container();
            $('#example1_wrapper .col-md-6:eq(0)').append(buttonContainer);
        });

    </script>
@endpush
