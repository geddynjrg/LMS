@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                <li class="breadcrumb-item active" aria-current="page">
                    <span class="text-teal font-weight-bold">Accounts</span>
                </li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="font-bold text-gray-900 mb-1">
                    <i class="fas fa-calculator text-teal mr-2"></i>Accounts Dashboard
                </h3>
                <p class="text-gray-600 mb-0">
                    Review HQ processed code lists (Excel) and constituency bank reconciliations.
                </p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                <span class="badge badge-pill badge-info px-3 py-2 shadow-sm">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }}
                </span>
            </div>
        </div>

        <hr class="mt-3 mb-0">
    </div>
</div>

<div class="container-fluid pb-4">
    @livewire('accountant.phase-review-list')
</div>
@endsection
