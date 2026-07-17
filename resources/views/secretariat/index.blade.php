@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2 secretariat-page-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
                <span class="sec-hero-badge sec-hero-badge--inline mb-2"><i class="fas fa-stamp mr-1"></i> Head of Secretariat</span>
                <h3 class="font-bold sec-page-title mb-1">Command Center</h3>
                <p class="sec-page-sub mb-0">Review HQ approvals, authorize fund transfer, training, and final disbursement.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-hq-theme-toggle />
                <a href="{{ route('dashboard', ['national' => 1]) }}" class="btn btn-sm sec-btn-primary">
                    <i class="fas fa-chart-line mr-1"></i> National analytics
                </a>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid hq-themed-surface secretariat-page-body pb-4">
    @livewire('secretariat.phase-review-list')
</div>
@endsection
