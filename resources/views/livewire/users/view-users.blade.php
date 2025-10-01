<section class="content">
@hasrole('admin|super-admin')
    <div class="container-fluid">
      <h5 class="mt-4 mb-2">App Users </h5>
      <!-- card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Users Information</h3>
          @can('create user')
          <a href="{{route('users.adduser', [Str::slug($county->slug),])}}" class="btn btn-primary mr-2 float-right">
            <span><i class="fas fa-plus"></i> Add New User </span>
          </a>
          @endcan
        </div>
        <!-- /.card-header -->
        <div wire:ignore class="card-body">


            <table id="example1" class="table table-bordered table-striped">
                <thead class="bg-gradient-cyan">
                    <tr>
                       <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>ID number</th>
                        <th>Phone number</th>
                        <th>Role</th>
                        <th>id</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $i = 1;
                    @endphp
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td class="col-2 text-capitalize">{{ strtolower($user->name) }}</td>
                            <td>{{ strtolower($user->email) }}</td>
                            <td>{{ $user->id_number }}</td>

                            <td>{{ $user->phone_number }}</td>
                            <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                            <td>{{ $user->id }}</td>
                            <td>
                                @can('update user')
                                <button type="button" id="edituser" class="btn btn-sm p-0 m-0" data-id="{{ $user->id }}">
                                    <i class="fas fa-edit text-primary"></i>
                                </button> /
                                <a href="{{route('users.viewuser', [Str::slug($county->slug), Str::slug($user->slug)])}}" class="nav-link {{ Route::currentRouteName() == 'users.viewusers' ? 'active':''}}">
                                    <i class="fas fa-eye nav-icon"></i>
                                    <p>View Users</p>
                                </a>
                                @endcan

                                @can('delete user')
                                <button type="button" id="deleteuser" class="btn btn-sm p-0 ml-1" data-id="{{ $user->id }}">
                                    <i class="fas fa-trash-alt text-danger"></i>
                                </button>
                                @endcan /

                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>ID number</th>
                        <th>Phone number</th>
                        <th>Role</th>
                        <th>id</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>

        </div>
      </div>
      <!-- /card -->
    </div>
    <!-- /container -->
  <!--edit user Modal -->
  <div wire:ignore class="modal fade" id="modal-edit-user">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="user_edit">Edit User</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <livewire:edit-user-data >
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.edit user modal -->

    <!--delete user Modal -->
  <div wire:ignore class="modal fade" id="modal-delete-user">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="user_delete">Remove User</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <livewire:delete-user-data >
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.delete user modal -->
 @endhasrole
</section>

@push('styles')
   <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush

@push('scripts')
  <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
  <script src="{{asset('plugins/jszip/jszip.min.js')}}"></script>
  <script src="{{asset('plugins/pdfmake/pdfmake.min.js')}}"></script>
  <script src="{{asset('plugins/pdfmake/vfs_fonts.js')}}"></script>
  <script src="{{asset('plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
  <script src="{{asset('plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
  <script src="{{asset('plugins/datatables-buttons/js/buttons.colvis.min.js')}}"></script>
<script>
    $(function () {
        console.log('livewire load')
        let table;

        function initDataTable() {
            let text = @this.constituency + ' Constituency Group Loans Information Summary';

            table = new DataTable('#example1', {
                destroy: true, // allow reinitialization
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                buttons: [
                    {
                        extend: 'print',
                        title: text,
                        text: 'Print',
                        exportOptions: {
                            columns: [':not(:last-child):not(:nth-last-child(2))']
                        },
                        footer: true
                    },
                    {
                        extend: 'csv',
                        title: text,
                        text: 'CSV',
                        exportOptions: {
                            columns: [':not(:last-child):not(:nth-last-child(2))']
                        },
                        footer: true
                    },
                    {
                        extend: 'pdf',
                        title: text,
                        text: 'Pdf',
                        exportOptions: {
                            columns: [':not(:last-child):not(:nth-last-child(2))']
                        },
                        footer: true
                    },
                    {
                        extend: 'excelHtml5',
                        title: text,
                        text: 'Excel',
                        exportOptions: {
                            columns: [':not(:last-child):not(:nth-last-child(2))']
                        },
                        footer: true
                    },
                ],
                columnDefs: [
                    {
                        searchable: false,
                        orderable: false,
                        targets: 0
                    },
                    {
                        searchable: false,
                        orderable: false,
                        targets: -1
                    },
                    {
                        targets: -2,
                        visible: false,
                        searchable: false
                    }
                ],
                processing: true,
            });

            table.on('click', '#edituser', function (e) {
                let row = table.row($(this).closest('tr')).data();
                let user = {
                    id: row[6],
                    name: row[1],
                    email: row[2],
                    id_number: row[3],
                    phone_number: row[4],
                    role: row[5],
                };

                Livewire.dispatch('edit-user-data', { user: user });
                $("#modal-edit-user").modal('show');
            });

            table.on('click', '#deleteuser', function (e) {
                let row = table.row($(this).closest('tr')).data();
                let user = {
                    id: row[6],
                    name: row[1],
                    email: row[2],
                    id_number: row[3],
                    phone_number: row[4],
                    role: row[5],
                };
                Livewire.dispatch('delete-user-data', { user: user });
                $("#modal-delete-user").modal('show');
            });

            $('#example1_wrapper .col-md-6:eq(0)').append(table.buttons().container());
        }

        initDataTable(); // first run
    });
</script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#modal-edit-user').on('hidden.bs.modal', function () {
            Livewire.dispatch('edit-user-modal-closed');
        });
        $('#modal-delete-user').on('hidden.bs.modal', function () {
            Livewire.dispatch('delete-user-modal-closed');
        });
    });



    document.addEventListener('user-deleted', function () {
        // wait for DOM changes by Livewire
        $("#modal-delete-user").addClass('fade').modal('hide');
        toastr.success('user deleted successfully');
        location.reload();
    });
    document.addEventListener('user-unknown', function () {
        // wait for DOM changes by Livewire
        $("#modal-delete-user").addClass('fade').modal('hide');
        toastr.error('user not found');
    });

  </script>
    @if (session()->has('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                toastr.success(@js(session('success')));
            });
        </script>
    @endif

@endpush
