@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-4">
            <i class="fas fa-hand-holding-usd fa-5x text-warning opacity-25"></i>
        </div>
        <h1 class="text-uppercase font-weight-bold">Authorization Required</h1>
        <p class="text-muted mb-4">
            This section requires additional financial clearance or <br>
            <strong>Phase Authorization</strong> before it can be accessed.
        </p>

        <a href="{{ url('/') }}" class="btn btn-dark shadow-sm px-4 text-white">
            <i class="fas fa-home mr-1"></i> Dashboard
        </a>
    </div>
</div>
@endsection
