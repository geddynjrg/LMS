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
