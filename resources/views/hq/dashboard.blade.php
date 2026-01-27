@extends('layouts.main')

@section('main-content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <i class="fas fa-landmark text-primary mr-2"></i>
                        <span class="text-bold">NATIONAL HQ</span>
                        <small class="text-muted ml-2">| Loan Management System</small>
                    </h1>
                    <div class="mt-1">
                        <span class="badge badge-success shadow-sm">
                            <i class="fas fa-circle text-xs mr-1"></i> System Online
                        </span>
                        <span class="text-muted text-xs ml-2">
                            Last synced: {{ now()->format('d M Y, H:i') }}
                        </span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        <ol class="breadcrumb bg-transparent p-0 mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">HQ Command Center</li>
                        </ol>
                        <div class="btn-group shadow-sm">
                            <button type="button" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus-circle mr-1"></i> New Disbursement Batch
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm bg-white">
                                <i class="fas fa-file-export mr-1"></i> National Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            @livewire('hq.hq-dashboard')
        </div>
    </section>
@endsection
