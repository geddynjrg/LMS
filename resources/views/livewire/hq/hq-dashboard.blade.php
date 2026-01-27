<div>
    <style>
        .bg-soft-pink { background-color: #fce4ec; color: #e91e63; }
        .bg-soft-purple { background-color: #f3e5f5; color: #9c27b0; }
        .bg-soft-green { background-color: #e8f5e9; color: #4caf50; }
        .bg-soft-orange { background-color: #fff8e1; color: #ffc107; }
        .bg-soft-blue { background-color: #e3f2fd; color: #2196f3; }
        .bg-soft-red { background-color: #ffebee; color: #f44336; }
        .card-custom { border-radius: 12px; border: none; transition: transform 0.2s; }
        .card-custom:hover { transform: translateY(-5px); }
    </style>
    <div class="row mb-4">
    @php
        $moneyCards = [
            [
                'label' => 'Total Disbursed',
                'val' => $financials['total_disbursed'],
                'icon' => 'fa-hand-holding-usd',
                'color' => 'soft-green',
                'desc' => 'Total funds in the field'
            ],
            [
                'label' => 'Approved (Pending Disb)',
                'val' => $financials['total_approved_pending'],
                'icon' => 'fa-check-double',
                'color' => 'soft-blue',
                'desc' => 'Committed but not paid'
            ],
            [
                'label' => 'Amount at Risk (Default)',
                'val' => $financials['total_defaulted_amt'],
                'icon' => 'fa-exclamation-circle',
                'color' => 'soft-red',
                'desc' => 'Active defaults'
            ],
            [
                'label' => 'Total Repayments',
                'val' => $financials['total_repaid'],
                'icon' => 'fa-university',
                'color' => 'soft-purple',
                'desc' => 'Collection to date'
            ],
        ];
    @endphp

    @foreach($moneyCards as $mCard)
    <div class="col-md-3">
        <div class="card card-custom shadow-sm border-left-{{ str_replace('soft-', '', $mCard['color']) }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small text-uppercase mb-1 font-weight-bold">{{ $mCard['label'] }}</p>
                        <h4 class="font-weight-bold mb-0">
                            <span class="text-xs text-muted">KES</span> {{ number_format($mCard['val'] / 1000000, 2) }}M
                        </h4>
                        <p class="text-xs text-muted mb-0">{{ $mCard['desc'] }}</p>
                    </div>
                    <div class="icon-shape p-3 bg-{{ $mCard['color'] }} rounded-circle">
                        <i class="fas {{ $mCard['icon'] }} fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="col-md-4">
    <div class="card card-custom shadow-sm">
        <div class="card-body">
            <h6 class="font-weight-bold mb-4">Portfolio Performance</h6>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">Repayment Rate</span>
                <span class="small font-weight-bold text-success">84%</span>
            </div>
            <div class="progress mb-4" style="height: 10px;">
                <div class="progress-bar bg-success" style="width: 84%"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">Portfolio at Risk (PAR)</span>
                <span class="small font-weight-bold text-danger">12%</span>
            </div>
            <div class="progress mb-4" style="height: 10px;">
                <div class="progress-bar bg-danger" style="width: 12%"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">Pipeline (Applications)</span>
                <span class="small font-weight-bold text-info">KES 4.2M</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-info" style="width: 45%"></div>
            </div>
        </div>
    </div>
</div>

    <div class="row">
        @php
            $topCards = [
                ['label' => 'Total Applications', 'val' => $stats['total_applications'], 'pct' => '+12%', 'icon' => 'fa-file-alt', 'color' => 'soft-pink'],
                ['label' => 'Total Groups Loaned', 'val' => $stats['total_groups_loaned'], 'pct' => '+8%', 'icon' => 'fa-users', 'color' => 'soft-purple'],
                ['label' => 'Total Member Loans', 'val' => $stats['total_member_loans'], 'pct' => '+15%', 'icon' => 'fa-user-friends', 'color' => 'soft-green'],
                ['label' => 'Pending Review', 'val' => $stats['pending_review'], 'pct' => '+5%', 'icon' => 'fa-clock', 'color' => 'soft-orange'],
            ];
        @endphp

        @foreach($topCards as $card)
        <div class="col-md-3 mb-4">
            <div class="card card-custom shadow-sm h-100">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">{{ $card['label'] }}</p>
                        <h3 class="font-weight-bold mb-1">{{ number_format($card['val']) }}</h3>
                        <span class="text-success small font-weight-bold">{{ $card['pct'] }} from last month</span>
                    </div>
                    <div class="p-3 rounded-lg bg-{{ $card['color'] }}" style="height: fit-content;">
                        <i class="fas {{ $card['icon'] }} fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        @php
            $demoCards = [
                ['label' => 'Total Youth Groups', 'val' => $stats['youth_groups'], 'pct' => '+18%', 'icon' => 'fa-user-graduate', 'color' => 'soft-blue'],
                ['label' => 'Women Groups', 'val' => $stats['women_groups'], 'pct' => '+10%', 'icon' => 'fa-female', 'color' => 'soft-pink'],
                ['label' => 'PWD Groups', 'val' => $stats['pwd_groups'], 'pct' => '+22%', 'icon' => 'fa-wheelchair', 'color' => 'soft-blue'],
                ['label' => 'Defaulters', 'val' => $stats['defaulters'], 'pct' => '-3%', 'icon' => 'fa-exclamation-triangle', 'color' => 'soft-red'],
            ];
        @endphp

        @foreach($demoCards as $card)
        <div class="col-md-3 mb-4">
            <div class="card card-custom shadow-sm h-100">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">{{ $card['label'] }}</p>
                        <h4 class="font-weight-bold mb-1">{{ number_format($card['val']) }}</h4>
                        <span class="{{ str_contains($card['pct'], '+') ? 'text-success' : 'text-danger' }} small font-weight-bold">
                            {{ $card['pct'] }} from last month
                        </span>
                    </div>
                    <div class="p-3 rounded-lg bg-{{ $card['color'] }}" style="height: fit-content;">
                        <i class="fas {{ $card['icon'] }} fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card card-custom shadow-sm h-100">
                <div class="card-body">
                    <h6 class="font-weight-bold mb-4">Groups by Activity</h6>
                    @foreach($activities as $activity)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-bold">{{ $activity->name ?? 'Uncategorized' }}</span>
                            <span class="text-muted">{{ $activity->count }} groups</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <div class="progress-bar bg-primary" style="width: 45%;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card card-custom shadow-sm h-100">
                <div class="card-body">
                    <h6 class="font-weight-bold mb-4">Applications by County</h6>
                    @foreach($countyStats as $county)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{{ $county->county_name }}</span>
                        <div class="flex-grow-1 mx-3">
                            <div class="progress" style="height: 6px; border-radius: 10px;">
                                <div class="progress-bar bg-purple" style="width: 60%;"></div>
                            </div>
                        </div>
                        <span class="font-weight-bold">{{ $county->total }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
    <div class="col-md-4">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="font-weight-bold mb-4">Groups by Activity</h6>
                @foreach($activities as $activity)
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <span class="d-block font-weight-bold small">{{ $activity['name'] }}</span>
                            <span class="text-muted text-xs">{{ $activity['count'] }} groups</span>
                        </div>
                        <span class="font-weight-bold small">{{ $activity['percentage'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 10px;">
                        <div class="progress-bar bg-purple" style="width: {{ $activity['percentage'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body">
                <h6 class="font-weight-bold mb-4">Applications by County</h6>
                @foreach($countyApps as $county)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">{{ $county->county_name }}</span>
                        <span class="font-weight-bold small">{{ $county->total }}</span>
                    </div>
                    <div class="progress" style="height: 6px; border-radius: 10px;">
                        <div class="progress-bar bg-primary" style="width: {{ ($county->total / $countyApps->max('total')) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom shadow-sm h-100">
            <div class="card-body p-0">
                <div class="p-3 d-flex justify-content-between align-items-center">
                    <h6 class="font-weight-bold mb-0">Recent Phases Submitted</h6>
                    <a href="#" class="small text-primary font-weight-bold text-decoration-none">View All</a>
                </div>

                <div class="list-group list-group-flush">
                    @forelse($recentPhases as $phase)
                    <div class="list-group-item border-0 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-soft-blue mr-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px; border-radius:50%">
                                    <i class="fas fa-layer-group text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 small font-weight-bold text-dark">{{ $phase->phase_ref }}</h6>
                                    <p class="mb-0 text-muted text-xs">{{ $phase->constituency->constituency_name }} • {{ $phase->phase }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-pill {{ $phase->status == 'submitted_to_hq' ? 'badge-soft-warning' : 'badge-soft-success' }} small px-2">
                                    {{ str_replace('_', ' ', $phase->status) }}
                                </span>
                                <p class="mb-0 mt-1 font-weight-bold small">KES {{ number_format($phase->total_amount_applied) }}</p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="#" class="btn btn-sm btn-block btn-outline-primary text-xs py-1">Review Assessment Data</a>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-muted small">No recent phases pending review.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 mb-5">
    <div class="col-md-4">
        <div class="card bg-primary text-white card-custom shadow-sm border-0">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-file-export fa-2x mr-3 opacity-50"></i>
                    <div>
                        <h6 class="font-weight-bold mb-1">Export National Report</h6>
                        <p class="small mb-0 opacity-75">Download all loan statuses in Excel/PDF</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
