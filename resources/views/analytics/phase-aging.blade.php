@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <h1 class="h3 mb-3">Constituency phase aging</h1>
    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label">Min days in phase</label>
            <input type="number" min="0" name="min_days" value="{{ $minDays }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <label class="form-label">Constituency code</label>
            <input type="text" name="constituency_code" value="{{ $constituencyCode }}" class="form-control form-control-sm" placeholder="optional">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary" type="submit">Filter</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead>
            <tr>
                <th>Constituency</th>
                <th>Code</th>
                <th>Phase status</th>
                <th class="text-end">Phases</th>
                <th class="text-end">Apps</th>
                <th class="text-end">Avg days</th>
                <th class="text-end">Max days</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->constituency_name ?: '—' }}</td>
                    <td>{{ $row->constituency_code ?: '—' }}</td>
                    <td>{{ $row->phase_status }}</td>
                    <td class="text-end">{{ $row->phase_count ?? '—' }}</td>
                    <td class="text-end">{{ $row->application_count }}</td>
                    <td class="text-end">{{ number_format((float) $row->avg_days_in_phase, 1) }}</td>
                    <td class="text-end fw-bold">{{ (int) $row->max_days_in_phase }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No stuck phases match this filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
