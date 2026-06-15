@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="{{ route('secretariat.phases.index') }}">Secretariat</a></li>
                <li class="breadcrumb-item active">{{ $phase->phase }}</li>
            </ol>
        </nav>
    </div>
</div>
<div class="container-fluid">
    @livewire('secretariat.phase-review', ['phase' => $phase])
</div>
@endsection
