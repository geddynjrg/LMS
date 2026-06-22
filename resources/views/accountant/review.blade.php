@extends('layouts.main')

@section('main-content')
<div class="container-fluid py-3">
    @livewire('accountant.phase-review', ['phase' => $phase])
</div>
@endsection
