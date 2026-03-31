@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        {{-- Top Row: Breadcrumbs (Reduced Prominence) --}}
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-gray-900">HQ Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('hq.submissions.tasks.phases') }}" class="text-decoration-none text-gray-900">Tasks</a></li>
                <li class="active breadcrumb-item text-gray-500" aria-current="page">{{$phase->phase}}</li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="font-bold text-gray-900">
                    {{ucwords($phase->phase)}}
                </h3>
                <div class="mt-2 d-flex align-items-center">
                    <span class="bg-gray-50 border-gray-200 text-gray-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">{{ucwords(str_replace('_', ' ', $phase->status))}}</span>
                    <span class="mx-2 text-gray-400">|</span>
                    <span class="text-gray-600 text-xs">
                        <i class="fas fa-calendar-alt mr-1"></i> Submitted To HQ At:
                        <b class="text-dark">{{ $phase->submitted_to_hq_at?->format('M d, Y') ?? 'Not set' }}</b>
                    </span>
                    <span class="mx-2 text-gray-400">|</span>
                    <span class="text-gray-600 text-xs">
                        Submitted By:
                        <b class="text-gray-900">{{ $phase->constituencyOfficer?->email }}</b>
                    </span>
                    <span class="mx-2 text-gray-400">|</span>
                    <span class="text-gray-600 text-xs">
                        <i class="fas fa-map-marker-alt mr-1"></i> {{ $phase->constituency->constituency_name }}
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <span class="badge badge-pill badge-primary px-3 py-2 shadow-sm">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                    </span>
            </div>
        </div>

        <hr>
        @livewire('hq.phase-application-task', ['phase' => $phase])
    </div>
</div>

@endsection
