<section class="content">
    <div class="container-fluid px-0">
        <div class="hq-workspace-shell">
            <div class="row no-gutters">
                {{-- SPSS-style filter pane --}}
                <div class="col-lg-3 col-xl-2 border-right bg-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="hq-sidebar-label mb-0">Constituency scope</span>
                        <span class="hq-badge-v2">Live</span>
                    </div>

                    <p class="small text-muted mb-3">
                        <i class="fas fa-map-marker-alt text-info mr-1"></i>
                        {{ ucwords(strtolower($constituency->constituency_name)) }}
                    </p>

                    <div class="mb-4" wire:key="const-v2-ward-wrapper-{{ $constituencyFilter }}">
                        <x-dropdown-search
                            wire:key="const-v2-ward-{{ count($this->wardsOptions) }}"
                            model="wardFilter"
                            :options="$this->wardsOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Filter by ward"
                            icon="fas fa-map-signs"
                        />
                    </div>

                    <div class="d-flex flex-column">
                        <x-loading-button
                            target="refreshData"
                            class="btn btn-info btn-sm mb-2"
                            icon="fas fa-sync-alt"
                            label="Refresh data"
                        />
                        <x-loading-button
                            target="resetFilters"
                            class="btn btn-outline-secondary btn-sm"
                            icon="fas fa-undo"
                            label="Clear ward filter"
                        />
                    </div>

                    <hr class="my-4">

                    <span class="hq-sidebar-label d-block mb-2">Quick links</span>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('const.app.app_dashboard', [$constituency->county->slug, $constituency->slug]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                            <i class="fas fa-file-signature text-info mr-2"></i> Application portal
                        </a>
                        <a href="{{ route('const.app.app_phases', [$constituency->county->slug, $constituency->slug]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                            <i class="fas fa-layer-group text-info mr-2"></i> Phases
                        </a>
                        <a href="{{ route('const.groups.index', [$constituency->county->slug, $constituency->slug]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                            <i class="fas fa-users text-info mr-2"></i> Groups registry
                        </a>
                        <a href="{{ route('const.reports.export', [$constituency->county->slug, $constituency->slug]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                            <i class="fas fa-file-export text-info mr-2"></i> Reports
                        </a>
                        <a href="{{ route('const.allocations.index', [$constituency->county->slug, $constituency->slug]) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                            <i class="fas fa-coins text-info mr-2"></i> Allocation profile
                        </a>
                    </div>

                    <div class="mt-4 p-3 rounded {{ $this->riskProfile['bg_class'] }}">
                        <span class="hq-sidebar-label d-block mb-1">Portfolio risk</span>
                        <div class="font-weight-bold {{ $this->riskProfile['text_color'] }}">
                            {{ number_format($this->performance['financial_rate'], 1) }}% PAR
                        </div>
                        <small class="text-muted d-block mt-1">{{ $this->riskProfile['message'] }}</small>
                    </div>
                </div>

                {{-- Main workspace --}}
                <div class="col-lg-9 col-xl-10 p-3 p-md-4">
                    @if(auth()->user()?->hasAnyRole(['super-admin', 'constituency_officer']))
                        <div class="hq-panel-card p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="font-weight-bold mb-1">
                                        <i class="fas fa-university text-info mr-2"></i>Constituency bank balance
                                    </h6>
                                    <p class="text-muted small mb-0">
                                        Letter of authority is required only when an open phase request exceeds this balance.
                                    </p>
                                </div>
                                <span class="badge badge-light border">
                                    Open phase request: KES {{ number_format($this->openPhaseRequestedAmount) }}
                                </span>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="small font-weight-bold text-muted text-uppercase">Available balance (KES)</label>
                                    <input type="number" min="0" step="0.01" wire:model="constituencyBankBalance" class="form-control" placeholder="Leave blank if not tracked">
                                    @error('constituencyBankBalance') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-auto">
                                    <x-loading-button
                                        target="saveConstituencyBankBalance"
                                        class="btn btn-info btn-sm"
                                        icon="fas fa-save"
                                        label="Save balance"
                                    />
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                        <ul class="nav hq-workspace-tabs mb-2 mb-md-0">
                            @foreach([
                                'overview' => ['label' => 'Overview', 'icon' => 'fa-chart-pie'],
                                'data' => ['label' => 'Data', 'icon' => 'fa-table'],
                                'analyze' => ['label' => 'Analyze', 'icon' => 'fa-microscope'],
                                'tasks' => ['label' => 'Tasks', 'icon' => 'fa-inbox'],
                            ] as $key => $tab)
                                <li class="nav-item mr-1">
                                    <button type="button"
                                        wire:click="setWorkspace('{{ $key }}')"
                                        class="nav-link {{ $workspace === $key ? 'active' : '' }}">
                                        <i class="fas {{ $tab['icon'] }} mr-1"></i>{{ $tab['label'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div wire:loading wire:target="refreshData,resetFilters,updatedWardFilter,setWorkspace,setAnalysisDimension,saveConstituencyBankBalance"
                            class="text-muted small">
                            <span class="spinner-border spinner-border-sm text-info mr-1"></span> Updating…
                        </div>
                    </div>

                    @if($workspace === 'overview')
                        <div class="row">
                            @foreach($this->kpis as $kpi)
                                <div class="col-md-6 col-xl-3 mb-3">
                                    <div class="card hq-kpi-card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <span class="text-muted small text-uppercase font-weight-bold">{{ $kpi['title'] }}</span>
                                                    <h4 class="font-weight-bold mb-0 mt-1">KES {{ number_format($kpi['val']) }}</h4>
                                                    <small class="text-muted">{{ $kpi['desc'] }}</small>
                                                </div>
                                                <div class="icon-circle bg-light-{{ $kpi['color'] }} text-{{ $kpi['color'] }}">
                                                    <i class="fas {{ $kpi['icon'] }}"></i>
                                                </div>
                                            </div>
                                            <div class="progress mt-3" style="height: 4px;">
                                                <div class="progress-bar bg-{{ $kpi['color'] }}" style="width: {{ min(100, $kpi['prog']) }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row mt-1">
                            <div class="col-lg-4 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-chart-pie mr-2 text-info"></i>Portfolio mix</h6>
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="constPortfolioChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-users mr-2 text-info"></i>Target groups</h6>
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="constDemographicsChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-chart-bar mr-2 text-info"></i>Recovery</h6>
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="constRecoveryChart"></canvas></div>
                                    <div class="mt-2 text-center">
                                        <span class="badge badge-info px-3 py-2">{{ $this->performance['recovery_rate'] }}% recovery rate</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-7 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-map-marked-alt mr-2 text-info"></i>Ward recovery (%)</h6>
                                    <div class="hq-chart-wrap-sm" wire:ignore><canvas id="constWardChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="font-weight-bold mb-0"><i class="fas fa-inbox mr-2 text-info"></i>Action inbox</h6>
                                        <span class="badge badge-warning">{{ $this->actionInbox['new_application_count'] }} new</span>
                                    </div>
                                    @forelse($this->actionInbox['new_applications'] as $app)
                                        <div class="hq-inbox-item mb-2">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $app['application_id'] }}</strong>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($app['created_at'])->diffForHumans() }}</small>
                                            </div>
                                            <small class="text-muted">{{ $app['group_name'] }}</small>
                                            <div class="mt-2">
                                                <a href="{{ $app['url'] }}" class="btn btn-sm btn-outline-info">Review</a>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">No new applications for review.</p>
                                    @endforelse
                                    <a href="{{ $this->actionInbox['portal_url'] }}" class="btn btn-link btn-sm px-0 mt-2">Open application portal →</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($workspace === 'data')
                        <div class="hq-panel-card p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">Ward performance</h5>
                                    <p class="text-muted mb-0 small">Single table — no duplicate KPI or progress cards.</p>
                                </div>
                                <span class="hq-badge-v2">{{ count($this->dataRows) }} wards</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover hq-data-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Ward</th>
                                            <th>Metric</th>
                                            <th>Value</th>
                                            <th>Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($this->dataRows as $row)
                                            <tr>
                                                <td class="font-weight-bold">{{ $row['name'] }}</td>
                                                <td>{{ $row['metric'] }}</td>
                                                <td><span class="text-{{ $row['tone'] }} font-weight-bold">{{ $row['value'] }}</span></td>
                                                <td class="text-muted">{{ $row['secondary'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted py-4">No ward data for the current filter.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3">Economic sectors</h6>
                                    <div class="hq-chart-wrap-sm" wire:ignore><canvas id="constSectorChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3">Registry summary</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex justify-content-between py-2 border-bottom">
                                            <span>Total groups</span>
                                            <strong>{{ number_format($this->getStats['totalGroups'] ?? 0) }}</strong>
                                        </li>
                                        <li class="d-flex justify-content-between py-2 border-bottom">
                                            <span>Disbursed</span>
                                            <strong>KES {{ number_format($this->getStats['totalAmountIssued'] ?? 0) }}</strong>
                                        </li>
                                        <li class="d-flex justify-content-between py-2 border-bottom">
                                            <span>Repaid</span>
                                            <strong>KES {{ number_format($this->getStats['totalAmountRepaid'] ?? 0) }}</strong>
                                        </li>
                                        <li class="d-flex justify-content-between py-2">
                                            <span>Groups at risk</span>
                                            <strong class="text-danger">{{ number_format($this->riskProfile['count']) }}</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($workspace === 'analyze')
                        <div class="hq-panel-card p-3 mb-3">
                            <div class="d-flex flex-wrap align-items-center mb-3">
                                <h5 class="font-weight-bold mb-2 mb-md-0 mr-md-3">Cross-tab analysis</h5>
                                <div class="btn-group btn-group-sm">
                                    @foreach([
                                        'demographics' => 'Demographics',
                                        'sectors' => 'Sectors',
                                        'recovery' => 'Recovery',
                                    ] as $dim => $label)
                                        <button type="button"
                                            wire:click="setAnalysisDimension('{{ $dim }}')"
                                            class="btn btn-outline-secondary hq-dimension-btn {{ $analysisDimension === $dim ? 'active' : '' }}">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-5 mb-3 mb-lg-0">
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="constAnalyzeChart"></canvas></div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="table-responsive">
                                        <table class="table table-sm hq-data-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Count</th>
                                                    <th>Amount</th>
                                                    <th>Share</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($this->analysisTable as $row)
                                                    <tr>
                                                        <td class="font-weight-bold">{{ $row['label'] }}</td>
                                                        <td>{{ $row['count'] }}</td>
                                                        <td>{{ $row['amount'] }}</td>
                                                        <td>{{ $row['share'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="hq-panel-card p-3 text-center h-100">
                                    <small class="text-muted text-uppercase">Expected</small>
                                    <h4 class="font-weight-bold">KES {{ number_format($this->performance['due']) }}</h4>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="hq-panel-card p-3 text-center h-100">
                                    <small class="text-muted text-uppercase">Collected</small>
                                    <h4 class="font-weight-bold text-success">KES {{ number_format($this->performance['repaid']) }}</h4>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="hq-panel-card p-3 text-center h-100">
                                    <small class="text-muted text-uppercase">Gap</small>
                                    <h4 class="font-weight-bold text-danger">KES {{ number_format($this->performance['deficit']) }}</h4>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($workspace === 'tasks')
                        <div class="row">
                            <div class="col-lg-7 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">Incoming applications</h5>
                                        <a href="{{ $this->actionInbox['portal_url'] }}" class="btn btn-sm btn-info">Open portal</a>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover hq-data-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Application</th>
                                                    <th>Group</th>
                                                    <th>Amount</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($this->actionInbox['new_applications'] as $app)
                                                    <tr>
                                                        <td class="font-weight-bold">{{ $app['application_id'] }}</td>
                                                        <td>{{ $app['group_name'] }}</td>
                                                        <td>KES {{ number_format($app['loan_amount']) }}</td>
                                                        <td><a href="{{ $app['url'] }}" class="btn btn-sm btn-outline-info">View</a></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted py-4">No applications awaiting review.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">Approved phases</h5>
                                        <a href="{{ $this->actionInbox['phases_url'] }}" class="btn btn-sm btn-outline-info">All phases</a>
                                    </div>
                                    @forelse($this->actionInbox['approved_phases'] as $phase)
                                        <div class="hq-inbox-item mb-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $phase['label'] }}</strong>
                                                <small class="d-block text-muted">{{ $phase['ref'] }}</small>
                                                <small class="text-success font-weight-bold">KES {{ number_format($phase['amount']) }}</small>
                                            </div>
                                            <a href="{{ $phase['url'] }}" class="btn btn-sm btn-outline-info">View</a>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">No recently approved phases.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
