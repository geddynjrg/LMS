@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
                <h3 class="font-bold text-gray-900">Head of Secretariat</h3>
                <p class="text-muted mb-0">Review HQ approvals, authorize fund transfer, training, and final disbursement.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-hq-theme-toggle />
                <a href="{{ route('dashboard', ['national' => 1]) }}" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-chart-line mr-1"></i> National analytics
                </a>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid hq-themed-surface">
    @livewire('secretariat.phase-review-list')
</div>
@endsection
