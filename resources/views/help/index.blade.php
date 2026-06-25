@extends('layouts.main')

@section('main-content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-bold">Help &amp; Support</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Help &amp; Support</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title text-sm text-uppercase text-muted mb-0">Quick navigation</h3>
                        </div>
                        <div class="card-body p-0">
                            <nav class="nav nav-pills flex-column">
                                <a class="nav-link" href="#getting-started">Getting started</a>
                                <a class="nav-link" href="#applications">Processing applications</a>
                                <a class="nav-link" href="#training">Training &amp; disbursement</a>
                                <a class="nav-link" href="#reports">Reports &amp; uploads</a>
                                <a class="nav-link" href="#troubleshooting">Troubleshooting</a>
                                <a class="nav-link" href="#contact">Contact support</a>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card card-outline card-info mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                Welcome to the Uwezo Fund LMS help centre. Use the links on the left to find step-by-step
                                guidance for constituency officers, HQ staff, and other LMS users.
                            </p>
                        </div>
                    </div>

                    <div id="getting-started" class="card mb-4 scroll-mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold mb-0">Getting started</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Sign in with your assigned LMS credentials.</li>
                                <li>Select your constituency from the sidebar if you manage more than one.</li>
                                <li>Open <strong>Applications</strong> to view portal submissions linked to your constituency.</li>
                                <li>Use <strong>Tasks</strong> under Applications for phase review work items.</li>
                            </ul>
                        </div>
                    </div>

                    <div id="applications" class="card mb-4 scroll-mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold mb-0">Processing applications</h3>
                        </div>
                        <div class="card-body">
                            <p>Typical constituency workflow:</p>
                            <ol>
                                <li>Review new applications on the Applications dashboard.</li>
                                <li>Link portal applications to LMS records where required.</li>
                                <li>Complete constituency review tasks in the Tasks section.</li>
                                <li>Move approved groups into the correct loan phase for training scheduling.</li>
                            </ol>
                        </div>
                    </div>

                    <div id="training" class="card mb-4 scroll-mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold mb-0">Training &amp; disbursement</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Schedule group training dates from the application phase details screen.</li>
                                <li>Upload training attendance and supporting documents as required.</li>
                                <li>After training, proceed groups to pending final disbursement and complete disbursement records.</li>
                                <li>Applicants see training and disbursement status on the loans portal dashboard.</li>
                            </ul>
                        </div>
                    </div>

                    <div id="reports" class="card mb-4 scroll-mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold mb-0">Reports &amp; uploads</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li><strong>Upload Data</strong> — import constituency phase and group data from templates.</li>
                                <li><strong>Generate Report</strong> — run constituency reports from saved templates.</li>
                                <li><strong>Loan Subm Form (F.5)</strong> — access the standard submission form.</li>
                                <li>HQ users can access national reports from the National Reports menu.</li>
                            </ul>
                        </div>
                    </div>

                    <div id="troubleshooting" class="card mb-4 scroll-mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold mb-0">Troubleshooting</h3>
                        </div>
                        <div class="card-body">
                            <dl>
                                <dt>Application not appearing in LMS</dt>
                                <dd class="mb-3">Confirm the portal application was submitted and synced. Check the Applications dashboard for unlinked records.</dd>
                                <dt>Cannot upload report data</dt>
                                <dd class="mb-3">Verify the correct loan phase is selected and the Excel template matches the current format.</dd>
                                <dt>Training certificate not available to applicant</dt>
                                <dd class="mb-0">Ensure training is marked complete and the scheduled training date has passed.</dd>
                            </dl>
                        </div>
                    </div>

                    <div id="contact" class="card mb-4 scroll-mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title text-bold mb-0">Contact support</h3>
                        </div>
                        <div class="card-body">
                            <p>For LMS technical issues or access problems, contact the Uwezo Fund ICT team:</p>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-envelope text-primary mr-2"></i>
                                    <a href="mailto:loans@uwezo.go.ke">loans@uwezo.go.ke</a>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-globe text-primary mr-2"></i>
                                    <a href="https://loans.uwezo.go.ke/help" target="_blank" rel="noopener">Loans portal help centre</a>
                                    (for applicant-facing questions)
                                </li>
                                <li>
                                    <i class="fas fa-info-circle text-primary mr-2"></i>
                                    Include your constituency name, application ID, and a screenshot when reporting errors.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .scroll-mt-4 { scroll-margin-top: 5rem; }
        nav.nav-pills .nav-link { border-radius: 0; border-bottom: 1px solid #f0f0f0; }
        nav.nav-pills .nav-link:hover { background: #f8f9fa; }
    </style>
@endpush
