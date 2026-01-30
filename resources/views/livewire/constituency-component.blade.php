<section class="content">
    <div class="container-fluid">
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
        <div class="row mt-3">
            <div class="col-md-7">
                <div class="card card-hover border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title font-weight-bold text-dark mb-0">
                                    <i class="fas fa-chart-line text-pink mr-2"></i>Collection Efficiency
                                </h3><br>
                                <span class="text-xs text-muted mb-0">Assessment of actual vs. scheduled recovery</span>
                            </div>
                            <div class="text-right">
                                <span class="badge {{ $performance['recovery_rate'] >= 0 ? 'bg-info' : 'bg-danger' }} rounded-pill px-3 shadow-sm">
                                    {{ $performance['recovery_rate'] }}% Performance
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
                                                style="stroke-dashoffset: {{ 283 - (283 * min($performance['recovery_rate'], 100) / 100) }};
                                                    stroke: {{ $performance['recovery_rate'] >= 90 ? '#28a745' : ($performance['recovery_rate'] >= 70 ? '#ffc107' : '#dc3545') }};">
                                            </circle>
                                        </svg>
                                        <div class="gauge-center-content">
                                            @if($performance['recovery_rate'] >= 100)
                                                <i class="fas fa-level-up-alt text-pink fa-2x animate-bounce"></i>
                                            @elseif($performance['recovery_rate'] >= 80)
                                                <i class="fas fa-minus text-warning fa-2x"></i>
                                            @else
                                                <i class="fas fa-level-down-alt text-danger fa-2x animate-pulse"></i>
                                            @endif
                                            <span class="d-block font-weight-bold mt-1 text-lg">{{ $performance['recovery_rate'] }}%</span>
                                        </div>
                                    </div>
                                    <small class="text-uppercase text-muted font-weight-bold mt-2 d-block" style="letter-spacing: 1px; font-size: 10px;">
                                        Recovery Performance
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-7 pl-md-4">
                                <div class="mb-3">
                                    <span class="text-xs text-muted text-uppercase font-weight-bold d-block mb-1">Status Summary</span>
                                    @if($this->performance['recovery_rate'] >= 100)
                                        <div class="alert alert-soft-success py-2 px-3 border-0 mb-0">
                                            <small class="font-weight-bold text-pink"><i class="fas fa-check-circle mr-1"></i> SURPLUS RECOVERY</small>
                                        </div>
                                    @else
                                        <div class="alert alert-soft-danger py-2 px-3 border-0 mb-0">
                                            <small class="font-weight-bold text-danger"><i class="fas fa-exclamation-circle mr-1"></i> DEFICIT: KES {{ number_format($this->performance['deficit']) }}</small>
                                        </div>
                                    @endif
                                </div>

                                <ul class="list-group list-group-unbordered">
                                    <li class="list-group-item border-0 d-flex justify-content-between align-items-center py-2">
                                        <span class="text-muted small">Expected Payments</span>
                                        <span class="font-weight-bold text-dark">KES {{ number_format($this->performance['due']) }}</span>
                                    </li>
                                    <li class="list-group-item border-0 d-flex justify-content-between align-items-center py-2">
                                        <span class="text-muted small">Total Collections</span>
                                        <span class="font-weight-bold text-pink">KES {{ number_format($this->performance['repaid']) }}</span>
                                    </li>
                                    <li class="list-group-item border-0 pt-3 mt-2 border-top">
                                        <div class="d-flex justify-content-between mb-1 small text-uppercase font-weight-bold">
                                            <span class="text-muted">Collection Gap</span>
                                            <span class="text-danger">{{ $this->performance['gap_percentage'] }}% Missing</span>
                                        </div>
                                        <div class="progress rounded-pill shadow-xs" style="height: 6px;">
                                            <div class="progress-bar bg-danger" style="width: {{ $this->performance['gap_percentage'] }}%"></div>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer bg-light border-0 py-2">
                        <div class="row text-center small">
                            <div class="col-6 border-right">
                                <span class="text-muted d-block text-uppercase" style="font-size: 9px;">Total Progress</span>
                                <span class="font-weight-bold">{{ $this->performance['progress_rate'] }}%</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block text-uppercase" style="font-size: 9px;">Arrears Ratio</span>
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
                                <h3 class="font-weight-bold text-maroon mb-0">{{ $this->riskProfile['count'] }}</h3>
                                <small class="text-muted text-uppercase">Groups at Risk</small>
                            </div>
                            <div class="text-center border-left pl-4">
                                <h3 class="font-weight-bold text-dark mb-0">{{ $this->riskProfile['avg_lag'] }}</h3>
                                <small class="text-muted text-uppercase">Avg. Months Lag</small>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl shadow-sm {{ $riskProfile['bg_class'] }} mb-4">
                            <div class="d-flex align-items-center">
                                <div class="mr-4 flex-shrink-0">
                                    <div class="bg-white rounded-circle p-3 shadow">
                                        <i class="fas {{ $riskProfile['icon'] }} fa-2x {{ $riskProfile['text_color'] }}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 font-weight-bold text-uppercase small {{ $riskProfile['text_color'] }}">
                                        Portfolio at Risk (PAR)
                                    </h6>
                                    <p class="display-5 mb-0 font-weight-bolder {{ $riskProfile['text_color'] }}">
                                        {{ number_format($performance['financial_rate'], 1) }}%
                                    </p>
                                    <small class="d-block {{ $riskProfile['text_color'] }} opacity-85">
                                        {{ $riskProfile['message'] ?? 'of disbursed funds currently overdue' }}
                                    </small>
                                    Overdue: KES {{ number_format(max($performance['due'] - $performance['repaid'], 0)) }}
                                    out of KES {{ number_format($performance['due']) }} expected
                                </div>
                            </div>
                        </div>

                        <a href="#" class="btn btn-block btn-outline-danger btn-sm mt-3 border-dashed">
                            <i class="fas fa-file-download mr-1"></i> Export Delinquency Report
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <hr class="my-4">
        <h5 class="text-muted font-weight-bold mb-3">Target Group Allocation</h5>
        <div class="row mt-4">
            @foreach($this->demographics as $d)
                @php
                    $totalIssued = $this->stats['totalAmountIssued'] ?? 0;
                    // Calculate share of money, capped at 100%
                    $percentage = ($totalIssued > 0 && $d['cat'])
                        ? min(100, round(($d['amount'] / $totalIssued) * 100))
                        : 0;
                @endphp

                <div class="col-lg-3 col-6">
                    <a href="{{ $d['cat'] ? route('const.groups.index', ['county' => $constituency->county->slug, 'constituency' => $constituency->slug, 'category' => $d['cat']]) : '#' }}"
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
                    $totalIssued = $this->stats['totalAmountIssued'] ?? 0;
                    $percentage = $s['percentage']; // Already calculated in the Computed Property
                @endphp

                <div class="col-lg-3 col-6">
                    {{-- Link to filtered groups by sector --}}
                    <a href="{{ route('const.groups.index', ['county' => $constituency->county->slug, 'constituency' => $constituency->slug, 'sector' => $s['label']]) }}"
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
                        <span class="badge badge-warning-light text-xs">{{ count($this->stats['newApplications']) }} New</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="text-xs text-uppercase py-2">Group & Activity</th>
                                        <th class="text-xs text-uppercase py-2">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->stats['newApplications'] as $app)
                                    <tr>
                                        <td class="py-3">
                                            <span class="font-weight-bold text-dark d-block text-truncate" style="max-width: 150px;">
                                                {{ $app->group_name }}
                                            </span>
                                            <span class="text-xs text-muted">{{ $app->nature_of_business ?? $app->key_activities }}</span>
                                        </td>
                                        <td class="py-3">
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
                        <a href="#" class="btn btn-sm btn-block btn-outline-info border-0 font-weight-bold">
                            Open Application Portal <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
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
                                    @forelse($this->stats['approvedPhases'] as $phase)
                                    <tr>
                                        <td class="py-3">
                                            <span class="font-weight-bold text-dark d-block">Phase #{{ $phase->id }}</span>
                                            <span class="text-xs text-muted">{{ $phase->phase_name }}</span>
                                        </td>
                                        <td class="py-3 text-sm">KES {{ number_format($phase->total_amount) }}</td>
                                        <td class="py-3">
                                            <span class="badge badge-pill badge-success text-xs">Ready for Disbursement</span>
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



        <div class="text-center mt-4">
            <button
                wire:click="refreshStats"
                wire:loading.attr="disabled"
                class="btn btn-primary shadow-sm px-4">

                <span wire:loading.remove>
                    <i class="fas fa-sync-alt mr-2"></i> Refresh Data
                </span>

                <span wire:loading>
                    <i class="fas fa-spinner fa-spin mr-2"></i> Refreshing Dashboard...
                </span>
            </button>
        </div>
        {{-- <p class="text-xs text-muted mt-2">
            <i class="far fa-clock mr-1"></i>
            Data last cached at {{ $lastUpdated ?? 'N/A' }}.
            Click refresh to sync live records.
        </p> --}}
    </div>
