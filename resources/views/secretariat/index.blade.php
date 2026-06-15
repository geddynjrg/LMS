@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <h3 class="font-bold text-gray-900">Head of Secretariat</h3>
        <p class="text-muted">Review HQ approvals, authorize fund transfer, training, and final disbursement.</p>
    </div>
</div>
<div class="container-fluid">
    @livewire('secretariat.phase-review-list')
</div>
@endsection
