@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-4">
            <i class="fas fa-user-shield fa-5x text-danger opacity-25"></i>
        </div>
        <h1 class="display-1 font-weight-bold text-danger">403</h1>
        <h3 class="text-uppercase font-weight-bold text-danger">Access Denied</h1>
        <p class="text-muted mb-4">
            You do not have the required permissions to access this <strong>Data</strong>.<br>
            Please contact the system administrator if you believe this is an error.
        </p>

        <div class="d-flex justify-content-center">
            <a href="{{ url('/dashboard') }}" class="btn btn-dark shadow-sm px-4">
                <i class="fas fa-arrow-left mr-2"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
