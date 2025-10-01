@extends('layouts.main')

@section('main-content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <a class="float btn btn-sm btn-primary" href="{{ url()->previous() }}"><i class="fas fa-step-backward p-1"></i>Back</a>
            <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><span class="text-bold">{{ ucwords(strtolower($constituency->constituency_name)) }} Constituency</span> - <span class="text-cyan text-light">Loans</span></h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item "><a href="{{route('dashboard')}}">{{ auth()->user()->county->county_name }}</a></li>
                    <li class="breadcrumb-item "> <a href="{{route('const.dashboard',[
                    'county' => $county->slug,
                    'constituency' => $constituency->slug
                    ])}}">{{ ucwords(strtolower($constituency->constituency_name)) }} Constituency</a></li>
                    <li class="breadcrumb-item active"> <a href="">Loans</a></li>
                </ol>
            </div><!-- /.col -->
            </div><!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    @livewire('group-loans', ['county'=>$county, 'constituency'=>$constituency])
    <!-- /.content -->
@endsection
