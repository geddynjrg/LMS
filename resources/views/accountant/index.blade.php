@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <h3 class="font-bold text-gray-900">Accounts Review</h3>
        <p class="text-muted mb-0">Review HQ approved code lists and bank reconciliations before management committee approval.</p>
    </div>
</div>
<div class="container-fluid">
    @livewire('accountant.phase-review-list')
</div>
@endsection
