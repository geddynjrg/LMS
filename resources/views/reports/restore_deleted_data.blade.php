@extends('layouts.main')

@section('main-content')
    <div class="content-header pt-4">
        <div class="container-fluid">
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
                        <li class="breadcrumb-item">
                            <a href="#" class="text-muted">Reports</a>
                        </li>
                        <li class="breadcrumb-item active text-primary font-weight-bold" aria-current="page">
                            Restore Deleted Data
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="font-weight-bold mb-1">
                        <i class="fas fa-undo text-primary mr-2"></i>
                        Restore Deleted Data
                    </h4>
                    <p class="text-muted mb-0">
                        Recover loan phases or groups that were deleted with an automatic backup.
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @livewire('reports.restore-deleted-data', ['constituency' => $constituency])
                </div>
            </div>
        </div>
    </div>
@endsection
