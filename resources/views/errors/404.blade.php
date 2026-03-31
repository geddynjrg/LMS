
@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        @php
            $message = $exception->getMessage();
            $isFileNotFound = str_contains($message, 'File not found');
            $isRecordNotFound = str_contains($message, 'No query results for model');
        @endphp

        <div class="mb-4">
            @if($isFileNotFound)
                {{-- Icon for physical file missing --}}
                <i class="fas fa-file-excel fa-5x text-danger opacity-25"></i>
            @elseif($isRecordNotFound)
                {{-- Icon for database record missing --}}
                <i class="fas fa-database fa-5x text-warning opacity-25"></i>
            @else
                {{-- Default icon --}}
                <i class="fas fa-search-location fa-5x text-success opacity-25"></i>
            @endif
        </div>

        <h1 class="display-1 font-weight-bold text-dark">404</h1>

        <h3 class="text-uppercase letter-spacing-2 font-weight-bold">
            @if($isFileNotFound)
                Attachment Missing
            @elseif($isRecordNotFound)
                Data Entry Missing
            @else
                Page Not Found
            @endif
        </h3>

        <p class="text-muted mb-4 px-5">
            @if($isFileNotFound)
                The record exists, but the **physical file** could not be located on the server storage. Please contact the ICT department.
            @elseif($isRecordNotFound)
                We couldn't find a **database record** matching that ID. It may have been deleted.
            @else
                We couldn't find the <strong>requested Page</strong>.
            @endif
        </p>

        {{-- Always show the specific error message if it's provided --}}
        @if($message)
            <div class="alert alert-light border mx-auto mb-4" style="max-width: 400px; font-size: 0.85rem;">
                <code class="text-danger">Debug Info: {{ $message }}</code>
            </div>
        @endif

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


