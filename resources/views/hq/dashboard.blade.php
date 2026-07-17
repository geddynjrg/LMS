@extends('layouts.main')

@section('main-content')
@php
    $isSecretariatNational = auth()->user()?->hasRole('head_of_secretariat')
        && !auth()->user()?->hasAnyRole(['hq_officer', 'admin', 'super-admin'])
        && request()->boolean('national');
@endphp
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                <li class="breadcrumb-item active" aria-current="page">
                    <a href="{{ $isSecretariatNational ? route('dashboard', ['national' => 1]) : route('dashboard') }}" class="text-decoration-none text-gray-900">
                        {{ $isSecretariatNational ? 'National Analytics' : 'HQ Admin' }}
                    </a>
                </li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="font-bold text-gray-900">
                    {{ $isSecretariatNational ? 'National Portfolio Analytics' : 'Loan Management System Dashboard' }}
                </h3>
                <p class="mt-2 text-gray-600">
                    {{ $isSecretariatNational
                        ? 'National metrics and secretariat authorization queue (read-only portfolio view; authorize from Secretariat Dashboard).'
                        : 'Overview of key metrics and activities' }}
                </p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0 d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                <x-hq-theme-toggle />
                <span class="badge badge-pill badge-primary px-3 py-2 shadow-sm">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                </span>
            </div>
        </div>

        <hr>

        @if(config('dashboard.hq_v2_enabled'))
            @livewire('hq.hq-dashboard-v2')
        @else
            @livewire('hq.hq-dashboard')
        @endif
    </div>
</div>
@endsection
