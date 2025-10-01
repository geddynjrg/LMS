@extends('layouts.main')

@section('main-content')
    <section class="content-header">
        <div class="container-fluid">
          <a class="float btn btn-sm btn-primary" href="{{ url()->previous() }}"><i class="fas fa-step-backward p-1"></i>Back</a>
            <div class="row mb-2">
                <div class="col-sm-6">
                <h1>Profile</h1>
                </div>
                <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item "><a href="#">User Profile</a></li>
                    <li class="breadcrumb-item active"><a href="#"></a></li>
                </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->

        <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
          <div class="row">
            
            <!-- /.col -->
            <div class="col">
              <div class="card">
                <div class="card-header p-2">
                  <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">Profile Information</a></li>
                    <li class="nav-item"><a class="nav-link" href="#password" data-toggle="tab">Password</a></li>
                    <li class="nav-item"><a class="nav-link" href="#sessions" data-toggle="tab">Browser Sessions</a></li>
                    <li class="nav-item"><a class="nav-link" href="#delete" data-toggle="tab">Delete Account</a></li>
                  </ul>
                </div><!-- /.card-header -->
                <div class="card-body">
                  <div class="tab-content">
                    
                    <div class="active tab-pane" id="activity">
                        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                            <p>Update your account's profile information and email address.</p>
                            @livewire('profile.update-profile-information-form')
                        @endif
                    </div>
                    <!-- /.tab-pane -->

                    <div class="tab-pane" id="password">
                        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                            <p>Ensure your account is using a long, random password to stay secure.</p>
                            @livewire('profile.update-password-form')
                        @endif
                    </div>
                    <!-- /.tab-pane -->
  
                    <div class="tab-pane" id="sessions">
                      <p>Manage and log out your active sessions on other browsers and devices.</p>
                      @livewire('profile.logout-other-browser-sessions-form')
                    </div>
                    <!-- /.tab-pane -->

                    <div class="tab-pane" id="delete">
                      @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                        @livewire('profile.delete-user-form')
                      @endif
                    </div>
                  </div>
                  <!-- /.tab-content -->
                </div><!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->
        </div><!-- /.container-fluid -->
      </section>
      <!-- /.content -->
    </section>
@endsection
@push('styles')
   <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script>
      document.addEventListener('loggedOut', function (event) {
        $("#modal-logout").addClass('fade').modal('hide');
        toastr.success('Sessions Logged-out Successfully.');
      });

      document.addEventListener('saved', function (event) {
        toastr.success('Updated Successfully.');
      });
    </script>
@endpush
