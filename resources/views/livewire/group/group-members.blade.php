<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Group Members Summary </h5>
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

                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fa fa-female"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Female</span>
                    <span class="info-box-number">{{ number_format(($female))}} </span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($female/$totalMembers)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description text-sm">
                        {{ $totalMembers > 0 ? number_format(($female/$totalMembers)*100, 2) : 0}}% of total members.
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fa fa-male text-white"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">Male</span>
                    <span class="info-box-number">{{ number_format(($male))}}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($male/$totalMembers)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description text-sm">
                        {{ $totalMembers > 0 ? number_format(($male/$totalMembers)*100, 2) : 0}}% of total members.
                    </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-gradient-gray">
                    <span class="info-box-icon"><i class="fa fa-male text-white"></i></span>

                    <div class="info-box-content">
                    <span class="info-box-text">PWD</span>
                    <span class="info-box-number">{{ number_format(($disabled))}}</span>

                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $totalMembers > 0 ? number_format(($disabled/$totalMembers)*100, 2) : 0}}%"></div>
                    </div>
                    <span class="progress-description text-sm">
                        {{ $totalMembers > 0 ? number_format(($disabled/$totalMembers)*100, 2) : 0}}% of total members.
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
                    <table id="example1" class="table table-bordered table-striped w-100">
                        <thead class="bg-gradient-cyan">
                             <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>ID No.</th>
                                <th>Contact</th>
                                <th>Gender</th>
                                <th>Disabled</th>
                                <th>More</th>
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
                                <td>{{ $member->member->disabled?'Yes':'No'}}</td>
                                <td class="col-2 text-center">

                                </td>


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
                                <th>Disabled</th>
                                <th>More</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!--View Member Modal -->
    <div  class="modal fade" id="view-group-member-modal">
        <div class="modal-dialog modal-lg">
            <livewire:view-group-member :constituency="$constituency" />
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.view group member modal -->
</section>
@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script src="{{asset('plugins/pdfmake/pdfmake.min.js')}}"></script>
    <script src="{{asset('plugins/pdfmake/vfs_fonts.js')}}"></script>
    <script src="{{asset('plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
    <script src="{{asset('plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>

    <script>

        $(function () {
            var text = ' Constituency Group Loans Information Summary';
            const table = new DataTable('#example1', {
                scrollX: true,
                lengthChange: true,
                autoWidth: true,
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
                columnDefs: [
                    {
                    data: null,
                    defaultContent: '<button type="button" class="btn btn-sm btn-primary mr-2" id="view"><span><i class="fas fa-eye"></i> View</span></button>',
                    targets: -1
                    }
                ],
            });

            table.draw();
            table.on('click', '#view', function (e) {
                let data = table.row($(this).closest('tr')).data();
               // document.getElementById('memberName').innerHTML = data[1].replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
                Livewire.dispatch('group-member-info', { data: data});
                console.log(data)
                $("#view-group-member-modal").modal('show');
            });

            var buttonContainer = table.buttons().container();
            $('#example1_wrapper .col-md-6:eq(0)').append(buttonContainer);
        });


        document.addEventListener('groupMemberEdited', function (event) {

            switch (event.detail.message) {
                case 'error':
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    toastr.success('Member Edited Successfully');
                    // Listen to the hidden.bs.modal event
                    $('#view-group-member-modal').on('hidden.bs.modal', function (e) {
                        location.reload();
                    });
                    break;
                case 'deleted':
                    toastr.success('Member Deleted Successfully');
                    $("#view-group-member-modal").addClass('fade').modal('hide');
                    $('#view-group-member-modal').on('hidden.bs.modal', function (e) {
                        location.reload();
                    });
                    break;
                default:
                    break;
            }
        });
    </script>
@endpush
