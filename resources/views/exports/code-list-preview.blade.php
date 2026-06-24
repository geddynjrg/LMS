@extends('layouts.main')

@section('main-content')
<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-3">
            <div>
                <h1 class="h4 font-weight-bold text-navy mb-1">Processed Codelist Preview</h1>
                <p class="text-muted mb-0">
                    {{ $phase->phase }} · {{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}
                    · Generated {{ $generatedAt->format('d M Y H:i') }}
                </p>
            </div>
            <div class="d-flex flex-wrap" style="gap: 8px;">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Back</a>
                @if(request()->routeIs('hq.*'))
                    <a href="{{ route('hq.submissions.phase.processed_code_list', $phase) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download mr-1"></i> Download Excel
                    </a>
                @elseif(request()->routeIs('secretariat.*'))
                    <a href="{{ route('secretariat.phases.code-list', $phase) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download mr-1"></i> Download Excel
                    </a>
                @elseif(request()->routeIs('accountant.*'))
                    <a href="{{ route('accountant.phases.processed_code_list', $phase) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download mr-1"></i> Download Excel
                    </a>
                @elseif(request()->routeIs('const.*'))
                    <a href="{{ route('const.app.app_phase_processed_code_list', ['county' => request()->route('county'), 'constituency' => request()->route('constituency'), 'phase' => $phase]) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download mr-1"></i> Download Excel
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="pl-3">#</th>
                                <th>Group</th>
                                <th>GFS Code</th>
                                <th>Level</th>
                                <th class="text-right">Applied</th>
                                <th class="text-right pr-3">Approved</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $index => $row)
                                <tr>
                                    <td class="pl-3">{{ $loop->iteration }}</td>
                                    <td>{{ $row['group_name'] }}</td>
                                    <td><code>{{ $row['gfs_code'] }}</code></td>
                                    <td>{{ $row['loan_level'] }}</td>
                                    <td class="text-right">{{ number_format($row['amount_applied'], 2) }}</td>
                                    <td class="text-right pr-3">{{ number_format($row['amount_approved'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No approved applications in this phase.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="4" class="pl-3 text-right">Totals</td>
                                    <td class="text-right">{{ number_format($rows->sum('amount_applied'), 2) }}</td>
                                    <td class="text-right pr-3">{{ number_format($rows->sum('amount_approved'), 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
