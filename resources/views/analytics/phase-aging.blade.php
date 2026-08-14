@extends('layouts.main')

@section('main-content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Constituency phase aging</h1>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        <form method="get" class="form-inline mb-3">
            <label class="mr-2">Min days in phase</label>
            <input type="number" min="0" name="min_days" value="{{ $minDays }}" class="form-control form-control-sm mr-3" style="width: 90px;">
            <label class="mr-2">Constituency code</label>
            <input type="text" name="constituency_code" value="{{ $constituencyCode }}" class="form-control form-control-sm mr-3" placeholder="optional">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
        </form>

        @if(!empty($missingAlerts) && count($missingAlerts))
            <div class="alert alert-warning shadow-sm mb-3">
                <div class="font-weight-bold mb-1">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Missing GFS code / artefact alerts
                    <span class="badge badge-light border ml-1">Assistive</span>
                </div>
                <ul class="mb-0 small pl-3">
                    @foreach($missingAlerts as $alert)
                        <li class="mb-1">
                            <strong>{{ $alert->group_code ?: ('App #'.$alert->phase_application_id) }}</strong>
                            ({{ $alert->constituency_name ?: $alert->constituency_code }} · {{ $alert->phase }} · {{ $alert->app_status }}):
                            {{ implode('; ', $alert->issues) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($suggestions) && count($suggestions))
            <div class="card mb-3 border-primary">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 font-weight-bold">
                        Suggest review next
                        <span class="badge badge-light border ml-1">Assistive triage</span>
                    </h6>
                    <small class="text-muted">Oldest in phase first. SLA warn ≥{{ \App\Services\PhaseAgingAnalyticsService::SLA_WARN_DAYS }} days, breach ≥{{ \App\Services\PhaseAgingAnalyticsService::SLA_BREACH_DAYS }} days.</small>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>Phase</th>
                            <th>Constituency</th>
                            <th>Status</th>
                            <th class="text-right">Apps</th>
                            <th class="text-right">Days</th>
                            <th>SLA</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($suggestions as $s)
                            <tr>
                                <td>{{ $s->phase }}</td>
                                <td>{{ $s->constituency_name ?: $s->constituency_code }}</td>
                                <td>{{ $s->phase_status }}</td>
                                <td class="text-right">{{ $s->application_count }}</td>
                                <td class="text-right font-weight-bold">{{ (int) $s->days_in_phase }}</td>
                                <td>
                                    @if(($s->sla_badge ?? '') === 'breach')
                                        <span class="badge badge-danger">Breach</span>
                                    @elseif(($s->sla_badge ?? '') === 'warn')
                                        <span class="badge badge-warning">Warn</span>
                                    @else
                                        <span class="badge badge-success">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Constituency</th>
                        <th>Code</th>
                        <th>Phase status</th>
                        <th class="text-right">Phases</th>
                        <th class="text-right">Apps</th>
                        <th class="text-right">Avg days</th>
                        <th class="text-right">Max days</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->constituency_name ?: '—' }}</td>
                            <td>{{ $row->constituency_code ?: '—' }}</td>
                            <td>{{ $row->phase_status }}</td>
                            <td class="text-right">{{ $row->phase_count ?? '—' }}</td>
                            <td class="text-right">{{ $row->application_count }}</td>
                            <td class="text-right">{{ number_format((float) $row->avg_days_in_phase, 1) }}</td>
                            <td class="text-right font-weight-bold">{{ (int) $row->max_days_in_phase }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No stuck phases match this filter.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
