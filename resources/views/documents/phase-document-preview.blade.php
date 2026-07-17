@extends('layouts.main')

@php
    $docLabel = \App\Support\PhaseDocumentType::labels()[$doc->document_type] ?? ($doc->title ?: 'Phase document');
@endphp

@section('main-content')
<div class="content-header pt-4 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb bg-transparent p-0 mb-0 small">
                        @if(auth()->user()?->hasRole('accountant'))
                            <li class="breadcrumb-item">
                                <a href="{{ route('accountant.phases.index') }}" class="text-teal">Accounts Dashboard</a>
                            </li>
                            @if($phase)
                                <li class="breadcrumb-item">
                                    <a href="{{ route('accountant.phases.review', $phase) }}" class="text-teal">Phase review</a>
                                </li>
                            @endif
                        @elseif(auth()->user()?->hasRole('head_of_secretariat'))
                            <li class="breadcrumb-item">
                                <a href="{{ route('secretariat.phases.index') }}" style="color:#4f46e5;">Secretariat</a>
                            </li>
                            @if($phase)
                                <li class="breadcrumb-item">
                                    <a href="{{ route('secretariat.phases.review', $phase) }}" style="color:#4f46e5;">Phase review</a>
                                </li>
                            @endif
                        @elseif(auth()->user()?->hasAnyRole(['hq_officer', 'admin', 'super-admin']))
                            <li class="breadcrumb-item">
                                <a href="{{ route('hq.submissions.apps.index') }}" class="text-teal">HQ Submissions</a>
                            </li>
                            @if($phase)
                                <li class="breadcrumb-item">
                                    <a href="{{ route('hq.submissions.phase.review', $phase->slug) }}" class="text-teal">Phase review</a>
                                </li>
                            @endif
                        @elseif(auth()->user()?->hasRole('constituency_officer') && $phase?->constituency)
                            <li class="breadcrumb-item">
                                <a href="{{ route('const.app.app_phase_details', [$phase->constituency->county->slug, $phase->constituency->slug, $phase->slug]) }}" class="text-teal">Phase details</a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active" aria-current="page">Document preview</li>
                    </ol>
                </nav>
                <h1 class="h4 font-weight-bold text-navy mb-1">{{ $docLabel }}</h1>
                <p class="text-muted mb-0 small">
                    @if($phase)
                        {{ $phase->phase }} · {{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}
                        ·
                    @endif
                    {{ $doc->original_filename }}
                </p>
            </div>
            <div class="d-flex flex-wrap" style="gap: 8px;">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <a href="{{ $downloadUrl }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-download mr-1"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content pb-4">
    <div class="container-fluid">
        @if(!empty($fileMissing))
            <div class="card shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body text-center py-5 px-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 72px; height: 72px;">
                        <i class="fas fa-file-exclamation fa-2x text-warning"></i>
                    </div>
                    <h5 class="font-weight-bold text-navy mb-2">Document file not found</h5>
                    <p class="text-muted mb-0 mx-auto" style="max-width: 32rem;">
                        The document record exists but the uploaded file is missing from storage. Ask the constituency or HQ officer to re-upload
                        <strong>{{ $doc->original_filename }}</strong>.
                    </p>
                </div>
            </div>
        @elseif($canEmbed)
            <div class="card shadow-sm border-0 overflow-hidden" style="border-radius: 16px;">
                <div class="card-body p-0 bg-light">
                    <iframe
                        src="{{ $embedUrl }}"
                        title="{{ $docLabel }}"
                        class="w-100 border-0"
                        style="min-height: 80vh; background: #fff;"
                    ></iframe>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body text-center py-5 px-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 72px; height: 72px;">
                        <i class="fas {{ $doc->icon }} fa-2x text-muted"></i>
                    </div>
                    <h5 class="font-weight-bold text-navy mb-2">Preview not available in browser</h5>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 32rem;">
                        This file format cannot be embedded here. Download the document to open it in Word or another compatible application.
                    </p>
                    <a href="{{ $downloadUrl }}" class="btn btn-teal text-white shadow-sm font-weight-bold"
                       style="background:#0d9488;border-color:#0d9488;">
                        <i class="fas fa-download mr-1"></i> Download {{ $doc->original_filename }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
