@extends('layouts.main')

@section('main-content')
    <div class="content-header pt-4">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a class="btn btn-white btn-sm shadow-sm border rounded-pill p-1 px-3"
                       href="{{ url()->previous() == url()->current() ? route('users.viewusers', ['county' => $county->slug]) : url()->previous() }}">
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
                        <li class="breadcrumb-item active text-primary font-weight-bold" aria-current="page">
                            Audit Trail
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <h4 class="font-weight-bold mb-1">
                        <i class="fas fa-history text-primary mr-2"></i>
                        County Audit Trail
                    </h4>
                    <p class="text-muted mb-0">
                        Phase edits, group changes, deletions, and restores recorded for {{ $county->county_name }} County.
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @livewire('reports.county-audit-trail', ['county' => $county])
                </div>
            </div>
        </div>
    </div>
@endsection
