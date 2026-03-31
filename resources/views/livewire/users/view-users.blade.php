<section class="content">

@hasrole('super-admin|constituency_officer')
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            Users Management
                        </h3>
                        <p class="text-muted mb-0">Manage System Users</p>
                        <div class="mt-2 d-flex align-items-center">
                            <span class="text-muted small">
                                <i class="fas fa-id-badge mr-1"></i> System Staff:
                                <b class="text-dark">{{ $this->stats['total'] }} Accounts</b>
                            </span>
                            <span class="mx-2 text-light">|</span>
                            <span class="text-muted small">
                                <i class="fas fa-shield-alt mr-1"></i> Managed by:
                                <b class="text-dark">{{ $isSuperAdmin ? 'Super Admin' : 'Constituency Officer'}}</b>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-info px-3 py-2 shadow-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            {{-- Total Users --}}
            <div class="col-sm col-6 m-2">
                <x-stat-card
                    label="Total Users"
                    :value="$this->stats['total']"
                    icon="fa-users"
                    color="primary"
                    text="All registered users under your jurisdiction"
                />
            </div>

            {{-- Active Users --}}
            <div class="col-sm col-6 m-2">
                <x-stat-card
                    label="Active Users"
                    :value="$this->stats['active']"
                    icon="fa-user-check"
                    color="success"
                    text="Enabled accounts"
                />
            </div>

            {{-- Deactivated Users --}}
            <div class="col-sm col-6 m-2">
                <x-stat-card
                    label="Deactivated"
                    :value="$this->stats['deactivated']"
                    icon="fa-user-slash"
                    color="warning"
                    text="Suspended access"
                />
            </div>

            {{-- Unverified/Inactive Email --}}
            <div class="col-sm col-6 m-2">
                <x-stat-card
                    label="Unverified"
                    :value="$this->stats['unverified']"
                    icon="fa-envelope-open-text"
                    color="danger"
                    text="Pending email activation"
                />
            </div>

            @if($isSuperAdmin)
                {{-- hq users --}}
                <div class="col-sm col-6 m-2">
                    <x-stat-card
                        label="HQ Users"
                        :value="$this->stats['hqUsers']"
                        icon="fa-building"
                        color="info"
                        text="Headquarters staff"
                    />
                </div>

                <div class="col-sm col-6 m-2">
                    <x-stat-card
                        label="Admins"
                        :value="$this->stats['admins']"
                        icon="fa-user-shield"
                        color="dark"
                        text="System managers"
                    />
                </div>

                {{-- Field Officers --}}
                <div class="col-sm col-6 m-2">
                    <x-stat-card
                        label="Constituency Officers"
                        :value="$this->stats['constituencyUsers']"
                        icon="fa-map-marked-alt"
                        color="success"
                        text="Constituency level"
                    />
                </div>
                {{-- Interns/Others --}}
                <div class="col-sm col-6 m-2">
                    <x-stat-card
                        label="Interns"
                        :value="$this->stats['interns']"
                        icon="fa-user-graduate"
                        color="secondary"
                        text="Other attachments"
                    />
                </div>
            @endif
        </div>

        <div class="card card-outline card-info shadow-sm border-0 mt-2" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                    <h3 class="card-title font-weight-bold text-dark mb-3 mb-md-0 mr-md-4">
                        <i class="fas fa-users text-primary mr-2"></i> Users
                    </h3>

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-md-end flex-md-grow-1" style="gap: 15px;">

                        <div class="input-group input-group-sm order-2 order-sm-1"
                            style="min-width: 250px; width: 100%; max-width: 350px;">
                            <input type="text"
                                wire:model.live.debounce.500ms="search"
                                class="form-control"
                                placeholder="Search for Applications...">
                            <div class="input-group-append">
                                <button class="btn btn-default" wire:loading.attr="disabled">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex order-1 order-sm-2" style="gap: 8px;">
                            <div class="btn-group">
                                <x-loading-button
                                    title="refresh data"
                                    target="refreshData"
                                    class="btn-default"
                                    icon="fas fa-sync-alt"
                                    label=""
                                />
                                <x-loading-button
                                    title="clear filters"
                                    target="resetFilters"
                                    class="btn-default"
                                    icon="fas fa-filter"
                                    label=""
                                />
                            </div>

                            <x-loading-button
                                target="exportUsers"
                                class="btn-dark"
                                icon="fas fa-file-excel"
                                label="Export Data"
                                loadingLabel="..."
                            />

                            <x-loading-button
                                target="createUser"
                                class="btn-info"
                                icon="fas fa-plus-circle"
                                label="Register New User"
                                loadingLabel="..."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($search || $roleFilter || $statusFilter || $countyFilter || $constituencyFilter)
                    <div class="d-flex flex-wrap align-items-center my-2 px-3 py-2 bg-light rounded border shadow-sm animate__animated animate__fadeInUp animate__fast"" style="gap: 8px;">
                        <small class="text-muted font-weight-bold text-uppercase mr-2" style="font-size: 0.7rem;">
                            <i class="fas fa-filter mr-1"></i> Active Filters:
                        </small>
                        @if($search)
                            <span class="badge badge-pill badge-info py-2 px-3 shadow-sm">
                                Search: <strong>{{ $search }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="$set('search', '')" title="Remove search"></i>
                            </span>
                        @endif
                        @if($statusFilter)
                            <span class="badge badge-pill badge-success py-2 px-3 shadow-sm">
                                Status: <strong>{{ $statusFilter ?? '' }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('statusFilter')" title="Remove Status"></i>
                            </span>
                        @endif
                        @if($roleFilter)
                            <span class="badge badge-pill badge-success py-2 px-3 shadow-sm">
                                Role: <strong>{{ $roleFilter ?? '' }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('roleFilter')" title="Remove Status"></i>
                            </span>
                        @endif
                        @if($countyFilter)
                            <span class="badge badge-pill badge-success py-2 px-3 shadow-sm">
                                County: <strong>{{ collect($counties)->firstWhere('county_code', $countyFilter)->county_name ?? 'N/A' }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('countyFilter')" title="Remove Status"></i>
                            </span>
                        @endif
                        @if($constituencyFilter)
                            <span class="badge badge-pill badge-success py-2 px-3 shadow-sm">
                                Constituency: <strong>{{ $constituencyFilter ?? '' }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('constituencyFilter')" title="Remove Status"></i>
                            </span>
                        @endif
                        <button class="btn btn-link btn-sm p-0 ml-auto text-muted small" wire:click="resetFilters">Reset All</button>
                    </div>
                @endif

                {{-- filters --}}
                <div class="row mb-3">
                    @if($isSuperAdmin)
                        {{-- County Filter --}}
                        <div class="col-sm col-6">
                            <label class="small font-weight-bold text-muted ml-1">COUNTY</label>
                            <x-dropdown-search
                                wire:key="dropdown-countyFilter-{{ count($countyOptions) }}"
                                model="countyFilter"
                                :options="$countyOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select County"
                                icon="fas fa-map-marker-alt"
                            />
                        </div>

                        {{-- Bind the key to the count AND the county code --}}
                        <div class="col-sm col-6" wire:key="constituency-wrapper-{{ $countyFilter }}">
                            @if(!empty($constituencyOptions))
                                <label class="small font-weight-bold text-muted ml-1">CONSTITUENCY</label>
                                <x-dropdown-search
                                    wire:key="dropdown-{{ $countyFilter }}-{{ count($constituencyOptions) }}"
                                    model="constituencyFilter"
                                    :options="$constituencyOptions"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select Constituency"
                                    icon="fas fa-map-signs"
                                />
                            @else
                                <label class="small text-muted ml-1 opacity-50">CONSTITUENCY</label>
                                <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                    <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                    <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a county first...</small>
                                    <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading constituencies...</small>
                                </div>
                            @endif
                        </div>
                    @endif
                    {{-- Role Filter --}}
                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-muted ml-1">USER ROLE</label>
                        <x-dropdown-search
                            model="roleFilter"
                            :options="$roleOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="All Roles"
                            icon="fas fa-user-tag"
                        />
                    </div>

                    {{-- Status Filter --}}
                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-muted ml-1">ACCOUNT STATUS</label>
                        <x-dropdown-search
                            model="statusFilter"
                            :options="$statusOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Any Status"
                            icon="fas fa-shield-alt"
                        />
                    </div>

                    {{-- page filter --}}
                    <div class="col-sm-2 col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">Display</label>
                        <x-dropdown-search
                            model="perPage"
                            :options="$this->perPageOptions"
                            icon="fas fa-list-ol"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="10 per page"
                        />
                    </div>
                </div>

                {{-- data --}}
                <div class="position-relative">
                    <div wire:loading.delay.shortest
                        wire:target="nextPage, previousPage, gotoPage, refreshData, perPage, statusFilter, countyfilter, constituencyfilter, rolefilter, search"
                        class="w-100 h-100 position-absolute justify-content-center align-items-center"
                        style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 200px;">
                        <div class="text-center">
                            <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                            <p class="text-navy font-weight-bold mt-2">Loading Data...</p>
                        </div>
                    </div>

                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch; max-height: 550px;" >
                        <table id="table-data" class="table table-hover text-wrap table-head-fixed">
                            <thead style="background-color: #f8fafc; border-bottom: 2px solid #edf2f7;">
                                <tr>
                                    <th width="50" class="align-middle text-center">
                                        #
                                    </th>
                                    <th class="small font-weight-bold text-uppercase text-muted">User Identity</th>
                                    <th class="small font-weight-bold text-uppercase text-muted">Assigned Jurisdiction</th>
                                    <th class="small font-weight-bold text-uppercase text-muted">Security Role</th>
                                    <th class="small font-weight-bold text-uppercase text-muted">Account Status</th>
                                    <th class="small font-weight-bold text-uppercase text-muted">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="text-center align-middle" style="height: 60px;">
                                            <span class="rounded-circle bg-gray-light d-flex align-items-center justify-content-center mx-auto"
                                                style="width: 28px; height: 28px;">
                                                {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                                            </span>
                                        </td>

                                        <td class="px-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-gradient-info text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px; font-weight: 600;">
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
                                                <span class="badge badge-light border text-info px-2 py-1 mb-1">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $con->constituency_name }}
                                                </span>
                                            @empty
                                                <span class="badge badge-light px-2 py-1">HQ / Unassigned</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $isConstituencyOfficer ? 'badge-primary' : 'badge-info' }} px-3 py-1 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
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
                                                <x-table-action-btn
                                                    type="view"
                                                    route="users.viewuser"
                                                    :params="[
                                                        'county' => $county->slug,
                                                        'user' => $user->slug,
                                                    ]"
                                                />
                                            </div>
                                        </td>
                                    </tr>

                                @empty

                                @endforelse
                                {{-- @forelse($applications as $app) --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-footer clearfix bg-transparent">
                <div class="pagination-sm">
                    {{ $users->links() }}
                </div>
            </div>

        </div>
    </div>
    <!-- /container -->

    {{-- create new user --}}
    <x-action-modal
        id="create-new-user-modal"
        title="Register New User"
        icon="fa-user-plus"
        x-on:open-modal.window="if($event.detail.name === 'create-new-user-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'create-new-user-modal') $($el).modal('hide')"
        >
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm border-left-danger" style="border-radius: 12px; background-color: #f8f9fa;">
                <div class="d-flex align-items-center text-danger mb-2">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <strong class="text-uppercase small">Please fix the following errors:</strong>
                </div>
                <ul class="mb-0 px-3 text-dark">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form wire:submit.prevent="storeUser">
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-uppercase text-muted font-weight-bold small mb-3">
                        <i class="fas fa-id-card mr-1"></i> Staff Personal Details
                    </h6>
                </div>
                <div class="col-md-6 form-group mb-4 text-left">
                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group premium-input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user text-info"></i></span>
                        </div>
                        <input type="text" wire:model="username"
                            class="form-control form-control-lg"
                            placeholder="John Doe" required>
                    </div>
                    @error('username') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-6 form-group mb-4 text-left">
                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">ID / Passport Number <span class="text-danger">*</span></label>
                    <div class="input-group premium-input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-fingerprint text-info"></i></span>
                        </div>
                        <input type="text" wire:model="idNumber"
                            class="form-control form-control-lg"
                            placeholder="12345678" required>
                    </div>
                    @error('idNumber') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6 form-group mb-4 text-left">
                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group premium-input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope text-info"></i></span>
                        </div>
                        <input type="email" wire:model="email"
                            class="form-control form-control-lg"
                            placeholder="john@uwezo.go.ke" required>
                    </div>
                    @error('email') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-6 form-group mb-4 text-left">
                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Contact <span class="text-danger">*</span></label>
                    <div class="input-group premium-input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">+254</i></span>
                        </div>
                        <input type="text" wire:model="phoneNumber"
                            class="form-control form-control-lg"
                            placeholder="712345678" required>
                    </div>
                    @error('phoneNumber') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h6 class="text-uppercase text-muted font-weight-bold small mb-3">
                        <i class="fas fa-shield-alt mr-1"></i> Access & Security
                    </h6>
                </div>

                <div class="col-md-6 form-group mb-4 text-left">
                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Assigned Role <span class="text-danger">*</span></label>
                    <x-dropdown-search
                        model="selectedRole"
                        :options="$roleOptions"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="select role"
                        icon="fas fa-user-tag"
                    />
                    @error('selectedRole') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-6 form-group mb-4 text-left">
                    <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Temporary Password <span class="text-danger">*</span></label>
                    <div class="input-group premium-input-group">
                        <div class="input-group-prepend">
                            <x-loading-button
                                target="generatePassword"
                                class=""
                                icon="fas fa-sync-alt"
                                label=""
                                loadingLabel=""
                            />
                        </div>
                        <input type="text" wire:model="password"
                            class="form-control form-control-lg" readonly>
                    </div>
                    <small class="text-muted extra-small">User will be required to change this on first login.</small>
                    @error('password') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                </div>
            </div>

            @if(in_array($selectedRole, ['constituency_officer', 'intern']))
                <div class="row mt-2 animate__animated animate__fadeIn">
                    <div class="col-md-12">
                        <h6 class="text-uppercase text-muted font-weight-bold small mb-3">
                            <i class="fas fa-map-marker-alt mr-1"></i> Area of Jurisdiction
                        </h6>
                    </div>
                    <div class="col-md-6 form-group mb-4 text-left">
                        <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">Select County <span class="text-danger">*</span></label>
                        <x-dropdown-search
                            model="selectedCounty"
                            :options="$countyOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="select county"
                            icon="fas fa-user-tag"
                        />
                        @error('selectedCounty') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-sm col-6" wire:key="constituency-wrapper-user-{{ $selectedCounty }}">
                        @if(!empty($userConstituencyOptions))
                            <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">CONSTITUENCY</label>
                            <x-dropdown-multiselect
                                wire:key="dropdown-user-{{ $selectedCounty }}-{{ count($userConstituencyOptions) }}"
                                model="selectedConstituencies"
                                :options="$userConstituencyOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Constituencies"
                                icon="fas fa-map-marked-alt"
                            />
                            @error('selectedConstituencies') <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span> @enderror
                        @else
                            <label class="text-uppercase font-weight-black small tracking-wider mb-2 text-muted ml-1 opacity-50">Assigned Constituencies</label>
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedSelectedCounty" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedSelectedCounty">Select a county first...</small>
                                <small class="text-muted" wire:loading wire:target="updatedSelectedCounty">Loading constituencies...</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <x-slot name="footer">
                <x-pill-button label="Cancel" variant="outline" color="dark" data-dismiss="modal" class="mx-1" />
                <x-pill-button type="submit" label="Create User" color="info" target="storeUser" icon="fas fa-plus-circle" class="mx-1 shadow-sm" />
            </x-slot>
        </form>

    </x-action-modal>
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

