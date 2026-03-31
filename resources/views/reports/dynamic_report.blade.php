@extends('layouts.main')

@section('main-content')
    <div class="content-header pt-4">
        <div class="container-fluid">
            {{-- Navigation Row --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

                {{-- Left: Smart Back Button --}}
                <div class="d-flex align-items-center">
                    <a class="btn btn-white btn-sm shadow-sm border rounded-pill p-1 px-3"
                       href="{{ url()->previous() == url()->current() ? route('const.app.app_dashboard', ['county' => $county->slug, 'constituency' => $constituency->slug]) : url()->previous() }}"
                       >
                        <i class="fas fa-chevron-left mr-1"></i> Back
                    </a>
                </div>

                {{-- Right: Minimalist Breadcrumbs --}}
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
                            <a href="#" class="text-muted" >
                                Reports
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-primary font-weight-bold" aria-current="page">
                            Dynamic Report
                        </li>
                    </ol>
                </nav>
            </div>

            {{-- Main Livewire Component --}}
            <div class="row">
                <div class="col-12">
                    @livewire('dynamic-reports', ['constituency'=>$constituency])
                </div>
            </div>
        </div>
    </div>
@endsection
