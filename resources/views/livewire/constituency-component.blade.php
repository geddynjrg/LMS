<section class="content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="row align-items-center">

                            <div class="col-md-auto">
                                <span class="text-muted small font-weight-bold text-uppercase">
                                    <i class="fas fa-filter mr-1"></i> Filters
                                </span>
                            </div>

                            <div class="col-md-3" wire:key="wards-n-wrapper-{{ $constituencyFilter }}">
                                <x-dropdown-search
                                    wire:key="dropdown-{{ count($this->wardsOptions) }}"
                                    model="wardFilter"
                                    :options="$this->wardsOptions"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select Ward"
                                    icon="fas fa-map-signs"
                                />
                            </div>

                            <div class="col-md-auto ml-auto text-right">
                                <div class="btn-group">
                                    <x-loading-button
                                        target="refreshData"
                                        class="btn-refresh-premium btn-sm"
                                        icon="fas fa-sync-alt"
                                        label="Refresh"
                                    />
                                    <x-loading-button
                                        target="resetFilters"
                                        class="btn-refresh-premium btn-sm"
                                        icon="fas fa-times"
                                        label="Clear Filters"
                                    />
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            @foreach($this->kpis as $kpi)
            <div class="col-md-3 col-sm-6 col-12">
                <div class="card card-hover shadow-sm border-0 mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small text-uppercase font-weight-bold d-block mb-1">{{ $kpi['title'] }}</span>
                                <h5 class="font-weight-bold mb-0">KES {{ number_format($kpi['val']) }}</h5>
                            </div>
                            <div class="icon-circle bg-light-{{ $kpi['color'] }} text-{{ $kpi['color'] }}">
                                <i class="fas {{ $kpi['icon'] }} fa-sm"></i>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px; border-radius: 10px;">
                            <div class="progress-bar bg-{{ $kpi['color'] }}" style="width: {{ $kpi['prog'] }}%"></div>
                        </div>
                        <p class="text-xs text-muted mb-0 mt-2 font-italic">
                            {{ $kpi['desc'] }}
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <hr class="my-4">
        <h5 class="text-muted font-weight-bold mb-3">Performance Metric</h5>
        <div class="row mt-3">
            <div class="col-md-7">
                <div class="card card-hover border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title font-weight-bold text-dark mb-0">
                                    <i class="fas fa-chart-line mr-2"></i>Collection Efficiency
                                </h3><br>
                                <span class="text-xs text-gray-600 mb-0">Assessment of actual vs. scheduled recovery</span>
                            </div>
                            <div class="text-right">
                                <span class="badge {{ $this->performance['recovery_rate'] >= 0 ? 'bg-primary' : 'bg-success' }} rounded-pill px-3 shadow-sm">
                                    {{ $this->performance['recovery_rate'] }}% Performance
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        <div class="row align-items-center">
                            <div class="col-md-5 text-center border-right">
                                <div class="position-relative d-inline-block py-3">
                                    <div class="gauge-container">
                                        <svg viewBox="0 0 100 100" class="gauge-svg">
                                            <circle class="gauge-base" cx="50" cy="50" r="45"></circle>
                                            <circle class="gauge-progress" cx="50" cy="50" r="45"
                                                style="stroke-dashoffset: {{ 283 - (283 * min($this->performance['recovery_rate'], 100) / 100) }};
                                                {{-- 811d70 db17ba dc3545--}}
                                                    stroke: {{ $this->performance['recovery_rate'] >= 90 ? '#811d70' : ($this->performance['recovery_rate'] >= 60 ? '#db17ba' : '#e53c80') }};">
                                            </circle>
                                        </svg>
                                        <div class="gauge-center-content">
                                            @if($this->performance['recovery_rate'] >= 70)
                                                <i class="fas fa-level-up-alt fa-2x animate-bounce"></i>
                                            @elseif($this->performance['recovery_rate'] >= 40)
                                                <i class="fas fa-minus text-primary fa-2x"></i>
                                            @else
                                                <i class="fas fa-level-down-alt text-danger fa-2x animate-pulse"></i>
                                            @endif
                                            <span class="d-block font-weight-bold mt-1 text-lg">{{ $this->performance['recovery_rate'] }}%</span>
                                        </div>
                                    </div>
                                    <small class="text-uppercase text-gray-600 font-weight-bold mt-2 d-block">
                                        Recovery Performance
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-7 pl-md-4">
                                <div class="mb-3">
                                    <span class="text-xs text-gray-600 text-uppercase font-weight-bold d-block mb-1">Status Summary</span>
                                    @if($this->performance['recovery_rate'] >= 100)
                                        <div class="alert alert-soft-primary py-2 px-3 border-0 mb-0">
                                            <small class="font-weight-bold"><i class="fas fa-check-circle mr-1"></i> SURPLUS RECOVERY</small>
                                        </div>
                                    @else
                                        <div class="alert alert-soft-info py-2 px-3 border-0 mb-0">
                                            <small class="font-weight-bold text-info"><i class="fas fa-exclamation-circle mr-1"></i> DEFICIT: KES {{ number_format($this->performance['deficit']) }}</small>
                                        </div>
                                    @endif
                                </div>

                                <ul class="list-group list-group-unbordered">
                                    <li class="list-group-item border-0 d-flex justify-content-between align-items-center py-2">
                                        <span class="text-gray-600 small">Expected Payments</span>
                                        <span class="font-weight-bold text-dark">KES {{ number_format($this->performance['due']) }}</span>
                                    </li>
                                    <li class="list-group-item border-0 d-flex justify-content-between align-items-center py-2">
                                        <span class="text-gray-600 small">Total Collections</span>
                                        <span class="font-weight-bold">KES {{ number_format($this->performance['repaid']) }}</span>
                                    </li>
                                    <li class="list-group-item border-0 pt-3 mt-2 border-top">
                                        <div class="d-flex justify-content-between mb-1 small text-uppercase font-weight-bold">
                                            <span class="text-gray-600">Collection Gap</span>
                                            <span class="text-primary">{{ $this->performance['gap_percentage'] }}% Missing</span>
                                        </div>
                                        <div class="progress rounded-pill shadow-xs" style="height: 6px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $this->performance['gap_percentage'] }}%"></div>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer bg-light border-0 py-2">
                        <div class="row text-center small">
                            <div class="col-6 border-right">
                                <span class="text-gray-600 d-block text-uppercase" style="font-size: 9px;">Total Progress</span>
                                <span class="font-weight-bold">{{ $this->performance['progress_rate'] }}%</span>
                            </div>
                            <div class="col-6">
                                <span class="text-gray-600 d-block text-uppercase" style="font-size: 9px;">Arrears Ratio</span>
                                <span class="font-weight-bold text-danger">{{ $this->performance['arrears_ratio'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card card-hover border-0 shadow-sm h-100 bg-gradient-white">
                    <div class="card-header bg-white border-0 py-3">
                        <h3 class="card-title font-weight-bold text-dark">
                            <i class="fas fa-shield-virus text-danger mr-2"></i>Risk Portfolio
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex justify-content-around mb-4">
                            <div class="text-center">
                                <h3 class="font-weight-bold text-danger mb-0">{{ $this->riskProfile['count'] }}</h3>
                                <small class="text-muted text-uppercase">Groups at Risk</small>
                            </div>
                            <div class="text-center border-left pl-4">
                                <h3 class="font-weight-bold text-dark mb-0">{{ $this->riskProfile['avg_lag'] }}</h3>
                                <small class="text-muted text-uppercase">Avg. Months Lag</small>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl shadow-sm">
                            <div class="d-flex align-items-center">
                                <div class="mr-4 flex-shrink-0">
                                    <div class="bg-white rounded-circle p-3 shadow">
                                        <i class="fas {{ $this->riskProfile['icon'] }} fa-2x text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 font-weight-bold text-uppercase">
                                        Portfolio at Risk (PAR)
                                    </h6>
                                    <p class="display-5 mb-0 font-weight-bold text-gray-900">
                                        {{ number_format($this->performance['financial_rate'], 1) }}%
                                    </p>
                                    <b class="d-block text-xs">
                                        {{ $this->riskProfile['message'] ?? 'of disbursed funds currently overdue' }}
                                    </b>
                                    Overdue: KES {{ number_format(max($this->performance['due'] - $this->performance['repaid'], 0)) }}
                                    out of KES {{ number_format($this->performance['due']) }} expected
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">
        <h5 class="text-muted font-weight-bold mb-3">Target Group Allocation</h5>
        <div class="row mt-4">
            @foreach($this->demographics as $d)
                @php
                    $totalIssued = $this->getStats['totalAmountIssued'] ?? 0;
                    // Calculate share of money, capped at 100%
                    $percentage = ($totalIssued > 0 && $d['cat'])
                        ? min(100, round(($d['amount'] / $totalIssued) * 100))
                        : 0;
                @endphp

                <div class="col-lg-3 col-6">
                    <a href="{{ $d['cat'] ? route('const.groups.index', ['county' => $constituency->county->slug, 'constituency' => $constituency->slug, 'categoryFilter' => $d['cat']]) : '#' }}"
                    class="text-decoration-none">
                        <div class="card card-hover shadow-sm border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-light-{{ $d['color'] }} p-2 rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                        <i class="fas {{ $d['icon'] }} text-{{ $d['color'] }} fa-lg"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        {{-- Primary Value: Money for demographics, Count for Registry --}}
                                        <h4 class="mb-0 font-weight-bold text-dark">
                                            {{ number_format($d['val']) }}
                                        </h4>
                                        <p class="text-muted text-xs text-uppercase mb-0 truncate-text">{{ $d['label'] }}</p>
                                    </div>
                                </div>

                                @if($d['cat'])
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-xs font-weight-bold text-muted">Capital Share</span>
                                            <span class="text-xs font-weight-bold text-{{ $d['color'] }}">{{ $percentage }}%</span>
                                        </div>
                                        <div class="progress rounded-pill" style="height: 4px;">
                                            <div class="progress-bar bg-{{ $d['color'] }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <small class="text-xs text-muted mt-1 d-block font-weight-bold">
                                            KES {{ number_format($d['amount']) }}/=
                                        </small>
                                    </div>
                                @else
                                    <div class="mt-2 pt-1">
                                        <span class="text-xs text-primary font-weight-bold">
                                            <i class="fas fa-database mr-1"></i> Total Registry
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <hr class="my-4">
        <h5 class="text-muted font-weight-bold mb-3 mt-4">Economic Sector Allocation</h5>
        <div class="row">
            @foreach($this->economicSectors as $s)
                @php
                    // We use the same total baseline for the percentage
                    $totalIssued = $this->getStats['totalAmountIssued'] ?? 0;
                    $percentage = $s['percentage']; // Already calculated in the Computed Property
                @endphp

                <div class="col-lg-3 col-6">
                    {{-- Link to filtered groups by sector --}}
                    <a href="{{ route('const.groups.index', ['county' => $constituency->county->slug, 'constituency' => $constituency->slug, 'sectorFilter' => $s['label']]) }}"
                    class="text-decoration-none">
                        <div class="card card-hover shadow-sm border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-light-{{ $s['color'] }} p-2 rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                        <i class="fas {{ $s['icon'] }} text-{{ $s['color'] }} fa-lg"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h4 class="mb-0 font-weight-bold text-dark">
                                            {{ number_format($s['count']) }}
                                        </h4>
                                        <p class="text-muted text-xs text-uppercase mb-0 truncate-text">{{ $s['label'] }}</p>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-xs font-weight-bold text-muted">Capital Share</span>
                                        <span class="text-xs font-weight-bold text-{{ $s['color'] }}">{{ $percentage }}%</span>
                                    </div>
                                    <div class="progress rounded-pill" style="height: 4px;">
                                        <div class="progress-bar bg-{{ $s['color'] }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <small class="text-xs text-muted mt-1 d-block font-weight-bold">
                                        KES {{ number_format($s['amount']) }}/=
                                    </small>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <hr class="my-4">
        <h5 class="text-muted font-weight-bold mb-3 mt-4">Applications and Tasks</h5>
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-file-signature mr-2 text-warning"></i> Incoming Applications
                        </h6>
                        <span class="badge badge-warning-light text-xs">{{ count($this->getStats['newApplications']) }} New</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="text-xs text-uppercase py-2">Application Details</th>
                                        <th class="text-xs text-uppercase py-2">Group & Activity</th>
                                        <th class="text-xs text-uppercase py-2">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->getStats['newApplications'] as $app)
                                    <tr class="position-relative" onclick="Livewire.navigate('{{ route('const.app.application-view', [$constituency->county->slug, $constituency->slug, $app->application_id]) }}')"
                                        style="cursor: pointer;">

                                        <td class="py-3 col-3">
                                            <span class="font-weight-bold text-dark d-block text-truncate" style="max-width: 150px;">
                                                {{ $app->application_id }}
                                            </span>
                                            <span class="text-dark d-block text-truncate" style="max-width: 150px;">
                                                {{ $app->group_name }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="font-weight-bold text-dark d-block text-truncate" style="max-width: 150px;">
                                                {{ ucwords($app->nature_of_group) }} - {{ implode(', ', json_decode($app->business_type, true) ?? []) }}
                                            </span>
                                            <span class="text-xs text-muted">
                                                {{ implode(', ', json_decode($app->key_activities, true) ?? []) }}
                                            </span>
                                        </td>
                                        <td class="py-3 col-3">
                                            <span class="text-sm font-weight-bold">KES {{ number_format($app->loan_amount) }}</span>
                                            <small class="d-block text-xs text-muted">{{ \Carbon\Carbon::parse($app->created_at)->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No new applications to review.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 py-2">
                        <button onclick="Livewire.navigate('{{ route('const.app.app_dashboard', [$constituency->county->slug, $constituency->slug]) }}')" class="btn btn-sm btn-block btn-outline-info border-0 font-weight-bold">
                            Open Application Portal <i class="fas fa-external-link-alt ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-check-circle mr-2 text-pink"></i> Recent Approved Phases</h6>
                        <span class="badge badge-success-light">Active Funding</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="text-xs text-uppercase py-2">Phase ID</th>
                                        <th class="text-xs text-uppercase py-2">Total Value</th>
                                        <th class="text-xs text-uppercase py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->getStats['approvedPhases'] as $phase)
                                    <tr>
                                        <td class="py-3">
                                            <span class="font-bold text-gray-900 d-block">{{ ucwords($phase->phase) }}</span>
                                            <span class="text-xs text-muted">{{ $phase->phase_ref }}</span>
                                        </td>
                                        <td class="py-3 text-sm">KES {{ number_format($phase->total_amount_approved) }}</td>
                                        <td class="py-3">
                                            <span class="text-xs">Ready for Disbursement</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No recently approved phases.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
