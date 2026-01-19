@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-4">
            <i class="fas fa-tools fa-5x text-danger opacity-25"></i>
        </div>
        <h1 class="display-1 font-weight-bold text-danger">500</h1>
        <h3 class="text-uppercase font-weight-bold text-danger">Server Error</h3>
        <p class="text-muted mb-4">
            Something went wrong on our end while processing the <strong>Data</strong>.<br>
            Our technical team has been notified.
        </p>

        <div class="d-flex justify-content-center">
            <a href="mailto:support@uwezo.go.ke" class="btn btn-outline-danger px-4 mr-2">
                <i class="fas fa-envelope mr-1"></i> Report Issue
            </a>
            <button onclick="window.location.reload();" id="refresh-page-btn" class="btn btn-danger shadow-sm px-4">
                <i class="fas fa-sync-alt mr-1"></i> Try Again
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
    });
</script>
@endpush
