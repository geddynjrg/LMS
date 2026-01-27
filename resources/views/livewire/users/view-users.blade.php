<section class="content">

@hasrole('super-admin|constituency_officer')
    <div class="container-fluid">
        @hasrole('super-admin')
        <div class="row mb-4 align-items-end">
            <div class="col-md-7">
                <h1 class="m-0 text-dark font-weight-bold display-5">
                    <i class="fas fa-users-cog mr-2 text-cyan"></i>
                    User Management
                </h1>
                <div class="mt-2 d-flex align-items-center">
                    <span class="text-muted small">
                        <i class="fas fa-id-badge mr-1"></i> System Staff:
                        <b class="text-dark">{{ $users->count() }} Accounts</b>
                    </span>
                    <span class="mx-2 text-light">|</span>
                    <span class="text-muted small">
                        <i class="fas fa-shield-alt mr-1"></i> Managed by:
                        <b class="text-dark">Super Admin</b>
                    </span>
                </div>
            </div>

            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                <div class="btn-group shadow-sm">
                    @can('create user')
                    <button type="button"
                            class="btn btn-primary btn-sm"
                            onclick="Livewire.dispatch('open-add-user-modal',
                                {
                                    component: 'add-user-data',
                                    arguments: { county_code: '{{ auth()->user()->county_code }}' }
                                })"
                            data-toggle="modal"
                            data-target="#modal-add-user">
                        <i class="fas fa-user-plus mr-1"></i> Add New User
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-left-primary h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">HQ Officers</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">
                            {{ $stats['hq'] }}
                        </div>
                        <hr class="my-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-primary font-weight-bold">Active</span>
                                <small class="text-muted text-xs">Reviewers</small>
                            </div>
                            <i class="fas fa-building text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-success h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Field Officers</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">
                            {{ $stats['field'] }}
                        </div>
                        <hr class="my-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted small">Constituency Level</span>
                            <i class="fas fa-map-marked-alt text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-danger h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">System Admins</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">
                            {{ $stats['admins'] }}
                        </div>
                        <hr class="my-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted small">Full Access Control</span>
                            <i class="fas fa-user-shield text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-info h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Login Security</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">
                            {{ $users->whereNotNull('email_verified_at')->count() }}
                        </div>
                        <hr class="my-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-info font-weight-bold">{{ $users->whereNull('email_verified_at')->count() }}</span>
                                <small class="text-muted text-xs">Unverified</small>
                            </div>
                            <i class="fas fa-key text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endhasrole

        <div class="col mb-4">
            <h1 class="m-0 text-dark font-weight-bold display-5">
                <i class="fas fa-users-cog mr-2 {{ auth()->user()->hasRole('super-admin') ? 'text-cyan' : 'text-success' }}"></i>
                @if(auth()->user()->hasRole('super-admin'))
                    County User Directory
                @else
                    {{ $constituencyName ?? 'Constituency' }} Staff Registry
                @endif
            </h1>
            <div class="mt-2 d-flex align-items-center">
                <span class="badge badge-pill {{ auth()->user()->hasRole('super-admin') ? 'badge-info' : 'badge-success' }} mr-3 px-3">
                    {{ auth()->user()->hasRole('super-admin') ? $county->county_name . ' County' : 'Local Access' }}
                </span>
                <span class="text-muted small">
                    <i class="fas fa-user-friends mr-1"></i> Visible Accounts:
                    <b class="text-dark">{{ $users->count() }}</b>
                </span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-left-info h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Managed Staff</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                        <hr class="my-2">
                        <div class="small text-muted">Users within your jurisdiction</div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->hasRole('super-admin'))
            <div class="col-md-4">
                <div class="card shadow-sm border-left-purple h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-purple text-uppercase mb-1">HQ Reviewers</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">{{ $stats['hq'] }}</div>
                        <hr class="my-2">
                        <div class="small text-muted">National level access</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users-cog mr-2"></i>System User Management
                    </h6>
                    <small class="text-muted">Manage system access and monitor user activities</small>
                </div>
                <button class="btn btn-primary btn-sm px-3 shadow-sm ml-auto"
                    onclick="Livewire.dispatch('open-add-user-modal', { component: 'add-user-data', arguments: { county_code: '{{ auth()->user()->county_code }}' } })">
                    <i class="fas fa-plus-circle mr-1"></i> Register User
                </button>
            </div>

            <div class="card-body">
                <div class="row g-2 mb-4 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-merge">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="text" class="form-control border-left-0 bg-light" placeholder="Search by name, email, or ID...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="roleFilter" class="form-control custom-select bg-light">
                            <option value="">📂 All Roles</option>
                            <option value="constituency_officer">Constituency Officers</option>
                            <option value="intern">Interns</option>
                            @hasrole('super-admin')
                            <option value="hq_officer">HQ Officer</option>
                            @endhasrole
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="statusFilter" class="form-control custom-select bg-light">
                            <option value="">🔔 All Status</option>
                            <option value="1">Active Accounts</option>
                            <option value="0">Deactivated</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        @if($search || $roleFilter || $statusFilter !== '')
                            <button wire:click="resetFilters" class="btn btn-link btn-sm text-danger decoration-none">
                                <i class="fas fa-times-circle"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-top-0 px-3">User Identity</th>
                                <th class="border-top-0">Assigned Jurisdiction</th>
                                <th class="border-top-0 text-center">Security Role</th>
                                <th class="border-top-0">Account Status</th>
                                <th class="border-top-0 text-right px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td class="px-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px; font-weight: 600;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-dark font-weight-bold mb-0">{{ ucwords($user->name) }}</div>
                                            <span class="text-muted extra-small"><i class="far fa-envelope mr-1"></i>{{ $user->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @forelse($user->constituencies as $con)
                                        <span class="badge badge-light border text-primary px-2 py-1 mb-1">
                                            <i class="fas fa-map-marker-alt mr-1"></i>{{ $con->constituency_name }}
                                        </span>
                                    @empty
                                        <span class="badge badge-light text-muted italic px-2 py-1">HQ / Unassigned</span>
                                    @endforelse
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $user->hasRole('constituency_officer') ? 'badge-primary' : 'badge-info' }} px-3 py-1 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                        {{ str_replace('_', ' ', $user->getRoleNames()->first() ?? 'No Role') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch" title="Toggle active/inactive">
                                        <input type="checkbox" class="custom-control-input" id="status-{{ $user->id }}"
                                            {{ $user->status ? 'checked' : '' }}
                                            wire:click="toggleStatus({{ $user->id }})">
                                        <label class="custom-control-label cursor-pointer" for="status-{{ $user->id }}">
                                            <span class="{{ $user->status ? 'text-success' : 'text-danger' }} small font-weight-bold">
                                                {{ $user->status ? 'Active' : 'Locked' }}
                                            </span>
                                        </label>
                                    </div>
                                </td>
                                <td class="text-right px-3">
                                    <div class="btn-group shadow-sm rounded">
                                        <button class="btn btn-white btn-sm text-info border-right"
                                            wire:click="$dispatch('open-view-user-modal', { userId: {{ $user->id }} })"
                                            title="View Activity & Logs">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-white btn-sm text-warning border-right" title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button class="btn btn-white btn-sm text-primary" title="Edit Profile">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-search-minus fa-3x text-light mb-3"></i>
                                    <h5 class="text-muted">No users found</h5>
                                    <p class="small text-muted">Try adjusting your filters or search keywords.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} records
                    </div>
                    {{ $users->links() }}
                </div>
            </div>
        </div>

      {{-- <h5 class="mt-4 mb-2">App Users </h5>
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
      <!-- /card --> --}}
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


                    @livewire('add-user-data', ['county' => $county])

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
