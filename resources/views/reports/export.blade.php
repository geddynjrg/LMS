@extends('layouts.main')

@section('main-content')
    <div class="content-header pt-4">
        <div class="container-fluid">
            {{-- Navigation Row --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a class="btn btn-white btn-sm shadow-sm border rounded-pill p-1 px-3"
                       href="{{ url()->previous() == url()->current() ? route('const.dashboard', ['county' => $county->slug, 'constituency' => $constituency->slug]) : url()->previous() }}">
                        <i class="fas fa-chevron-left mr-1"></i> Back
                    </a>
                </div>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-light border rounded-pill p-1 px-3 mb-0 shadow-sm" style="font-size: 0.85rem;">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-muted">
                                <i class="fas fa-map-marker-alt mr-1"></i> {{ $county->county_name }}
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('const.dashboard', ['county' => $county->slug, 'constituency' => $constituency->slug]) }}" class="text-muted">
                                {{ ucwords(strtolower($constituency->constituency_name)) }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-primary font-weight-bold" aria-current="page">
                            Reports
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="font-weight-bold mb-1">
                        <i class="fas fa-file-export text-info mr-2"></i>
                        {{ ucwords(strtolower($constituency->constituency_name)) }} Reports
                    </h4>
                    <p class="text-muted mb-0">Generate, export, and manage constituency reporting tools.</p>
                </div>
            </div>

            <div class="row">
                @foreach([
                    [
                        'route' => 'const.reports.dynamic-report',
                        'icon' => 'fa-folder-open',
                        'color' => 'primary',
                        'title' => 'Generate Report',
                        'desc' => 'Build custom reports with filters and export to Excel or PDF.',
                    ],
                    [
                        'route' => 'const.reports.codelist',
                        'icon' => 'fa-file-alt',
                        'color' => 'info',
                        'title' => 'Loan Submission / Combined Ledger (F.5)',
                        'desc' => 'Historical loan submission report and combined loan ledger (code list).',
                    ],
                    [
                        'route' => 'const.reports.disbursement',
                        'icon' => 'fa-money-check-alt',
                        'color' => 'success',
                        'title' => 'Disbursement Reports',
                        'desc' => 'Track and export historical loan disbursements.',
                    ],
                    [
                        'route' => 'const.reports.dynamic-report',
                        'icon' => 'fa-chart-pie',
                        'color' => 'secondary',
                        'title' => 'Sectoral Analysis',
                        'desc' => 'Build sector-based historical loan returns (filter by group sector).',
                    ],
                    [
                        'route' => 'const.reports.application-volume',
                        'icon' => 'fa-chart-bar',
                        'color' => 'primary',
                        'title' => 'Apps by Constituency',
                        'desc' => 'View volumes and preview/download constituency, phase, group, and member reports.',
                    ],
                    [
                        'route' => 'const.reports.data-upload',
                        'icon' => 'fa-file-import',
                        'color' => 'warning',
                        'title' => 'Upload Data',
                        'desc' => 'Import constituency data for reporting and analysis.',
                    ],
                    [
                        'route' => 'const.reports.restore-deleted-data',
                        'icon' => 'fa-undo',
                        'color' => 'danger',
                        'title' => 'Restore Deleted Data',
                        'desc' => 'Recover deleted loan phases or groups from automatic SQL backups.',
                    ],
                ] as $report)
                    <div class="col-md-6 col-xl-3 mb-4">
                        <a href="{{ route($report['route'], ['county' => $county->slug, 'constituency' => $constituency->slug]) }}"
                           class="card h-100 shadow-sm border-0 text-decoration-none report-hub-card">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light-{{ $report['color'] }} text-{{ $report['color'] }}"
                                          style="width: 48px; height: 48px;">
                                        <i class="fas {{ $report['icon'] }} fa-lg"></i>
                                    </span>
                                </div>
                                <h6 class="font-weight-bold text-dark mb-2">{{ $report['title'] }}</h6>
                                <p class="text-muted small flex-grow-1 mb-3">{{ $report['desc'] }}</p>
                                <span class="text-{{ $report['color'] }} small font-weight-bold">
                                    Open <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .report-hub-card {
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .report-hub-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
            }
        </style>
    @endpush
@endsection
