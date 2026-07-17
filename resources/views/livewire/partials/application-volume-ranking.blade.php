@php
    $stats = $this->applicationVolumeStats;
    $rows = $this->filteredApplicationVolumeRows;
    $maxCount = $stats['max_count'] ?: 1;
@endphp

<div class="application-volume-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
        <div>
            <h6 class="font-weight-bold mb-1">
                <i class="fas fa-clipboard-list mr-2 text-success"></i>{{ $title ?? 'Applications by constituency' }}
            </h6>
            <p class="text-muted small mb-0">
                All {{ number_format($stats['constituency_count']) }} constituencies ranked highest to lowest.
                Counts include portal applications (any workflow stage) and historical/completed phase group records.
            </p>
        </div>
        <span class="hq-badge-v2">{{ number_format($stats['total']) }} total records</span>
    </div>

    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2 mb-md-0">
            <div class="application-volume-kpi">
                <div class="kpi-label">National total</div>
                <div class="kpi-value hq-count-up" data-count-up="{{ $stats['total'] }}">0</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2 mb-md-0">
            <div class="application-volume-kpi">
                <div class="kpi-label">With records</div>
                <div class="kpi-value hq-count-up" data-count-up="{{ $stats['active_constituencies'] }}">0</div>
                <small class="text-muted">of <span class="hq-count-up" data-count-up="{{ $stats['constituency_count'] }}">0</span></small>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2 mb-md-0">
            <div class="application-volume-kpi">
                <div class="kpi-label">Highest volume</div>
                <div class="kpi-value" style="font-size: 1rem;">{{ $stats['top_name'] ?? '—' }}</div>
                <small class="text-muted"><span class="hq-count-up" data-count-up="{{ $stats['top_count'] }}">0</span></small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="application-volume-kpi">
                <div class="kpi-label">Showing</div>
                <div class="kpi-value hq-count-up" data-count-up="{{ count($rows) }}">0</div>
                <small class="text-muted">constituencies</small>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <input type="search"
               wire:model.live.debounce.300ms="applicationVolumeSearch"
               class="form-control form-control-sm"
               placeholder="Search constituency or county…"
               style="max-width: 320px;">
    </div>

    <div class="application-volume-table-wrap table-responsive">
        <table class="table table-sm table-hover application-volume-table mb-0">
            <thead>
                <tr>
                    <th style="width: 3rem;">#</th>
                    <th>Constituency</th>
                    <th>County</th>
                    <th class="text-right">Portal</th>
                    <th class="text-right">Historical</th>
                    <th class="text-right">Total</th>
                    <th style="min-width: 140px;">Share</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    @php
                        $share = $maxCount > 0 ? ($row->application_count / $maxCount) * 100 : 0;
                    @endphp
                    <tr class="{{ $row->application_count === 0 ? 'text-muted' : '' }}">
                        <td class="text-muted">{{ $row->rank ?? ($index + 1) }}</td>
                        <td class="font-weight-bold">{{ $row->constituency_name }}</td>
                        <td>{{ $row->county_name }}</td>
                        <td class="text-right">{{ number_format($row->portal_applications) }}</td>
                        <td class="text-right">{{ number_format($row->historical_records) }}</td>
                        <td class="text-right font-weight-bold">{{ number_format($row->application_count) }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="application-volume-share-track flex-grow-1 mr-2">
                                    <div class="application-volume-share-fill"
                                         data-share-width="{{ $row->application_count > 0 ? max($share, 2) : 0 }}%"
                                         style="width: {{ $row->application_count > 0 ? max($share, 2) : 0 }}%;"></div>
                                </div>
                                <small class="text-muted text-nowrap">{{ number_format($share, 0) }}%</small>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No constituencies match your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
