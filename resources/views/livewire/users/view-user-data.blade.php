<section class="content">
    @hasrole('super-admin|constituency_officer')
        <div class="container-fluid">
            <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-0 font-weight-bold text-dark">
                                <i class="fas fa-user text-info mr-2"></i>
                                User Management
                            </h3>
                            <p class="text-muted mb-0">{{ $user->name }} - {{ $user->email }}</p>
                        </div>
                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                <span class="badge badge-info px-3 py-2 shadow-sm">
                                <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                                </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="card card-primary card-outline shadow-sm card-hover">
                        <div class="card-body box-profile">
                            <div class="text-center position-relative">
                                <img class="profile-user-img img-fluid img-circle border-3 border-{{ $user->status ? 'success' : 'danger' }}"
                                    src="{{$user->profile_photo_url}}" alt="User profile picture">
                                <span class="position-absolute translate-middle p-2 {{ $user->status ? 'bg-success' : 'bg-danger' }} border border-light rounded-circle"
                                    style="bottom: 5px; right: 35%;"></span>
                            </div>

                            <h3 class="profile-username text-center font-weight-bold mt-3 mb-0">{{ ucwords(strtolower($user->name)) }}</h3>
                            <p class="text-muted text-center mb-3">{{ $user->roles->pluck('name')->first() }}</p>

                            <div class="bg-light rounded p-3 mb-3">
                                <small class="text-muted d-block mb-1"><i class="fas fa-envelope mr-1"></i> Email</small>
                                <span class="d-block font-weight-600 mb-2">{{$user->email}}</span>

                                <small class="text-muted d-block mb-1"><i class="fas fa-phone mr-1"></i> Phone</small>
                                <span class="d-block font-weight-600 mb-2">{{$user->phone_number ?? 'N/A'}}</span>

                                <small class="text-muted d-block mb-1"><i class="fas fa-id-card mr-1"></i> ID Number</small>
                                <span class="d-block font-weight-600">{{$user->id_number}}</span>
                            </div>

                            <x-loading-button
                                title="{{ $user->status ? 'Deactivate' : 'Reactivate'}}"
                                target="updateUserStatus"
                                class="btn-outline-info btn-block shadow-sm"
                                icon="fas fa-file-excel"
                                label="{{ $user->status ? 'Deactivate' : 'Reactivate'}} Account"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card shadow-sm border-0" x-data="{ tab: @entangle('activeTab') }">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs nav-fill" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-3" :class="tab === 'activity' ? 'active font-weight-bold border-top-info' : 'text-muted'"
                                    href="#" @click.prevent="tab = 'activity'">
                                    <i class="fas fa-history mr-2"></i> Activity
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-3" :class="tab === 'permissions' ? 'active font-weight-bold border-top-info' : 'text-muted'"
                                    href="#" @click.prevent="tab = 'permissions'">
                                    <i class="fas fa-shield-alt mr-2"></i> Permissions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-3" :class="tab === 'update' ? 'active font-weight-bold border-top-info' : 'text-muted'"
                                    href="#" @click.prevent="tab = 'update'">
                                    <i class="fas fa-user-edit mr-2"></i> Settings
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content mt-2">
                                {{-- Use wire:key on each tab panel and x-cloak to prevent flicker --}}
                                <div x-show="tab === 'activity'"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    wire:key="activity-tab-{{ $user->id }}">
                                    @include('livewire.users.partials._activity_log')
                                </div>

                                <div x-show="tab === 'permissions'"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    wire:key="permissions-tab-{{ $user->id }}"
                                    wire:ignore.self>
                                    @include('livewire.users.partials._permissions_grid')
                                </div>

                                <div x-show="tab === 'update'"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    wire:key="update-tab-{{ $user->id }}"
                                    >
                                    @include('livewire.users.partials._user_account_info')
                                    @include('livewire.users.partials._user_system_info')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    @endhasrole
</section>

@push('styles')
   <style>
        /* Smooth transitions for tabs */
        .tab-pane { transition: opacity 0.3s ease-in-out; }


        /* Custom Switch Styling */
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
        }

        .profile-user-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.addEventListener('url-updated', event => {
            const newUrl = event.detail.url || event.detail[0].url;
            window.history.replaceState(null, null, newUrl);
        });
    </script>
@endpush
