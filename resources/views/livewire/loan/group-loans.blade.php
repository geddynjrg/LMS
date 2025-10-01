<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Group loans Summary </h5>
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fa fa-calendar-check"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Approved Applications</span>
                    <span class="info-box-number">{{ $applications }}</span>

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
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fa fa-hand-holding-usd"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Total Disbursed</span>
                    <span class="info-box-number">Ksh {{ number_format(($amount_issued),2)}} </span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $amount_issued > 0 ? number_format(($amount_issued/$amount_issued)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description">

                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fa fa-envelope-open-text text-white"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Total Repayment</span>
                    <span class="info-box-number">Ksh {{ number_format(($amount_repaid),2)}}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $amount_issued > 0 ? number_format(($amount_repaid/$amount_issued)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description text-sm">
                        {{ $amount_issued > 0 ? number_format(($amount_repaid/$amount_issued)*100, 2) : 0}}% Return rate
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fa fa-search-dollar"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Balance</span>
                    <span class="info-box-number">Ksh {{ $amount_issued > 0 ? number_format(($amount_issued - $amount_repaid),2) : 0}} </span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $amount_issued > 0 ? number_format((($amount_issued - $amount_repaid)/$amount_issued)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description text-sm">
                        {{ $amount_issued > 0 ? number_format((($amount_issued - $amount_repaid)/$amount_issued)*100, 2) : 0}}% of disbursed
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
        </div>
        <!-- row -->

        <div class="card">
            <div class="card-header">
              <h3 class="card-title">Group Loans information data</h3>
            </div>
            <!-- /.card-header -->

            <div wire:ignore class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead class="bg-gradient-cyan">
                         <tr>
                            <th>#</th>
                            <th>Group</th>
                            <th>Ward</th>
                            <th>Phase</th>

                            <th>Status</th>
                            <th>Disbursement Date</th>
                            <th>Amount Issued</th>
                            <th>Amount repaid</th>
                            <th>Balance</th>
                            <th>Action</th>
                         </tr>
                    </thead>
                    <tbody>
                        @php
                            $i = 1;
                        @endphp
                        @foreach ($grouploans as $grouploan)
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td class="col-2 text-capitalize">{{ strtolower($grouploan->group->name) }}</td>
                            <td class="text-capitalize">{{ strtolower($grouploan->group->ward->ward_name) }}</td>
                            <td>{{ $grouploan->loanPhase->phase }}-{{ $grouploan->level}}</td>

                            <td>{{ $grouploan->status }}</td>
                            <td>{{ $grouploan->disbursement_date }}</td>
                            <td class="text-bold">{{ $amount_issued > 0 ? number_format($grouploan->amount_issued) : 0 }}</td>
                            <td class="text-bold">{{ $amount_issued > 0 ? number_format($grouploan->amount_repaid) : 0 }}</td>
                            @if (($grouploan->amount_issued - $grouploan->amount_repaid) <= 0)
                                <td class="text-bold text-success">{{ number_format($grouploan->amount_issued - $grouploan->amount_repaid) }}</td>
                                @else
                                <td class="text-bold">{{ number_format($grouploan->amount_issued - $grouploan->amount_repaid) }}</td>
                            @endif
                            <td>
                                <a href="{{route('const.loans.grouploan', [
                                    'county' => $county->slug,
                                    'constituency' => $constituency->slug,
                                    'group'=>$grouploan->group->slug],
                                    )}}" class="text-bold bg-fuchsia p-2 rounded-lg" href="#">View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Group</th>
                            <th>Ward</th>
                            <th>Phase</th>

                            <th>Status</th>
                            <th>Disbursement Date</th>
                            <th>Amount Issued</th>
                            <th>Amount repaid</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
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
    <script src="{{asset('plugins/datatables-buttons/js/buttons.colvis.min.js')}}"></script>

    <script>
        $(function () {
            var text = @this.constituency +' Constituency Group Loans Information Summary';
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

            table.draw();

            var buttonContainer = table.buttons().container();
            $('#example1_wrapper .col-md-6:eq(0)').append(buttonContainer);
        });

    </script>
@endpush