</section>

@push('styles')
    <style>
    .alert-soft-danger { background-color: #fceaea; color: #e53e3e; }
    .bg-light-pink { background-color: #fff5f7; }
    .bg-light-orange { background-color: #fffaf0; }
    .bg-light-purple { background-color: #f3f0ff; }
    .bg-light-primary { background-color: #ebf8ff; }
    .btn-round { border-radius: 50px; }
    .info-box-content { overflow: visible !important; }
</style>
<style>
    /* Mimics a modern SaaS dashboard look */
    .icon-circle {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-light-info { background-color: #e0f2fe; }
    .bg-light-success { background-color: #dcfce7; }
    .bg-light-danger { background-color: #fee2e2; }
    .bg-light-dark { background-color: #f3f4f6; }

    .text-xs { font-size: 0.75rem; }
</style>
<style>
    .bg-light-danger { background-color: #fff5f5; border: 1px solid #fed7d7; }
    .bg-light-info { background-color: #f0f9ff; border: 1px solid #bae6fd; }
    .border-dashed { border-style: dashed !important; }
</style>
<style>
    /* Gauge Styles */
    .gauge-container { position: relative; width: 150px; height: 150px; margin: 0 auto; }
    .gauge-svg { transform: rotate(-90deg); width: 100%; height: 100%; }
    .gauge-base { fill: none; stroke: #f1f5f9; stroke-width: 8; }
    .gauge-progress {
        fill: none; stroke-width: 8; stroke-linecap: round;
        stroke-dasharray: 283; transition: stroke-dashoffset 1s ease;
    }
    .gauge-center-content {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%); width: 100%;
    }

    /* Animations */
    .animate-bounce { animation: bounce 2s infinite; }
    .animate-pulse { animation: pulse 2s infinite; }
    @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-5px);} 60% {transform: translateY(-3px);} }
    @keyframes pulse { 0% {opacity: 1;} 50% {opacity: 0.5;} 100% {opacity: 1;} }

    /* Soft Alerts */
    .alert-soft-success { background-color: #f0fdf4; color: #166534; }
    .alert-soft-danger { background-color: #fef2f2; color: #991b1b; }
</style>
<style>
    /* Modern Styling */
    .card-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-radius: 12px;
    }
    .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1) !important;
    }
    .bg-light-pink { background-color: #fff1f2; }
    .text-pink { color: #e11d48 !important; }
    .bg-pink { background-color: #e11d48 !important; }

    .bg-light-orange { background-color: #fff7ed; }
    .bg-light-purple { background-color: #f5f3ff; }
    .bg-light-primary { background-color: #eff6ff; }

    .truncate-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .text-xs { font-size: 0.7rem; letter-spacing: 0.5px; }
</style>
@endpush
@push('scripts')
    <script>
    $(function () {
        $('.knob').knob({
            draw: function () {
                // Style the value text
                $(this.i).css('font-size', '18px').css('font-weight', 'bold');
            }
        });
    });
</script>
@endpush
