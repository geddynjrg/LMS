<section class="content">
@hasrole('admin|super-admin')
    <div class="container-fluid">
      <h5 class="mt-4 mb-2">Add User </h5>
      <!-- card -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Create new user</h3>
        </div>

        <!-- /.card-header -->
        <div class="card-body border col-md-6 ml-auto mr-auto rounded m-5">
            <form wire:submit.prevent="create" class="">
                <!-- input states -->
                <div class="form-group">
                    <label class="col-form-label" for="name"> Input users's full name</label>
                    <input wire:model.defer="name" required type="text" class="form-control" id="name" placeholder="Enter ...">
                    @error('name')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="row">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label class="col-form-label" for="email"> Email</label>
                            <input wire:model.defer="email" required type="email" class="form-control" id="email" placeholder="Enter ...">
                        </div>
                        @error('email')
                            <span class="text-sm text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label class="col-form-label" for="role">Select User Role</label>
                            <select id="role" wire:model.defer="role" required class="form-control">
                                <option selected value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            @error('role')
                                <span class="text-sm text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-form-label" for="phone"> Phone Number</label>
                            <input wire:model.defer="phone_number" required type="text" class="form-control" id="phone" placeholder="Enter ...">
                            @error('phone_number')
                                <span class="text-sm text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-form-label" for="idnumber"> ID Number</label>
                            <input wire:model.defer="id_number" required type="text" class="form-control" id="idnumber" placeholder="Enter ...">
                            @error('id_number')
                                <span class="text-sm text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-form-label">Assign to Constituency(s)</label>
                            <div wire:ignore>
                                <select class="select2 form-control" wire:model="assignedConstituencies" multiple="multiple" data-placeholder="Select a State" style="width: 100%;">
                                    @foreach ($availableConstituencies as $constituency)
                                        <option value="{{ $constituency->constituency_code }}">{{ $constituency->constituency_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('assignedConstituencies')
                                <span class="text-sm text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-form-label" for="password"> Password</label>
                            <input wire:model.defer="password" required type="password" class="form-control" id="password" placeholder="Enter ...">
                            @error('password')
                                <span class="text-sm text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="btn-group float-right m-2" role="group">
                    <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                        <span wire:loading wire:target="create">Saving...</span>
                        <span wire:loading.remove wire:target="create"><i class="fas fa-save"></i> Add User </span>
                    </button>
                </div>
            </form>
        </div>
      </div>
      <!-- /card -->
    </div>
    <!-- /container -->

 @endhasrole
</section>

@push('styles')
   <!-- Select2 -->
  <link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
@endpush

@push('scripts')
  <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
  <script src="../../plugins/select2/js/select2.full.min.js"></script>
  <script>
     $(function () {
        //Initialize Select2 Elements
        $('.select2').select2()
        $('.select2').on('change', function () {
            let selectedValues = $(this).val();
            @this.set('assignedConstituencies', selectedValues);
        });
     })
    document.addEventListener('user-added', function (event) {
      toastr.success('Receipt Recorded Successfully.');
    });

  </script>


@endpush
