@extends('layouts.main')

@section('main-content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><span class="text-bold">{{ ucwords(strtolower($constituency->constituency_name)) }} Constituency</span> - <span class="text-cyan text-light">Loan Application</span></h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item "><a href="{{route('dashboard')}}">{{ auth()->user()->county->county_name }}</a></li>
                    <li class="breadcrumb-item "> <a href="{{route('const.dashboard',[
                    'county' => $county->slug,
                    'constituency' => $constituency->slug
                    ])}}">{{ ucwords(strtolower($constituency->constituency_name)) }} Constituency</a></li>
                    <li class="breadcrumb-item active">Loan Application</li>
                </ol>
            </div><!-- /.col -->
            </div><!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    @livewire('application', ['constituency'=> $constituency])
    <!-- /.content -->
@endsection
