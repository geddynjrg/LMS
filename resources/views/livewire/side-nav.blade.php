
 <!-- Main Sidebar Container -->
 <aside wire:ignore.self class="main-sidebar main-sidebar-custom sidebar-dark-primary elevation-4">
    <a href="{{route('dashboard')}}" class="brand-link row">
        <img src="{{asset('images/uwezo_logo.PNG')}}" alt="Uwezo Logo" class="brand-image img-circle elevation-4" style="opacity: .8">
        <span class="brand-text text-bold">UWEZO FUND LMS</span>
    </a>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="mt-3 mb-3">
            <div class="info ">
                <div class="d-block text-center text-bold text-lg">
                    @auth
                        @hasrole('hq_officer|admin')
                            <a class="brand-text text-lime">National Headquarters</a>
                        @elseif(auth()->user()->hasRole('head_of_secretariat'))
                            <a class="brand-text text-purple">Head of Secretariat</a>
                        @elseif(auth()->user()->hasRole('accountant'))
                            <a class="brand-text text-teal">Accounts</a>
                        @else
                            @if(isset($county))
                                <a class="brand-text">{{ $county->county_name}} County</a>
                            @endif
                        @endhasrole
                    @endauth
                </div>
            </div>
        </div>

        <hr class="bold bg-gradient-gray w-100">

        <!-- SidebarSearch Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
            <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
                <button class="btn btn-sidebar">
                <i class="fas fa-search fa-fw"></i>
                </button>
            </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-flatt nav-child-indent nav-compact" data-widget="treeview" role="menu" data-accordion="false">

            @auth
            <li class="nav-header text-uppercase text-xs text-muted">Support</li>
            <li class="nav-item {{ Route::is('help.*') ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ Route::is('help.*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-life-ring text-info"></i>
                    <p>
                        Help &amp; Support
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('help.index') }}#getting-started" class="nav-link">
                            <i class="fas fa-play-circle nav-icon text-xs"></i>
                            <p>Getting started</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('help.index') }}#applications" class="nav-link">
                            <i class="fas fa-tasks nav-icon text-xs"></i>
                            <p>Applications</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('help.index') }}#training" class="nav-link">
                            <i class="fas fa-chalkboard-teacher nav-icon text-xs"></i>
                            <p>Training &amp; disbursement</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('help.index') }}#reports" class="nav-link">
                            <i class="fas fa-file-alt nav-icon text-xs"></i>
                            <p>Reports &amp; uploads</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('help.index') }}#troubleshooting" class="nav-link">
                            <i class="fas fa-wrench nav-icon text-xs"></i>
                            <p>Troubleshooting</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('help.index') }}#contact" class="nav-link">
                            <i class="fas fa-envelope nav-icon text-xs"></i>
                            <p>Contact support</p>
                        </a>
                    </li>
                </ul>
            </li>
            @endauth

            @hasrole('accountant|super-admin')
                <li class="nav-header text-uppercase text-xs text-muted">Accounts</li>
                <li class="nav-item">
                    <a href="{{ route('accountant.phases.index') }}" class="nav-link {{ Route::is('accountant.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calculator text-teal"></i>
                        <p>Accounts Dashboard</p>
                    </a>
                </li>
            @endhasrole

            @hasrole('head_of_secretariat|super-admin')
                <li class="nav-header text-uppercase text-xs text-muted">Secretariat</li>
                <li class="nav-item">
                    <a href="{{ route('secretariat.phases.index') }}" class="nav-link {{ Route::is('secretariat.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-stamp text-purple"></i>
                        <p>Secretariat Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('dashboard', ['national' => 1]) }}" class="nav-link {{ request()->boolean('national') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line text-info"></i>
                        <p>National Analytics</p>
                    </a>
                </li>
            @endhasrole

            @hasrole('hq_officer|admin|super-admin')
                <li class="nav-header text-uppercase text-xs text-muted">National Administration</li>
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line text-info"></i>
                        <p>HQ DASHBOARD</p>
                    </a>
                </li>
                <li class="nav-header text-uppercase text-xs font-weight-bold text-muted mt-3">Loan Applications</li>
                <li class="nav-item {{ Route::is('hq.submissions.apps*') || Route::is('hq.submissions.tasks*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('hq.submissions.apps*') || Route::is('hq.submissions.tasks*') ? 'active shadow-sm' : '' }}">
                        <i class="nav-icon fas fa-stream text-primary"></i>
                        <p>
                            Submissions
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('hq.submissions.apps.index') }}" class="nav-link {{ Route::is('hq.apps.index') ? 'active' : '' }}">
                                <i class="fas fa-file-alt nav-icon text-xs"></i>
                                <p>All Applications</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('hq.submissions.tasks.phases') }}" class="nav-link {{ Route::is('hq.tasks.phases') ? 'active' : '' }}">
                                <i class="fas fa-tasks nav-icon text-xs"></i>
                                <p>Approval Tasks</p>
                            </a>
                        </li>

                        {{-- <li class="nav-item">
                            <a href="#" class="nav-link {{ Route::is('hq.tasks.assessment-logs') ? 'active' : '' }}">
                                <i class="fas fa-database nav-icon text-xs"></i>
                                <p>Assessment Logs</p>
                            </a>
                        </li> --}}
                    </ul>
                </li>

                <li class="nav-header text-uppercase text-xs font-weight-bold text-muted mt-3">Finance & Portfolio</li>
                <li class="nav-item">
                    <a href="{{ route('hq.allocations.index') }}" class="nav-link {{ Route::is('hq.allocations.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-coins text-warning"></i>
                        <p>Constituency Allocations</p>
                    </a>
                </li>
                <li class="nav-item {{ Route::is('hq.loans*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('hq.loans*') ? 'active shadow-sm' : '' }}">
                        <i class="nav-icon fas fa-hand-holding-usd"></i>
                        <p>Loan Portfolio <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('hq.loans.group_loans') }}" class="nav-link {{ Route::is('hq.loans.group_loans') ? 'active' : '' }}">
                                <i class="fas fa-history nav-icon text-xs"></i>
                                <p>Group Loans</p>
                            </a>
                        </li>
                        {{-- <li class="nav-item">
                            <a href="{{ route('hq.loans.phases') }}" class="nav-link {{ Route::is('hq.loans.phases') ? 'active' : '' }}">
                                <i class="fas fa-layer-group nav-icon text-xs"></i>
                                <p>Loan Phases</p>
                            </a>
                        </li> --}}
                        <li class="nav-item">
                            <a href="{{ route('hq.loans.receipts') }}" class="nav-link {{ Route::is('hq.loans.receipts') ? 'active' : '' }}">
                                <i class="fas fa-receipt nav-icon text-xs"></i>
                                <p>Loan Receipts</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header text-uppercase text-xs font-weight-bold text-muted mt-3">Registry (National)</li>
                <li class="nav-item {{ Route::is('hq.groups*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('hq.groups*') ? 'active shadow-sm' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Groups <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        {{-- <li class="nav-item">
                            <a href="{{ route('hq.groups.dashboard') }}" class="nav-link {{ Route::is('hq.groups.dashboard') ? 'active' : '' }}">
                                <i class="fas fa-chart-pie nav-icon text-xs"></i>
                                <p>Beneficiary Groups</p>
                            </a>
                        </li> --}}
                        <li class="nav-item">
                            <a href="{{ route('hq.groups.index') }}" class="nav-link {{ Route::is('hq.groups.index') ? 'active' : '' }}">
                                <i class="fas fa-list nav-icon text-xs"></i>
                                <p>Beneficiary Groups</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- <li class="nav-item {{ Route::is('hq.members*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Route::is('hq.members*') ? 'active shadow-sm' : '' }}">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>Members <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('hq.members.dashboard') }}" class="nav-link {{ Route::is('hq.members.dashboard') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt nav-icon text-xs"></i>
                                <p>Members Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('hq.members.index') }}" class="nav-link {{ Route::is('hq.members.index') ? 'active' : '' }}">
                                <i class="fas fa-id-card nav-icon text-xs"></i>
                                <p>All Members List</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('hq.members.contagious') }}" class="nav-link {{ Route::is('hq.members.contagious') ? 'active' : '' }}">
                                <i class="fas fa-exclamation-triangle nav-icon text-xs text-danger"></i>
                                <p>Contagious Members</p>
                                <span class="badge badge-danger ml-auto">Audit</span>
                            </a>
                        </li>
                    </ul>
                </li> --}}

                <li class="nav-item">
                    <a href="{{ route('hq.reports.index') }}" class="nav-link {{ Route::is('hq.reports.*') ? 'active shadow-sm' : '' }}">
                        <i class="nav-icon fas fa-file-contract text-success"></i>
                        <p>National Reports</p>
                    </a>
                </li>
            @endhasrole
            @hasrole('constituency_officer|super-admin')
            @if ($constituencies->isNotEmpty() && $currentConstituency !== null)
                <li class="nav-item">
                <a href="{{ route('const.dashboard', [
                    Str::slug($county->slug),
                    Str::slug($currentConstituency->slug)
                    ]) }}" class="nav-link text-bold {{ $nav == 'const.dashboard' ? 'active' : '' }}">
                    <i class="nav-icon fas fa-home"></i>
                    <p>DASHBOARD</p>
                </a>
                </li>
                <li class="nav-item {{ Route::is('const.group*')? 'menu-open':''}}">
                    <a href="#" class="nav-link text-bold {{ Route::is('const.group*')? 'active':''}}">
                    <i class="nav-icon fas fa-object-group"></i>
                    <p>
                        GROUPS
                        <i class="right fas fa-angle-right"></i>
                    </p>
                    </a>
                    <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{route('const.groups.index', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ]) }}" class="nav-link {{ Route::currentRouteName() == 'const.groups.index' ? 'active':''}}">
                        <i class="fas fa-info nav-icon"></i>
                        <p>Groups Information</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.groups.members', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.groups.members' ? 'active':''}}">
                        <i class="fas fa-users-cog nav-icon"></i>
                        <p>Group Members</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.groups.multi-group-members', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.groups.multi-group-members' ? 'active':''}}">
                        <i class="fas fa-users nav-icon"></i>
                        <p>Contagious Members</p>
                        </a>
                    </li>
                    </ul>
                </li>
                <li class="nav-item {{ Route::is('const.loans*')? 'menu-open':''}}">
                    <a href="#" class="nav-link text-bold {{ Route::is('const.loans*')? 'active':''}}">
                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                    <p>
                        LOANS
                        <i class="right fas fa-angle-right"></i>
                    </p>
                    </a>
                    <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{route('const.loans.phases', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.phases' ? 'active':''}}">
                        <i class="fas fa-mountain nav-icon"></i>
                        <p>Loan Phase</p>
                        </a>
                    </li>
                    @can ('create loan application')
                    {{-- <li class="nav-item">
                        <a href="{{route('const.loans.application', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.application' ? 'active':''}}">
                        <i class="fas fa-wallet nav-icon"></i>
                        <p>Loan Application</p>
                        </a>
                    </li> --}}
                    @endcan
                    {{-- <li class="nav-item">
                        <a href="{{route('const.loans.applicants', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.applicants' ? 'active':''}}">
                        <i class="fas fa-wallet nav-icon"></i>
                        <p>Applied Loans</p>
                        </a>
                    </li> --}}
                    <li class="nav-item">
                        <a href="{{route('const.loans.grouploans', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.grouploans' ? 'active':''}}">
                        <i class="fas fa-landmark nav-icon"></i>
                        <p>Group Loans</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.loans.loanreceipts', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.loanreceipts' ? 'active':''}}">
                        <i class="fas fa-receipt nav-icon"></i>
                        <p>Receipts</p>
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a href="{{route('const.loans.loanmembers', [
                        Str::slug($county->slug),
                        Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.loanmembers' ? 'active':''}}">
                        <i class="fas fa-hand-holding-usd nav-icon"></i>
                        <p>Loaned Members</p>
                        </a>
                    </li> --}}
                    </ul>
                </li>
                <li class="nav-item {{ $nav == 'app' ? 'menu-open':''}}">
                    <a href="#" class="nav-link text-bold {{ $nav == 'app' ? 'active':''}}">
                        <i class="nav-icon fas fa-laptop-house"></i>
                        <p>
                            APPLICATIONS
                            <i class="right fas fa-angle-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <x-nav-link
                            route="const.app.app_dashboard"
                            :params="[Str::slug($county->slug), Str::slug($currentConstituency->slug)]"
                            icon="fas fa-tachometer-alt"
                        >
                            Applications

                            <x-slot:badgeSlot>
                                <livewire:side-nav-badge
                                    :constituencyCode="$currentConstituency->constituency_code"
                                    type="not_linked"
                                />
                            </x-slot:badgeSlot>
                        </x-nav-link>

                        <x-nav-link
                            route="const.app.app_phases"
                            :params="[Str::slug($county->slug), Str::slug($currentConstituency->slug)]"
                            icon="fas fa-layer-group"
                        >
                            Tasks

                            <x-slot:badgeSlot>
                                <livewire:side-nav-badge
                                    :constituencyCode="$currentConstituency->constituency_code"
                                    type="constituency_tasks"
                                />
                            </x-slot:badgeSlot>
                        </x-nav-link>

                        <!-- Dynamic: Review Session (only shows when active) -->
                        @php
                            // You would need to check if there's an active review phase
                            // This could be done with a Livewire computed property or session variable
                            $hasActiveReview = false; // Replace with actual logic
                        @endphp

                        @if($hasActiveReview)
                        <li class="nav-item">
                            <a href="{{-- route to active review --}}" class="nav-link">
                                <i class="fas fa-clipboard-check nav-icon"></i>
                                <p>Review Session</p>
                                <span class="badge badge-danger badge-pill ml-auto">Active</span>
                            </a>
                        </li>
                        @endif

                        <!-- Existing route -->
                        <li class="nav-item">
                            <a href="{{route('const.app.app_loans', [
                                Str::slug($county->slug),
                                Str::slug($currentConstituency->slug),
                            ])}}" class="nav-link {{ Route::currentRouteName() == 'const.app.app_loans' ? 'active':''}}">
                                <i class="fas fa-file-invoice-dollar nav-icon"></i>
                                <p>Applied Loans</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item {{ Route::is('const.reports*')? 'menu-open':''}}">
                <a href="#" class="nav-link text-bold {{ Route::is('const.reports*')? 'active':''}}">
                    <i class="nav-icon fas fa-envelope-open-text"></i>
                    <p>
                    REPORTS
                    <i class="right fas fa-angle-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{route('const.reports.data-upload', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.reports.data-upload' ? 'active':''}}">
                        <i class="fas fa-file-import nav-icon"></i>
                        <p>Upload Data</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.reports.dynamic-report', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.reports.dynamic-report' ? 'active':''}}">
                        <i class="fas fa-folder-open nav-icon"></i>
                        <p>Generate Report</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.reports.codelist', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.reports.codelist' ? 'active':''}}">
                        <i class="fas fa-file nav-icon"></i>
                        <p>Loan Subm Form (F.5)</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.reports.restore-deleted-data', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.reports.restore-deleted-data' ? 'active':''}}">
                        <i class="fas fa-undo nav-icon"></i>
                        <p>Restore Deleted Data</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('const.reports.audit-trail', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ]) }}" class="nav-link {{ Route::currentRouteName() == 'const.reports.audit-trail' ? 'active':''}}">
                        <i class="fas fa-history nav-icon"></i>
                        <p>Audit Trail</p>
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a href="{{route('const.reports.disbursement', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.reports.disbursement' ? 'active':''}}">
                        <i class="fas fa-file nav-icon"></i>
                        <p>Disbursement Reporting</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="fas fa-file nav-icon"></i>
                        <p>Sectorial Analysis</p>
                        </a>
                    </li> --}}
                </ul>
                </li>
                {{-- <li class="nav-item {{ Route::is('const.monitoring*')? 'menu-open':''}}">
                <a href="#" class="nav-link text-bold {{ Route::is('const.monitoring*')? 'active':''}}">

                    <i class="nav-icon fab fa-watchman-monitoring"></i>
                    <p>
                    M&E
                    <i class="right fas fa-angle-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{route('const.monitoring.data_view', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.monitoring.data_view' ? 'active':''}}">
                        <i class="fas fa-folder nav-icon"></i>
                        <p>View Data</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('const.monitoring.monitor_home', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.monitoring.monitor_home' ? 'active':''}}">
                        <i class="fas fa-balance-scale nav-icon"></i>
                        <p>Report</p>
                        </a>
                    </li>
                </ul>
                </li> --}}
            @endif

            <li class="nav-item {{ Route::is('users.*') || Route::currentRouteName() == 'county.audit-trail' ? 'menu-open':''}}">
                <a href="#" class="nav-link text-bold {{ Route::is('users.*') || Route::currentRouteName() == 'county.audit-trail' ? 'active':''}}">
                    <i class="nav-icon fas fa-users"></i>
                    <p>
                    USERS
                    <i class="right fas fa-angle-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                    <a href="{{route('users.viewusers', [Str::slug($county->slug),])}}" class="nav-link {{ Route::currentRouteName() == 'users.viewusers' ? 'active':''}}">
                        <i class="fas fa-eye nav-icon"></i>
                        <p>View Users</p>
                    </a>
                    </li>
                    <li class="nav-item">
                    <a href="{{ route('county.audit-trail', [Str::slug($county->slug)]) }}" class="nav-link {{ Route::currentRouteName() == 'county.audit-trail' ? 'active':''}}">
                        <i class="fas fa-history nav-icon"></i>
                        <p>Audit Trail</p>
                    </a>
                    </li>
                    {{-- @can('create user')
                        <li class="nav-item">
                        <a href="{{route('users.adduser', [Str::slug($county->slug),])}}" class="nav-link {{ Route::currentRouteName() == 'users.adduser' ? 'active':''}}">
                            <i class="fas fa-user-plus nav-icon"></i>
                            <p>Add Users</p>
                        </a>
                        </li>
                    @endcan --}}
                </ul>
            </li>

            <li class="nav-item {{ Route::currentRouteName() == 'dashboard' ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}">
                    <i class="nav-icon fas fa-globe"></i>
                    <p>
                        Constituency
                        <i class="right fas fa-angle-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    @if ($constituencies != null)
                    @foreach ($constituencies as $constituency)
                        <li class="nav-item">
                            @if ($currentConstituency !== null && $currentConstituency->constituency_code === $constituency['constituency_code'])
                                <a wire:click="selected('{{ $constituency['constituency_code'] }}')" class="nav-link active">
                                    <i class="fa fa-arrow-circle-right nav-icon"></i>
                                    <p>{{ ucwords(strtolower($constituency['constituency_name'])) }}</p>
                                </a>
                            @else
                                <a wire:click="selected('{{ $constituency['constituency_code'] }}')" class="nav-link">
                                    <i class="fa fa-arrow-circle-right nav-icon"></i>
                                    <p>{{ ucwords(strtolower($constituency['constituency_name'])) }}</p>
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif

                </ul>
            </li>
            @endhasrole
            </ul>
        </nav>
    <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->

    <div class="sidebar-custom">
    @auth
    <a href="{{ route('profile.show') }}" class="btn btn-link"><i class="fas fa-cogs"></i></a>
    <a href="{{ route('profile.show') }}" class="btn btn-secondary hide-on-collapse pos-right">{{ auth()->user()->name }}</a>
    @endauth
    </div>
  <!-- /.sidebar-custom -->
</aside>

