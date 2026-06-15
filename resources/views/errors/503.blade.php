@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-2">
            <img class="rounded-circle" src="{{ asset('images/uwezo_fund_logo.png') }}" style="height: 100px; ">
        </div>
        <h1 class="display-1 font-weight-bold text-dark">503</h1>
        <h3 class="text-uppercase font-weight-bold">System Maintenance</h3>
        <p class="text-muted mb-4">
            The <strong>Uwezo LMS Portal</strong> is currently undergoing scheduled maintenance.<br>
            We will be back online shortly. Thank you for your patience.
        </p>

        <div class="spinner-border text-success mb-3" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
@endsection
