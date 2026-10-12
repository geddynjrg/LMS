@extends('layouts.main')

@section('main-content')
    @livewire('hq.application-volume-report', ['constituencyCode' => $constituency->constituency_code])
@endsection
