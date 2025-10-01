<!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle"
                       src="{{$user->profile_photo_url}}"
                       alt="User profile picture">
                </div>

                <h3 class="profile-username text-center">{{ucwords(strtolower($user->name))}}</h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Email</b> <a class="float-right">{{$user->email}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Phone Number</b> <a class="float-right">{{$user->phone_number}}</a>
                  </li>

                  <li class="list-group-item">
                    <b>ID Number</b> <a class="float-right">{{$user->id_number}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Role</b> <a class="float-right">{{$user->roles->pluck('name')->join(', ')}}</a>
                  </li>
                </ul>

                <button class="btn btn-danger btn-block"><b><i class="fas fa-trash-alt"></i> Delete Account</b></button>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->


          </div>
          <!-- /.col -->
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a wire:loading.attr="disabled" class="nav-link {{ $activeTab === 'activity' ? 'active' : '' }}" wire:click="$set('activeTab', 'activity')">Activity</a></li>
                  <li class="nav-item"><a wire:loading.attr="disabled" class="nav-link {{ $activeTab === 'permissions' ? 'active' : '' }} " href="#" wire:click="$set('activeTab', 'permissions')" >Permissions</a></li>
                  <li class="nav-item"><a wire:loading.attr="disabled" class="nav-link {{ $activeTab === 'constituencies' ? 'active' : '' }}" href="#" wire:click="$set('activeTab', 'constituencies')">Constituencies</a></li>
                  <li class="nav-item"><a wire:loading.attr="disabled" class="nav-link {{ $activeTab === 'update' ? 'active' : '' }}" href="#" wire:click="$set('activeTab', 'update')">User Update</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <!-- Loading indicator -->
                <div wire:loading>
                    <div class="text-center my-4 mr-auto ml-auto">
                        <div class="spinner-border text-primary" role="status">
                        </div>
                        <div>Loading content...</div>
                    </div>
                </div>
                <div wire:loading.remove>
                    <div class="tab-content">
                    <div class="tab-pane fade {{ $activeTab === 'activity' ? 'show active' : '' }}" id="activity">
                        <div class="timeline-scroll-wrapper" style="max-height: 500px; overflow-y: auto;">
                            <div class="timeline timeline-inverse">
                                @foreach($activities->groupBy(function($item) {
                                    return \Carbon\Carbon::parse($item->created_at)->format('d M Y h:i A');
                                }) as $date => $logs)
                                <div class="time-label">
                                    <span class="bg-danger">{{ $date }}</span>
                                </div>

                                @foreach($logs as $log)
                                <div>
                                    <img class="profile-user-img img-fluid img-circle"
                                        src="{{$user->profile_photo_url}}"
                                        alt="User profile picture" style="
                                        text-align: center;
                                        height: 30px;
                                        width: 30px;
                                        position: absolute;
                                        left: 18px;
                                        top:0;
                                        background-color: #fff;
                                    ">
                                    <div class="timeline-item">
                                        <span class="time"><i class="far fa-clock"></i> {{ $log->created_at->format('H:i') }}</span>

                                        <h3 class="timeline-header">
                                            @php
                                                $causer = $log->causer?->name ?? 'System';
                                                $modelName = class_basename($log->subject_type);
                                                $subject = $log->subject; // actual model instance
                                                $subjectLabel = '';

                                                if ($modelName === 'User') {
                                                    $subjectLabel = $subject?->name ?? 'a user';
                                                } elseif ($modelName === 'Group') {
                                                    $subjectLabel = $subject?->name ?? 'a group';
                                                } elseif ($modelName === 'Member') {
                                                    $subjectLabel = $subject?->name ?? 'a member';
                                                } else {
                                                    $subjectLabel = 'a ' . strtolower($modelName);
                                                }
                                            @endphp

                                            <strong>{{ $log->description }}</strong> {{ strtolower($modelName) }} <strong>{{ $subjectLabel }}</strong>
                                        </h3>

                                        <div class="timeline-body">
                                            @php
                                                $changes = $log->properties?->toArray();
                                                $new = $changes['attributes'] ?? [];
                                                $old = $changes['old'] ?? [];
                                            @endphp

                                            @if (!empty($old))
                                                <ul class="mb-0">
                                                    @foreach ($old as $key => $oldValue)
                                                        @php
                                                            $newValue = $new[$key] ?? null;
                                                        @endphp
                                                        <li>
                                                            <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                            "{{ $oldValue }}" → "{{ $newValue }}"
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                No field changes recorded.
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                @endforeach
                                @endforeach

                                <div>
                                <i class="far fa-clock bg-gray"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane fade {{ $activeTab === 'permissions' ? 'show active' : '' }}" id="permissions">

                        <h5 class="mb-3">Manage Permissions for <strong>{{ $user->name }}</strong></h5>
                        <form wire:submit.prevent>
                            <div class="row">
                                @foreach ($categorizedPermissions as $category => $permissions)
                                    <div class="col-md-4">
                                        <div class="card card-outline card-primary mb-3">
                                            <div class="card-header">
                                                <strong>{{ $category }}</strong>
                                            </div>
                                            <div class="card-body">
                                                @foreach ($permissions as $permission)
                                                    <div class="form-check">
                                                        <input
                                                            wire:click="togglePermission('{{ $permission }}')"
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            id="{{ Str::slug($permission) }}"
                                                            {{ in_array($permission, $userPermissions) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="{{ Str::slug($permission) }}">
                                                            {{ ucfirst($permission) }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->

                    <div class="tab-pane fade {{ $activeTab === 'constituencies' ? 'show active' : '' }}" id="constituencies">
                        <!-- The timeline -->
                        <form wire:submit.prevent>
                            <div class="row">
                                @foreach ($availableConstituencies as $constituency)
                                    <div class="col-md-4">
                                        <div class="form-check mb-2">
                                            <input
                                                wire:click="toggleConstituency('{{ $constituency->constituency_code }}')"
                                                type="checkbox"
                                                class="form-check-input"
                                                id="constituency-{{ $constituency->constituency_code }}"
                                                {{ in_array($constituency->constituency_code, $userConstituencies) ? 'checked' : '' }}
                                            >
                                            <label class="form-check-label" for="constituency-{{ $constituency->constituency_code }}">
                                                {{ $constituency->constituency_name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->

                    <div class="tab-pane fade {{ $activeTab === 'update' ? 'show active' : '' }}" id="update">
                        <form wire:submit.prevent="updateUser" class="">
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
                                            <option>Select...</option>
                                            <option value="user">User</option>
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
                            <div class="btn-group float-right m-2" role="group">
                                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                                    <span wire:loading wire:target="updateUser">Updating...</span>
                                    <span wire:loading.remove wire:target="updateUser"><i class="fas fa-save"></i> Update User </span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->
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

@push('styles')
   <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush

@push('scripts')
  <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
  <script>
    // After Livewire update (listen for browser event)
    document.addEventListener('user-updated', function () {
        toastr.success('user updated successfully');
    });

  </script>
@endpush
