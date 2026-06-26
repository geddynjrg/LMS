@extends('layouts.main')

@section('main-content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="col-sm-3">
                    <a class="btn btn-sm btn-outline-secondary shadow-sm" href="{{ url()->previous() }}" >
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>

                <div class="col-sm-9 d-flex justify-content-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 m-0 text-sm">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-map-marker-alt mr-1"></i>{{ $county->county_name }}</a></li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('const.dashboard', ['county' => $county->slug, 'constituency' => $constituency->slug]) }}" >
                                    {{ ucwords(strtolower($constituency->constituency_name)) }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active font-weight-bold">Applied Loans</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">
                        <span class="text-bold">{{ ucwords(strtolower($constituency->constituency_name)) }} Constituency</span>
                        - <span class="text-cyan text-light">Applied Loans</span>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            @livewire('loan-applicants', ['county' => $county, 'constituency' => $constituency])
        </div>
    </div>
@endsection
