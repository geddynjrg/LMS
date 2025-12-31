
 <!-- Main Sidebar Container -->
 <aside wire:ignore.self class="main-sidebar main-sidebar-custom sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->

  <a href="{{route('dashboard')}}" class="brand-link row">
    <img src="{{asset('images/uwezo_logo.PNG')}}" alt="Uwezo Logo" class="brand-image img-circle elevation-4" style="opacity: .8">

  <span class="brand-text text-bold">UWEZO FUND DASHBOARD</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar user (optional) -->
    <div class="mt-3 mb-3">
      <div class="info ">
        <div class="d-block text-center text-bold text-lg">
          @auth
            <a class="brand-text">{{ $county->county_name}} County</a>
            @if($currentConstituency !== null)
                <br>
                <a class="text-lime text-sm brand-text">{{ ucwords(strtolower($currentConstituency->constituency_name)) }} Constituency</a>
            @endif
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
                    <p>Multi Group Members</p>
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
                <li class="nav-item">
                  <a href="{{route('const.loans.application', [
                    Str::slug($county->slug),
                    Str::slug($currentConstituency->slug),
                  ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.application' ? 'active':''}}">
                    <i class="fas fa-wallet nav-icon"></i>
                    <p>Loan Application</p>
                  </a>
                </li>
                @endcan
                <li class="nav-item">
                  <a href="{{route('const.loans.applicants', [
                    Str::slug($county->slug),
                    Str::slug($currentConstituency->slug),
                  ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.applicants' ? 'active':''}}">
                    <i class="fas fa-wallet nav-icon"></i>
                    <p>Applied Loans</p>
                  </a>
                </li>
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
                <li class="nav-item">
                  <a href="{{route('const.loans.loanmembers', [
                    Str::slug($county->slug),
                    Str::slug($currentConstituency->slug),
                  ])}}" class="nav-link {{ Route::currentRouteName() == 'const.loans.loanmembers' ? 'active':''}}">
                    <i class="fas fa-hand-holding-usd nav-icon"></i>
                    <p>Loaned Members</p>
                  </a>
                </li>
              </ul>
          </li>
            <li class="nav-item {{ Route::is('const.app*')? 'menu-open':''}}">
                <a href="#" class="nav-link text-bold {{ Route::is('const.app*')? 'active':''}}">
                    <i class="nav-icon fas fa-laptop-house"></i>
                    <p>
                        APPLICATIONS
                        <i class="right fas fa-angle-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{route('const.app.app_dashboard', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.app.app_dashboard' ? 'active':''}}">
                            <i class="fas fa-tachometer-alt nav-icon"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- NEW: Phase Management -->
                    <li class="nav-item">
                        <a href="{{route('const.app.app_phases', [
                            Str::slug($county->slug),
                            Str::slug($currentConstituency->slug),
                        ])}}" class="nav-link {{ Route::currentRouteName() == 'const.app.app_phases' ? 'active':''}}">
                            <i class="fas fa-layer-group nav-icon"></i>
                            <p>Phases</p>
                        </a>
                    </li>

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
                </li>
            </ul>
          </li>
          <li class="nav-item {{ Route::is('const.monitoring*')? 'menu-open':''}}">
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
          </li>
        @endif
        @hasrole('admin|super-admin')
        <li class="nav-item {{ Route::is('users.*')? 'menu-open':''}}">
            <a href="#" class="nav-link text-bold {{ Route::is('users.*')? 'active':''}}">
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
              @can('create user')
              <li class="nav-item">
                <a href="{{route('users.adduser', [Str::slug($county->slug),])}}" class="nav-link {{ Route::currentRouteName() == 'users.adduser' ? 'active':''}}">
                  <i class="fas fa-user-plus nav-icon"></i>
                  <p>Add Users</p>
                </a>
              </li>
              @endcan
            </ul>
        </li>
        @endhasrole
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
