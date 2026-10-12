@extends('layouts.main')

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 text-uppercase">
                <li class="breadcrumb-item">
                    <a href="{{ route('const.dashboard', [$constituency->county->slug, $constituency->slug]) }}" class="text-decoration-none text-gray-900">
                        {{ ucwords(strtolower($constituency->constituency_name)) }}
                    </a>
                </li>
                <li class="active breadcrumb-item text-gray-500" aria-current="page">Allocation profile</li>
            </ol>
        </nav>

        <h3 class="font-bold text-gray-900">Allocation profile</h3>
        <p class="mt-2 text-gray-600">Validate the figures maintained by Headquarters. No constituency-side entries.</p>
        <hr>
        @livewire('constituency.allocation-profile-view', ['constituency' => $constituency])
    </div>
</div>
@endsection
