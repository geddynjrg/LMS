<section class="content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">
                    <i class="fas fa-layer-group mr-2 text-muted"></i>
                    {{ $phase->phase_name ?? 'Phase ' . $phase->phase_number }}
                </h1>
                <small class="text-muted">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Review Date: {{ $phase->review_date?->format('M d, Y') ?? 'Not scheduled' }}
                </small>
            </div>
            <div class="col-sm-6 text-right">
                <a href="#" class="btn btn-default border shadow-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'applications' ? 'active' : '' }}"
                                        wire:click="$set('activeTab', 'applications')">
                                    <i class="fas fa-list-ul mr-1"></i> Applications
                                    <span class="badge badge-primary ml-1">{{ $phase->phaseApplications->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}"
                                        wire:click="$set('activeTab', 'documents')">
                                    <i class="fas fa-file-pdf mr-1"></i> Accompanying Docs
                                    <span class="badge badge-warning ml-1">{{ 5 }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body position-relative">
                        <!-- Loading Overlay -->
                        <div wire:loading.flex wire:target="activeTab"
                             class="position-absolute w-100 h-100 justify-content-center align-items-center bg-white bg-opacity-75"
                             style="z-index: 10;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading phase data...</p>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        @if($activeTab === 'applications')
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Group Name</th>
                                            <th>Amount Requested</th>
                                            <th>Status</th>
                                            <th>Decision</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($phase->phaseApplications as $pa)
                                            <tr>
                                                <td>{{ $pa->tempApplication->application_id }}</td>
                                                <td>{{ $pa->tempApplication->group_name }}</td>
                                                <td>KES {{ number_format($pa->amount_applied, 2) }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $pa->committee_decision === 'approved' ? 'success' : 'warning' }}">
                                                        {{ ucfirst($pa->committee_decision) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($pa->amount_approved)
                                                        KES {{ number_format($pa->amount_approved, 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    No applications assigned to this phase yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @elseif($activeTab === 'documents')
                            <div class="row">
                                @forelse($phase->phaseApplications as $doc)
                                    <div class="col-md-4 mb-3">
                                        <div class="card shadow-sm">
                                            <div class="card-body text-center">
                                                <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                                <h6 class="mb-1">{{ $doc->status }}</h6>
                                                <small class="text-muted">{{ $doc->id }}</small>
                                                <div class="mt-2">
                                                    <a href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="#" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                                        <p>No accompanying documents uploaded for this phase.</p>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar: Status Information -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-navy text-white">
                        <h3 class="card-title font-weight-bold">Status Information</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span><b>Current State</b></span>
                                <span class="badge badge-{{ $phase->status === 'draft' ? 'warning' : ($phase->status === 'submitted' ? 'info' : 'success') }}">
                                    {{ ucfirst($phase->status) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><b>Total Requested</b></span>
                                <span class="text-primary font-weight-bold">
                                    KES {{ number_format($phase->total_amount_applied ?? 0) }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><b>Applications</b></span>
                                <span>{{ $phase->phaseApplications->count() }}</span>
                            </li>
                            @if($phase->status === 'draft')
                                <li class="list-group-item text-center">
                                    <small class="text-muted">Ensure all applications are vetted and minutes are attached before submission.</small>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="card-footer">
                        @if($phase->status === 'draft')
                            <button wire:click="submitToHq" class="btn btn-success btn-block shadow-sm" wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    <i class="fas fa-paper-plane mr-1"></i> Submit to HQ
                                </span>
                                <span wire:loading>
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Submitting...
                                </span>
                            </button>
                        @else
                            <button class="btn btn-outline-secondary btn-block" disabled>
                                <i class="fas fa-lock mr-1"></i> Locked for Review
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<div class="col-md-3">
                <div class="sticky-top" style="top: 50px; z-index: 1020;">
                    <!-- Phase Assignment Section -->
                    <div class="card card-outline card-info {{ !is_null($application->phaseApplication) ? 'collapsed-card' : '' }}">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-layer-group mr-1"></i> Phase Assignment
                            </h3>
                             <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas {{ !is_null($application->phaseApplication) ? 'fa-plus' : 'fa-minus' }}"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(is_null($application->phaseApplication))
                                <p class="text-muted small mb-3">
                                    This application is not assigned to any phase yet.
                                </p>

                                <!-- Dropdown or button to select phase -->
                                <select class="form-control form-control-sm mb-2" wire:model.live="selectedPhaseId">
                                    <option value="">Select Phase...</option>
                                    @foreach($activePhases as $phase)
                                        @if($phase->canAddApplications())
                                        <option value="{{ $phase->id }}">
                                            {{ $phase->phase }} -
                                            {{ $phase->status }}
                                        </option>
                                        @endif
                                    @endforeach
                                </select>

                                <x-loading-button
                                    target="assignToPhase"
                                    class="btn-primary"
                                    icon="fas fa-plus-circle"
                                    label="Assign to Phase"
                                    block="true"
                                    :disabled="!$selectedPhaseId"
                                />
                            @else
                                <p class="text-success mb-2">
                                    <i class="fas fa-check-circle"></i> Assigned to:
                                    <span class="badge badge-info mr-1">{{ $application->phaseApplication->reviewPhase->phase }}</span>
                                </p>
                                @if($application->phaseApplication->status === 'pending')
                                <x-loading-button
                                    target="removeFromPhase"
                                    class="btn-outline-danger"
                                    icon="fas fa-unlink"
                                    label="Remove from Phase"
                                    block="true"
                                    {{-- onclick="confirm('Are you sure you want to remove this application from phase?') || event.stopImmediatePropagation()" --}}
                                />
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Group Linking --}}
                    @if(!is_null($application->phaseApplication))
                    <div class="card card-{{ $application->phaseApplication->group_id ? 'success' : 'warning' }} card-outline {{ $application->phaseApplication->group_id ? 'collapsed-card' : '' }}">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-fingerprint mr-1"></i> Identity Status
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas {{ $application->phaseApplication->group_id ? 'fa-plus' : 'fa-minus' }}"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            @if (!is_null($application->phaseApplication->group_id))
                                <div class="text-center mb-2">
                                    <span class="badge badge-success px-3 py-2">{{ $application->loan_type }} (Linked)</span>
                                </div>
                                <p class="small text-muted mb-1 text-center">
                                    Linked to: <strong>{{ $application->phaseApplication->linkedGroup->name }}</strong> • <strong>{{ $application->phaseApplication->linkedGroup->code }}</strong>
                                </p>
                                @if($application->phaseApplication->status === 'pending')
                                <x-modal-trigger
                                    target="removeLink"
                                    label="Unlink Application"
                                    variant="outline-danger"
                                    icon="fas fa-unlink"
                                    block="true"
                                    {{-- onclick="confirm('Are you sure you want to remove this group link?') || event.stopImmediatePropagation()" --}}
                                />
                                @endif
                            @elseif($application->link_status === 'attempted_no_match')
                                <div class="text-center mb-2">
                                    <span class="badge badge-warning px-3 py-2">{{ $application->loan_type }} (Attempted. No Link Found)</span>
                                </div>
                                <p class="small text-success mb-3 text-bold">
                                    <i class="fas fa-exclamation-triangle"></i> An attempt was posted but no link to a Master Group record.
                                </p>
                                @if($application->phaseApplication->status === 'pending')
                                <x-modal-trigger
                                    target="findMatches"
                                    label="Attempt To Link Again?"
                                    icon="fas fa-link"
                                    variant="primary"
                                    block="true"
                                />
                                @endif
                            @else
                                <div class="text-center mb-2">
                                    <span class="badge badge-warning px-3 py-2">{{ $application->loan_type }} (Not Linked)</span>
                                </div>
                                <p class="small text-success mb-3 text-bold">
                                    <i class="fas fa-exclamation-triangle"></i> Not yet linked to a Master Group record.
                                </p>
                                @if($application->phaseApplication->status === 'pending')
                                <x-modal-trigger
                                    target="findMatches"
                                    label="Link Application"
                                    icon="fas fa-link"
                                    variant="primary"
                                    block="true"
                                />
                                @endif
                            @endif
                        </div>
                    </div>


                    @if(in_array($this->application->link_status, ['attempted_no_match', 'linked']))
                    {{-- Vetting Descisions --}}
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-gavel mr-1"></i> Vetting Decision
                            </h3>
                        </div>
                        <div class="card-body">
                            @if($application->phaseApplication->status === 'pending')
                                {{-- YOUR EXISTING ACTION BUTTONS --}}
                                <div class="btn-group-vertical w-100">
                                    <x-modal-trigger class="mb-2" target="approve" label="APPROVE" icon="fas fa-check-circle" :disabled="$application->link_status === 'not_attempted'" variant="success" block="true" />
                                    <button wire:click="confirmReject" class="btn btn-danger" wire:loading.attr="disabled">
                                        <span wire:loading.remove><i class="fas fa-times-circle"></i> REJECT</span>
                                        <span wire:loading><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                                    </button>
                                </div>
                            @else
                                {{-- SHOW THIS AFTER APPROVAL/REJECTION --}}
                                <div class="text-center mb-3">
                                    @if($application->phaseApplication->status === 'pending_submission_to_hq')
                                        <div class="p-3 border border-success rounded bg-light">
                                            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                                            <h6 class="text-success font-weight-bold">APPLICATION APPROVED</h6>
                                        </div>
                                    @else
                                        <div class="p-3 border border-danger rounded bg-light">
                                            <i class="fas fa-times-circle text-danger fa-3x mb-2"></i>
                                            <h5 class="text-danger font-weight-bold">APPLICATION REJECTED</h5>
                                        </div>
                                    @endif
                                </div>

                                <table class="table table-sm table-borderless small border-top pt-2">
                                    <tr>
                                        <td class="text-muted">Vetted By:</td>
                                        <td class="text-right font-weight-bold">{{ auth()->user()->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Decision Date:</td>
                                        <td class="text-right font-weight-bold">{{ $application->phaseApplication->updated_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                    @if($application->phaseApplication->comments)
                                    <tr>
                                        <td colspan="2" class="text-muted pb-0">Remarks:</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="bg-light p-2 rounded small">
                                            "{{ $application->phaseApplication->comments }}"
                                        </td>
                                    </tr>
                                    @endif
                                </table>

                                <hr>
                                {{-- Navigation Helper for the Officer --}}
                                <button class="btn btn-default btn-block btn-sm shadow-sm" onclick="window.location.href='#'">
                                    <i class="fas fa-arrow-left mr-1"></i> Back to Phase List
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endif

                    {{-- Risk assesment --}}
                    <div wire:key="risk-assessment-section-{{ $application->phaseApplication->group_id ?? 'unlinked' }}">
                        {{-- Check if there are any reasons/flags --}}
                        @if($this->riskFlagsCount > 0)
                            <div class="card {{ $this->riskCategory == 'High Risk' ? 'card-danger' : 'card-warning' }} card-outline mt-3">
                                <div class="card-header">
                                    <h3 class="card-title font-weight-bold">
                                        <i class="fas fa-biohazard mr-1"></i>
                                        Risk Alerts ({{ $this->riskFlagsCount }})
                                    </h3>
                                    <div class="card-tools">
                                        <span class="badge {{ $this->riskCategory == 'High Risk' ? 'badge-danger' : 'badge-warning' }}">
                                            {{ $this->riskCategory }} (Score: {{ $this->riskAssessment['total_score'] }})
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($this->riskFlags as $index => $flag)
                                            <li class="mb-2 {{ $this->riskCategory == 'High Risk' ? 'text-danger' : 'text-warning' }}"
                                                wire:key="flag-{{ $index }}">
                                                <i class="fas fa-exclamation-triangle mr-2"></i> {{ $flag }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <div class="card card-success card-outline mt-3">
                                <div class="card-body p-3">
                                    <p class="text-success mb-0 font-weight-bold">
                                        <i class="fas fa-check-circle mr-2"></i> No risk flags detected.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
