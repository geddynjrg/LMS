@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        {{-- Top Row: Breadcrumbs (Reduced Prominence) --}}
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-gray-900">HQ Admin</a></li>
                <li class="active breadcrumb-item text-gray-500" aria-current="page">Repayment Receipts</li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="font-bold text-gray-900">
                    Repayment Receipts
                </h3>
                <p class="mt-2 text-gray-600">View all repayment receipts across the system</p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <span class="badge badge-pill badge-primary px-3 py-2 shadow-sm">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                    </span>
            </div>
        </div>

        <hr>
        @livewire('hq.loan-receipts')
    </div>
</div>

@endsection
