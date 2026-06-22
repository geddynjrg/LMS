@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">HQ Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Constituency Allocations</li>
            </ol>
        </nav>
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="font-bold text-gray-900 mb-1">
                    <i class="fas fa-coins text-success mr-2"></i>Constituency Allocation Profiles
                </h3>
                <p class="text-gray-600 mb-0">
                    Maintain yearly allocation figures per constituency for processed code lists. Enter online or download a prefilled Excel template, edit, and upload.
                </p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                <span class="badge badge-pill badge-success px-3 py-2 shadow-sm">
                    <i class="fas fa-database mr-1"></i> {{ $currentFY->full }}
                </span>
            </div>
        </div>
        <hr class="mt-3 mb-0">
    </div>
</div>

<div class="container-fluid pb-4">
    @livewire('hq.constituency-allocations')
</div>
@endsection
