@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('accountant.phases.index') }}" class="text-teal">Accounts Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Phase review</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid pb-4">
    @livewire('accountant.phase-review', ['phase' => $phase])
</div>
@endsection
