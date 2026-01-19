@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-4">
            <i class="fas fa-clock fa-5x text-info opacity-25"></i>
        </div>
        <h1 class="display-1 font-weight-bold">419</h1>
        <h3 class="text-uppercase font-weight-bold">Session Expired</h1>
        <p class="text-muted mb-4">
            For your security, your session has timed out.<br>
            Please <strong>refresh the page</strong> to continue your assessment.
        </p>

        <div class="d-flex justify-content-center">
            <button id="refresh-page-btn" class="btn btn-info shadow-sm px-5">
                <i class="fas fa-sync-alt mr-2"></i> Refresh Page
            </button>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.getElementById('refresh-page-btn').addEventListener('click', function() {
        // Change text to show loading
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Refreshing...';
        this.disabled = true;

        // Logic: Clean URL and add timestamp to bypass cache
        const baseUrl = window.location.origin + window.location.pathname;
        window.location.href = baseUrl + '?rel=' + Date.now();
    });
</script>
@endpush
