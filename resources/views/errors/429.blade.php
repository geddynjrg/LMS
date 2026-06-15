@extends('layouts.error')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <div class="mb-4">
            <i class="fas fa-hand-paper fa-5x text-info opacity-25"></i>
        </div>
        <h1 class="display-1 font-weight-bold">429</h1>
        <h3 class="text-uppercase font-weight-bold">Slow Down</h3>
        <p class="text-muted mb-4">
            You've made too many requests in a short period.<br>
            Please <strong>wait a minute</strong> before trying again.
        </p>

        <a href="{{ url('/dashboard') }}" class="btn btn-dark shadow-sm px-4 text-white">
            <i class="fas fa-home mr-1"></i> Return Home
        </a>
    </div>
</div>
@endsection
