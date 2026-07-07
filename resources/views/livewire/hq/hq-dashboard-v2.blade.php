<section class="content">
    <div class="container-fluid px-0">
        <div class="hq-workspace-shell">
            <div class="row no-gutters">
                {{-- SPSS-style variable / filter pane --}}
                <div class="col-lg-3 col-xl-2 border-right bg-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="hq-sidebar-label mb-0">Data scope</span>
                        <span class="hq-badge-v2">Live</span>
                    </div>

                    <div class="mb-3">
                        <x-dropdown-search
                            wire:key="v2-county-{{ count($countyOptions) }}"
                            model="countyFilter"
                            :options="$countyOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="County"
                            icon="fas fa-map-marker-alt"
                        />
                    </div>

                    <div class="mb-3" wire:key="v2-constituency-wrapper-{{ $countyFilter }}">
                        @if(!empty($constituencyOptions))
                            <x-dropdown-search
                                wire:key="v2-constituency-{{ $countyFilter }}-{{ count($constituencyOptions) }}"
                                model="constituencyFilter"
                                :options="$constituencyOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Constituency"
                                icon="fas fa-map-signs"
                            />
                        @else
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-success mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select county first…</small>
                                <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading…</small>
                            </div>
                        @endif
                    </div>

                    <div class="mb-4" wire:key="v2-ward-wrapper-{{ $constituencyFilter }}">
                        @if(!empty($wardsOptions))
                            <x-dropdown-search
                                wire:key="v2-ward-{{ $constituencyFilter }}-{{ count($wardsOptions) }}"
                                model="wardFilter"
                                :options="$wardsOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Ward"
                                icon="fas fa-map-signs"
                            />
                        @else
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedConstituencyFilter" class="spinner-border spinner-border-sm text-success mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedConstituencyFilter">Select constituency first…</small>
                                <small class="text-muted" wire:loading wire:target="updatedConstituencyFilter">Loading…</small>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-column">
                        <x-loading-button
                            target="refreshData"
                            class="btn btn-success btn-sm mb-2"
                            icon="fas fa-sync-alt"
                            label="Refresh data"
                        />
                        <x-loading-button
                            target="resetFilters"
                            class="btn btn-outline-secondary btn-sm"
                            icon="fas fa-undo"
                            label="Reset filters"
                        />
                    </div>

                    <hr class="my-4">

                    <span class="hq-sidebar-label d-block mb-2">Quick links</span>
                    <div class="list-group list-group-flush">
                        @if($this->isSecretariatDashboard())
                            <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <i class="fas fa-stamp text-purple mr-2"></i> Secretariat queue
                            </a>
                            <a href="{{ route('secretariat.phases.index', ['filter' => 'completed']) }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <i class="fas fa-check-circle text-success mr-2"></i> Authorized phases
                            </a>
                        @else
                            <a href="{{ route('hq.submissions.tasks.phases') }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <i class="fas fa-tasks text-success mr-2"></i> Approval tasks
                            </a>
                            <a href="{{ route('hq.groups.index') }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <i class="fas fa-users text-success mr-2"></i> Groups registry
                            </a>
                            <a href="{{ route('hq.reports.index') }}" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <i class="fas fa-file-export text-success mr-2"></i> Reports
                            </a>
                        @endif
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

                        <div wire:loading wire:target="refreshData,resetFilters,updatedCountyFilter,updatedConstituencyFilter,updatedWardFilter,setWorkspace,setAnalysisDimension"
                            class="text-muted small">
                            <span class="spinner-border spinner-border-sm text-success mr-1"></span> Updating…
                        </div>
                    </div>

                    {{-- OVERVIEW --}}
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
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-chart-pie mr-2 text-success"></i>Portfolio mix</h6>
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="hqPortfolioChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-users mr-2 text-success"></i>Target groups</h6>
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="hqDemographicsChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-chart-bar mr-2 text-success"></i>Recovery</h6>
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="hqRecoveryChart"></canvas></div>
                                    <div class="mt-2 text-center">
                                        <span class="badge badge-success px-3 py-2">{{ $this->performance['recovery_rate'] }}% recovery rate</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-7 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-map-marked-alt mr-2 text-success"></i>Top constituencies (recovery %)</h6>
                                    <div class="hq-chart-wrap-sm" wire:ignore><canvas id="hqConstituencyChart"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="font-weight-bold mb-0"><i class="fas fa-inbox mr-2 text-success"></i>{{ $this->actionInbox['inbox_title'] }}</h6>
                                        <span class="badge badge-{{ $this->actionInbox['mode'] === 'secretariat' ? 'primary' : 'warning' }}">{{ $this->actionInbox['pending_phase_count'] }} pending</span>
                                    </div>
                                    @forelse($this->actionInbox['pending_phases'] as $phase)
                                        <div class="hq-inbox-item mb-2">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $phase['label'] }}</strong>
                                                <small class="text-muted">{{ $phase['submitted_at'] }}</small>
                                            </div>
                                            <small class="text-muted">{{ $phase['constituency'] }}</small>
                                            @if(!empty($phase['meta']))
                                                <small class="d-block text-purple">{{ $phase['meta'] }}</small>
                                            @endif
                                            <div class="mt-2 d-flex flex-wrap">
                                                <a href="{{ $phase['url'] }}" class="btn btn-sm btn-{{ $this->actionInbox['mode'] === 'secretariat' ? 'primary' : 'outline-success' }} mr-1 mb-1">
                                                    {{ $phase['action_label'] }}
                                                </a>
                                                @if(!empty($phase['secondary_url']))
                                                    <a href="{{ $phase['secondary_url'] }}" class="btn btn-sm btn-outline-secondary mb-1">
                                                        {{ $phase['secondary_label'] }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">{{ $this->actionInbox['inbox_empty'] }}</p>
                                    @endforelse
                                    <a href="{{ $this->actionInbox['tasks_queue_url'] }}" class="btn btn-link btn-sm px-0 mt-2">{{ $this->actionInbox['tasks_queue_label'] }} →</a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-clipboard-list mr-2 text-success"></i>Applications by constituency (highest to lowest)</h6>
                                    <div class="hq-chart-wrap-sm" wire:ignore><canvas id="hqApplicationVolumeChart"></canvas></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- DATA --}}
                    @if($workspace === 'data')
                        <div class="hq-panel-card p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">Constituency performance</h5>
                                    <p class="text-muted mb-0 small">Single table view — no duplicate KPI cards.</p>
                                </div>
                                <span class="hq-badge-v2">{{ count($this->dataRows) }} rows</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover hq-data-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Constituency</th>
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
                                            <tr><td colspan="4" class="text-center text-muted py-4">No constituency data for the current filters.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <h6 class="font-weight-bold mb-3">Economic sectors</h6>
                                    <div class="hq-chart-wrap-sm" wire:ignore><canvas id="hqSectorChart"></canvas></div>
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

                    {{-- ANALYZE --}}
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
                                    <div class="hq-chart-wrap" wire:ignore><canvas id="hqAnalyzeChart"></canvas></div>
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

                    {{-- TASKS --}}
                    @if($workspace === 'tasks')
                        <div class="row">
                            <div class="col-lg-7 mb-3">
                                <div class="hq-panel-card p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">{{ $this->actionInbox['tasks_title'] }}</h5>
                                        <a href="{{ $this->actionInbox['tasks_queue_url'] }}" class="btn btn-sm btn-success">{{ $this->actionInbox['tasks_queue_label'] }}</a>
                                    </div>
                                    @forelse($this->actionInbox['pending_phases'] as $phase)
                                        <div class="hq-inbox-item mb-2 d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $phase['label'] }}</strong>
                                                <small class="d-block text-muted">{{ $phase['constituency'] }} · {{ $phase['submitted_at'] }}</small>
                                                @if(!empty($phase['meta']))
                                                    <small class="d-block text-purple">{{ $phase['meta'] }}</small>
                                                @endif
                                            </div>
                                            <div class="text-nowrap">
                                                <a href="{{ $phase['url'] }}" class="btn btn-sm btn-{{ $this->actionInbox['mode'] === 'secretariat' ? 'primary' : 'outline-success' }}">
                                                    {{ $phase['action_label'] }}
                                                </a>
                                                @if(!empty($phase['secondary_url']))
                                                    <a href="{{ $phase['secondary_url'] }}" class="btn btn-sm btn-outline-secondary ml-1">
                                                        {{ $phase['secondary_label'] }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted">{{ $this->actionInbox['inbox_empty'] }}</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-lg-5 mb-3">
                                @if($this->isSecretariatDashboard())
                                    <div class="hq-panel-card p-3 h-100">
                                        <h5 class="font-weight-bold mb-3">Secretariat workload</h5>
                                        <p class="text-muted small">
                                            This national view mirrors HQ portfolio analytics. Operational work stays on the
                                            <a href="{{ route('secretariat.phases.index') }}">Secretariat Dashboard</a>
                                            where you sign authorizations — not HQ batch review.
                                        </p>
                                        <ul class="list-unstyled mb-0 small">
                                            <li class="d-flex justify-content-between py-2 border-bottom">
                                                <span>Awaiting authorization</span>
                                                <strong>{{ $this->actionInbox['pending_phase_count'] }}</strong>
                                            </li>
                                            <li class="d-flex justify-content-between py-2">
                                                <span>View</span>
                                                <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}">Pending tasks →</a>
                                            </li>
                                        </ul>
                                    </div>
                                @else
                                <div class="hq-panel-card p-3 h-100">
                                    <h5 class="font-weight-bold mb-3">New applications</h5>
                                    @forelse($this->actionInbox['new_applications'] as $app)
                                        <div class="border-bottom py-2">
                                            <strong>{{ $app->application_id ?? 'Application' }}</strong>
                                            <small class="d-block text-muted">{{ $app->group_name ?? '' }}</small>
                                            <small class="text-success font-weight-bold">KES {{ number_format($app->loan_amount ?? 0) }}</small>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">No new applications for the current scope.</p>
                                    @endforelse
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
