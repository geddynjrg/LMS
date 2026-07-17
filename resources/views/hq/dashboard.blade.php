@extends('layouts.main')

@section('main-content')
@php
    $isSecretariatNational = auth()->user()?->hasRole('head_of_secretariat')
        && !auth()->user()?->hasAnyRole(['hq_officer', 'admin', 'super-admin'])
        && request()->boolean('national');
@endphp
<div class="content-header pt-4 pb-2 {{ $isSecretariatNational ? 'secretariat-page-header' : '' }}">
    <div class="container-fluid">
        @if($isSecretariatNational)
            <span class="sec-hero-badge sec-hero-badge--inline mb-2"><i class="fas fa-chart-pie mr-1"></i> National Analytics</span>
        @else
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                    <li class="breadcrumb-item active" aria-current="page">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none text-gray-900">HQ Admin</a>
                    </li>
                </ol>
            </nav>
        @endif

        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="font-bold {{ $isSecretariatNational ? 'sec-page-title' : 'text-gray-900' }} mb-1">
                    {{ $isSecretariatNational ? 'Portfolio KPI Dashboard' : 'Loan Management System Dashboard' }}
                </h3>
                <p class="{{ $isSecretariatNational ? 'sec-page-sub' : 'mt-2 text-gray-600' }} mb-0">
                    {{ $isSecretariatNational
                        ? 'Excel-style animated KPIs, charts, and secretariat queue — authorize from Command Center.'
                        : 'Overview of key metrics and activities' }}
                </p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0 d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                <x-hq-theme-toggle />
                @if($isSecretariatNational)
                    <a href="{{ route('secretariat.phases.index', ['filter' => 'pending']) }}" class="btn btn-sm sec-btn-primary">
                        <i class="fas fa-stamp mr-1"></i> Command Center
                    </a>
                @endif
                <span class="badge badge-pill badge-primary px-3 py-2 shadow-sm">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                </span>
            </div>
        </div>

        @if(!$isSecretariatNational)
            <hr>
        @endif

        <div class="{{ $isSecretariatNational ? 'hq-themed-surface national-page-body pb-4' : '' }}">
        @if(config('dashboard.hq_v2_enabled'))
            @livewire('hq.hq-dashboard-v2')
        @else
            @livewire('hq.hq-dashboard')
        @endif
        </div>
    </div>
</div>
@endsection
