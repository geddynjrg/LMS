
@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-4">
            <i class="fas fa-search-location fa-5x text-success opacity-25"></i>
        </div>
        <h1 class="display-1 font-weight-bold text-dark">404</h1>
        <h3 class="text-uppercase letter-spacing-2 font-weight-bold">Page Not Found</h3>
        <p class="text-muted mb-4">
            We couldn't find the <strong>requested Page</strong>.
        </p>

        <div class="d-flex justify-content-center gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary px-4 mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Go Back
            </a>
            <a href="{{ url('/dashboard') }}" class="btn btn-success shadow-sm px-4">
                <i class="fas fa-home mr-1"></i> Dashboard
            </a>
        </div>
    </div>
</div>
@endsection


