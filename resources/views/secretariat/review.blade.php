@extends('layouts.main')

@section('main-content')
<div class="container-fluid pb-4">
    @livewire('secretariat.phase-review', ['phase' => $phase])
</div>
@endsection
