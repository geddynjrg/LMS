@extends('layouts.main')

@section('main-content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="col-sm-3">
                    <a class="btn btn-sm btn-outline-secondary shadow-sm" href="{{ url()->previous() }}" wire:navigate>
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>

                <div class="col-sm-9 d-flex justify-content-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 m-0 text-sm">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-map-marker-alt mr-1"></i>{{ ucwords($county->slug) }}</a></li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('const.dashboard', ['county' => $county->slug, 'constituency' => $constituency->slug]) }}" wire:navigate>
                                    {{ ucwords(strtolower($constituency->constituency_name)) }}
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('const.groups.index', ['county' => $county->slug, 'constituency' => $constituency->slug]) }}" wire:navigate>
                                    Groups
                                </a>
                            </li>
                            <li class="breadcrumb-item active font-weight-bold text-uppercase">{{ $group->name }}</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-0 font-weight-bold text-dark">
                                <i class="fas fa-clipboard-list text-info mr-2"></i>
                                {{ ucwords(strtolower($constituency->constituency_name)) }} Constituency - Group Information
                            </h3>
                            <p class="text-muted mb-0">{{ $group?->name }}</p>
                        </div>
                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-info px-3 py-2 shadow-sm">
                                <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    @livewire('group-info', ['group'=>$group, 'constituency'=>$constituency, 'county'=>$county])
    <!-- /.content -->
@endsection
