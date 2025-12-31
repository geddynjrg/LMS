<section class="content">
    <div class="container-fluid">
        <!-- Header with Status & Actions -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="callout callout-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'info') }}">
                    <h5 class="text-uwezo">Application: <strong>{{ $application->application_id }}</strong></h5>
                    <p>
                        Status: <span class="badge badge-lg badge-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ strtoupper($application->status) }}
                        </span>
                        &nbsp;|&nbsp;
                        Sync: <span class="badge badge-lg badge-{{ $application->sync_status === 'completed' ? 'success' : ($application->sync_status === 'failed' ? 'danger' : 'warning') }}">
                            {{ strtoupper($application->sync_status) }}
                        </span>
                        &nbsp;|&nbsp;
                        Loan Phase: <span class="badge badge-lg badge-{{ is_null($application->phaseApplication)? 'danger' : 'warning' }}">
                            {{ is_null($application->phaseApplication) ? 'Not Assinged' : $application->phaseApplication->reviewPhase->phase}}
                        </span>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-right">
                        <x-loading-button
                            target="refreshData"
                            class="btn-primary"
                            icon="fas fa-sync-alt"
                            label="Refresh"
                        />
                        <button class="btn btn-uwezo btn-sm">
                            <i class="fas fa-download"></i> Institutional Information
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="sticky-top" style="top: 20px; z-index: 1020;">

                    <!-- Phase Assignment Section -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-layer-group mr-1"></i> Phase Assignment
                            </h3>
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

                                <x-loading-button
                                    target="removeFromPhase"
                                    class="btn-outline-danger"
                                    icon="fas fa-unlink"
                                    label="Remove from Phase"
                                    block="true"
                                    onclick="confirm('Are you sure you want to remove this application from phase?') || event.stopImmediatePropagation()"
                                />
                            @endif
                        </div>
                    </div>

                    {{-- Group Linking --}}
                    <div class="card card-{{ $application->group_id ? 'success' : 'warning' }} card-outline">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-fingerprint mr-1"></i> Identity Status
                            </h3>
                        </div>
                        <div class="card-body p-3">
                            @if ($application->link_status === 'linked')
                                <div class="text-center mb-2">
                                    <span class="badge badge-success px-3 py-2">{{ $application->loan_type }} (Linked)</span>
                                </div>
                                <p class="small text-muted mb-1 text-center">
                                    Linked to: <strong>{{ $application->linkedGroup->name }}</strong> • <strong>{{ $application->linkedGroup->code }}</strong>
                                </p>
                                <x-modal-trigger
                                    target="removeLink"
                                    label="Unlink Application"
                                    variant="outline-danger"
                                    icon="fas fa-unlink"
                                    block="true"
                                    onclick="confirm('Are you sure you want to remove this group link?') || event.stopImmediatePropagation()"
                                />
                            @elseif($application->link_status === 'attempted_no_match')
                                <div class="text-center mb-2">
                                    <span class="badge badge-warning px-3 py-2">{{ $application->loan_type }} (Attempted. No Link Found)</span>
                                </div>
                                <p class="small text-success mb-3 text-bold">
                                    <i class="fas fa-exclamation-triangle"></i> An attempt was posted but no link to a Master Group record.
                                </p>
                                <x-modal-trigger
                                    target="findMatches"
                                    label="Attempt To Link Again?"
                                    icon="fas fa-link"
                                    variant="primary"
                                    block="true"
                                />
                            @else
                                <div class="text-center mb-2">
                                    <span class="badge badge-warning px-3 py-2">{{ $application->loan_type }} (Not Linked)</span>
                                </div>
                                <p class="small text-success mb-3 text-bold">
                                    <i class="fas fa-exclamation-triangle"></i> Not yet linked to a Master Group record.
                                </p>

                                <x-modal-trigger
                                    target="findMatches"
                                    label="Link Application"
                                    icon="fas fa-link"
                                    variant="primary"
                                    block="true"
                                />
                            @endif
                        </div>
                    </div>

                    {{-- Vetting Descisions --}}
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-gavel mr-1"></i> Vetting Decision
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-group-vertical w-100">
                                <button wire:click="confirmApprove"
                                        class="btn btn-success mb-2"
                                        @if(!$application->group_id) disabled @endif
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove><i class="fas fa-check-circle"></i> APPROVE</span>
                                    <span wire:loading><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                                </button>

                                <button wire:click="confirmReject"
                                        class="btn btn-danger"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove><i class="fas fa-times-circle"></i> REJECT</span>
                                    <span wire:loading><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-footer text-muted small">
                            Last touched by: {{ auth()->user()->name }}
                        </div>
                    </div>

                    {{-- Risk Assessment --}}
                    @if($this->riskFlagsCount > 0)
                    <div class="card card-danger card-outline">
                        <div class="card-body p-2">
                            <h6 class="text-danger font-weight-bold"><i class="fas fa-biohazard"></i> Risk Alerts ({{ $this->riskFlagsCount }})</h6>
                            <ul class="list-unstyled small mb-0">
                                @foreach($this->riskFlags as $flag)
                                    <li class="mb-1 text-danger">● {{ $flag }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
            <div class="col-md-9">
                <div class="card card-primary card-outline card-tabs">
                    <div class="card-header p-1 pt-1 border-bottom-0">
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'group-loan' ? 'active font-weight-bold' : '' }}"
                                        wire:click="$set('activeTab', 'group-loan')" type="button">
                                    <i class="fas fa-users-cog mr-1"></i> Group & Loan
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'members' ? 'active font-weight-bold' : '' }}"
                                        wire:click="$set('activeTab', 'members')" type="button">
                                    <i class="fas fa-user-friends mr-1"></i>
                                    Members
                                    <span class="badge badge-pill badge-{{ $this->memberConflictCount > 0 ? 'danger' : 'secondary' }} ml-1">
                                        {{ $application->groupMembers->count() }}
                                    </span>
                                    @if($this->memberConflictCount > 0)
                                        <i class="fas fa-exclamation-circle text-danger ml-1" title="Duplicate members detected!"></i>
                                    @endif
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'history' ? 'active font-weight-bold' : '' }}"
                                        wire:click="$set('activeTab', 'history')" type="button">
                                    <i class="fas fa-history mr-1"></i>
                                    Loan History
                                    @if($application->group_id)
                                        <span class="badge badge-success ml-1">Endeleza</span>
                                    @else
                                        <span class="badge badge-info ml-1">Wezesha</span>
                                    @endif
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'documents' ? 'active font-weight-bold' : '' }}"
                                        wire:click="$set('activeTab', 'documents')" type="button">
                                    <i class="fas fa-file-invoice mr-1"></i>
                                    Documents
                                    <span class="badge badge-pill badge-secondary ml-1">{{ count($processedFiles) }}</span>
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link {{ $activeTab === 'audit' ? 'active font-weight-bold' : '' }}"
                                        wire:click="$set('activeTab', 'audit')" type="button">
                                    <i class="fas fa-stream mr-1"></i> Audit Trail
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <!-- Tab Content -->
                        <div class="tab-content mt-4">
                            <!-- Tab 1: Group & Loan -->
                            <div class="tab-pane fade {{ $activeTab === 'group-loan' ? 'show active' : '' }}">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="card card-outline card-primary shadow-sm">
                                            <div class="card-header">
                                                <h3 class="card-title font-weight-bold"><i class="fas fa-id-card mr-1"></i> Group Profile</h3>
                                                <div class="card-tools">
                                                    <span class="badge badge-info">Registered: {{ \Carbon\Carbon::parse($application->date_registered)->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-sm table-bordered table-striped mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <th class="pl-3" width="35%">Group Name</th>
                                                            <td class="text-primary font-weight-bold">{{ $application->group_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th class="pl-3">Registration No.</th>
                                                            <td><code>{{ $application->group_registration_number }}</code></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="pl-3">Nature of Group</th>
                                                            <td><span class="badge badge-outline-secondary text-dark border">{{ ucfirst($application->nature_of_group) }}</span></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="pl-3">Primary Activities</th>
                                                            <td>
                                                                @if(is_array($application->key_activities))
                                                                    @foreach($application->key_activities as $activity)
                                                                        <span class="badge badge-info mr-1">{{ $activity }}</span>
                                                                    @endforeach
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="pl-3">Physical Address</th>
                                                            <td>
                                                                {{ $application->ward_name }}, {{ $application->constituency_name }}<br>
                                                                <small class="text-muted"><i class="fas fa-map-marker-alt"></i> Near {{ $application->nearest_landmark }}</small>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="card shadow-none border mt-3 bg-light">
                                            <div class="card-body py-2">
                                                <div class="mt-4">
                                                    <h6 class="font-weight-bold mb-3"><i class="fas fa-user-shield mr-1"></i> Primary Contact (Officials)</h6>

                                                    @foreach([
                                                        'chairperson' => ['label' => 'Chairperson', 'bg' => 'bg-primary', 'icon' => 'C'],
                                                        'secretary'   => ['label' => 'Secretary', 'bg' => 'bg-info', 'icon' => 'S'],
                                                        'treasurer'   => ['label' => 'Treasurer', 'bg' => 'bg-success', 'icon' => 'T']
                                                    ] as $key => $meta)
                                                        <div class="row align-items-center mb-3">
                                                            <div class="col-auto">
                                                                <div class="avatar-circle {{ $meta['bg'] }} text-white d-flex align-items-center justify-content-center shadow-sm"
                                                                    style="width: 40px; height: 40px; border-radius: 50%; font-weight: bold;">
                                                                    {{ $meta['icon'] }}
                                                                </div>
                                                            </div>
                                                            <div class="col">
                                                                <h6 class="mb-0 font-weight-bold" style="font-size: 0.9rem;">{{ $meta['label'] }}</h6>
                                                                <p class="mb-0 small text-muted">
                                                                    @if(isset($groupOfficials[$key]))
                                                                        {{ $groupOfficials[$key]->name }} | {{ $groupOfficials[$key]->phone_number ?? 'No Phone' }}
                                                                    @else
                                                                        <span class="text-danger">Not assigned</span>
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="card card-outline card-success shadow-sm">
                                            <div class="card-header">
                                                <h3 class="card-title font-weight-bold"><i class="fas fa-money-check-alt mr-1"></i> Loan Details</h3>
                                            </div>
                                            <div class="card-body text-center py-4">
                                                <p class="text-muted mb-1 small uppercase">Requested Amount</p>
                                                <h2 class="text-success font-weight-bold mb-3">KES {{ number_format($application->loan_amount, 2) }}</h2>

                                                <div class="row border-top pt-3">
                                                    <div class="col-6 border-right">
                                                        <p class="small text-muted mb-0">Proposed Repayment Period</p>
                                                        <p class="font-weight-bold">{{ $application->proposed_repayment_period }} Months</p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="small text-muted mb-0">Interest Rate</p>
                                                        <p class="font-weight-bold">0% (Interest Free)</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card shadow-sm border-left-success">
                                            <div class="card-body p-3">
                                                <h6 class="font-weight-bold text-muted small mb-3"><i class="fas fa-university mr-1"></i> BANK DETAILS</h6>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <p class="mb-0 font-weight-bold">{{ $application->bank_name }}</p>
                                                        <p class="mb-0 small text-muted">A/C: {{ $application->bank_account_number }}</p>
                                                        <small class="text-info">Branch: {{ $application->bank_branch }}</small>
                                                    </div>
                                                    <i class="fas fa-credit-card fa-2x text-light"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Business & Expenses -->
                            <div class="tab-pane fade {{ $activeTab === 'business' ? 'show active' : '' }}">
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">Business Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-sm">
                                                    <tr><td width="40%">Nature</td><td><strong>{{ $application->nature_of_business }}</strong></td></tr>
                                                    <tr><td>Years in Operation</td><td><strong>{{ $application->years_in_operation }} years</strong></td></tr>
                                                    <tr><td>Average Income</td><td><strong class="text-success">KES {{ number_format($application->average_income, 2) }}/month</strong></td></tr>
                                                    <tr><td>Business Types</td><td>
                                                        @if(is_array($application->business_type))
                                                            @foreach($application->business_type as $type)
                                                                <span class="badge badge-primary mr-1">{{ $type }}</span>
                                                            @endforeach
                                                        @endif
                                                    </td></tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                @if(count($processedExpenses) > 0)
                                                    <h6>Monthly Expenses</h6>
                                                    <ul class="list-group">
                                                        @foreach($processedExpenses as $expense)
                                                            <li class="list-group-item d-flex justify-content-between">
                                                                {{ $expense['name'] }}
                                                                <strong>KES {{ number_format($expense['amount'], 2) }}</strong>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-muted">No expenses recorded.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 3: Members -->
                            <div class="tab-pane fade {{ $activeTab === 'members' ? 'show active' : '' }}">
                                <div class="card card-warning card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">Group Members ({{ $application->groupMembers->count() }})</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-sm">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Name & ID</th>
                                                        <th>Phone</th>
                                                        <th>Gender</th>
                                                        <th>Position</th>
                                                        <th>Status</th>
                                                        <th>ID Document</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($application->groupMembers as $member)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-circle mr-2">
                                                                    {{ substr($member->name, 0, 1) }}
                                                                </div>
                                                                <div>
                                                                    <strong>{{ $member->name }}</strong><br>
                                                                    <small class="text-muted">{{ $member->id_number }}</small>
                                                                    @if($member->person_with_disability)
                                                                        <small class="badge badge-info">PWD</small>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>{{ $member->phone_number }}</td>
                                                        <td>{{ ucfirst($member->gender) }}</td>
                                                        <td>{{ $member->position ?? 'Member' }}</td>
                                                        <td>
                                                            <span class="badge badge-{{ $member->confirmation_status === 'confirmed' ? 'success' : 'warning' }}">
                                                                {{ ucfirst($member->confirmation_status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($member->photo_member_id && is_array($member->photo_member_id))
                                                                <a href="{{ route('const.app.application.file.download', [
                                                                    'county' => $county->slug ?? $constituency->county->slug,
                                                                    'constituency' => $constituency->slug,
                                                                    'applicationId' => $application->application_id,
                                                                ]) . '?path=' . base64_encode($member->photo_member_id['path']) }}"
                                                                class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-download"></i> ID
                                                                </a>
                                                            @else
                                                                <span class="text-muted">No ID</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info view-member"
                                                                    data-member-id="{{ $member->id_number }}">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 4: Documents -->
                            <div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}">
                                <div class="card card-secondary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title">Documents & Files ({{ count($processedFiles) }})</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="list-group list-group-flush">
                                            @forelse($processedFiles as $file)
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas {{ $file['type'] }} fa-2x mr-3"></i>
                                                        <div>
                                                            <strong>{{ $file['field_name'] }}</strong><br>
                                                            <small class="text-muted">{{ $file['original_filename'] }} • {{ $file['size'] }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        @if($file['view_url'])
                                                            <a href="{{ $file['view_url'] }}" target="_blank" class="btn btn-outline-primary">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        @endif
                                                        @if($file['download_url'])
                                                            <a href="{{ $file['download_url'] }}" class="btn btn-outline-success">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @empty
                                                <div class="text-center py-5 text-muted">
                                                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                                                    <p>No documents uploaded yet.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="position-fixed bottom-0 right-0 p-3" style="z-index: 1500; max-width: 350px;">
            <div id="toasts-container" class="toasts-top-right"></div>
        </div>
    </div>

    {{-- link modal --}}
<div wire:ignore.self class="modal fade" id="modal-group-linker" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            {{-- Loading Overlay for Manual Search/Toggling --}}
            {{-- <div wire:loading wire:target="searchTerm, toggleSearchMode, markAsNoMatch"
                 class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center"
                 style="top:0; left:0; background: rgba(255,255,255,0.7); z-index: 1050; border-radius: .3rem;">
                <div class="text-center">
                    <i class="fas fa-circle-notch fa-spin fa-3x text-primary"></i>
                    <p class="mt-2 font-weight-bold">Processing...</p>
                </div>
            </div> --}}

            <div class="modal-header {{ $isManualSearch ? 'bg-primary' : 'bg-info' }}">
                <h5 class="modal-title text-white">
                    <i class="fas {{ $isManualSearch ? 'fa-search' : 'fa-magic' }} mr-2"></i>
                    {{ $isManualSearch ? 'Manual Group Search' : 'Group Linking Suggestions' }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body">
                {{-- Toggle Buttons --}}
                <div class="d-flex justify-content-end mb-3">
                    <button wire:click="toggleSearchMode" wire:loading.attr="disabled" class="btn btn-sm {{ $isManualSearch ? 'btn-outline-info' : 'btn-outline-primary' }}">
                        <i class="fas {{ $isManualSearch ? 'fa-robot' : 'fa-keyboard' }} mr-1"></i>
                        Switch to {{ $isManualSearch ? 'Automatic Suggestions' : 'Manual Search' }}
                    </button>
                </div>

                {{-- Context Box / Search Bar --}}
                @if(!$isManualSearch)
                    <div class="alert alert-light border border-info">
                        <small class="text-info d-block uppercase font-weight-bold">Automatic Matching For:</small>
                        <strong>{{ $application->group_name }}</strong> ({{ $application->group_registration_number }})
                    </div>
                @else
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" wire:model.live.debounce.300ms="searchTerm"
                                class="form-control form-control-lg border-primary"
                                placeholder="Search Name, Group Code, Reg Number, or GFS Code...">
                            <div class="input-group-append">
                                <span class="input-group-text bg-primary text-white">
                                    <i wire:loading.remove wire:target="searchTerm" class="fas fa-search"></i>
                                    <i wire:loading wire:target="searchTerm" class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </div>
                        <small class="text-muted">Searching within {{ $application->ward->constituency->constituency_name ?? 'Current Constituency' }}</small>
                    </div>
                @endif
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h6 class="mb-0 text-bold">Results</h6>
                    {{-- This will appear and disappear smoothly without blocking clicks --}}
                    <div wire:loading wire:target="searchTerm, toggleSearchMode" class="text-primary small">
                        <i class="fas fa-sync fa-spin mr-1"></i> Updating list...
                    </div>
                </div>
                <div class="table-responsive mt-3" style="max-height: 450px;">
                    <table class="table table-hover table-bordered table-head-fixed text-wrap table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Score</th>
                                <th>Group Identity</th>
                                <th>Ward</th>
                                <th>GFS Code</th>
                                <th>Current Phase</th>
                                <th>Basis of Match (Why?)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suggestedMatches as $match)
                                <tr class="{{ $match->match_score > 90 ? 'table-success' : '' }}">
                                    <td>
                                        <div class="badge badge-pill badge-dark">{{ number_format($match->match_score, 0) }}%</div>
                                    </td>
                                    <td>
                                        <span class="text-bold">{{ $match->name }}</span>
                                        <div class="small">
                                            @if($match->reg_matched)
                                                <span class="text-success text-bold"><i class="fas fa-id-card"></i> Reg. Number Match</span>
                                            @endif
                                            @if($match->phone_matched)
                                                <span class="ml-2 text-primary"><i class="fas fa-phone-check"></i> Phone Match</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $match->ward->ward_name }}</td>
                                    <td>{{ $match->gfs_code }}</td>
                                    <td>{{ $match->currentLoan?->loanPhase->phase ?? 'N/A' }}</td>
                                    <td class="align-middle">
                                        @if(is_array($match->match_reasons) || is_object($match->match_reasons))
                                            @foreach($match->match_reasons as $reason)
                                                <div class="small {{ $reason['class'] }} mb-1">
                                                    <i class="fas {{ $reason['icon'] }} fa-fw mr-1"></i> {{ $reason['text'] }}
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">No specific match details</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Link Button with unique spinner --}}
                                        <button wire:click="confirmLink({{ $match->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="confirmLink({{ $match->id }})"
                                                class="btn btn-xs btn-primary shadow-sm">
                                            <span wire:loading.remove wire:target="confirmLink({{ $match->id }})">
                                                Link Group
                                            </span>
                                            <span wire:loading wire:target="confirmLink({{ $match->id }})">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(count($suggestedMatches) === 0)
                        <div class="text-center p-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-2"></i>
                            <p class="text-muted">{{ $isManualSearch ? 'No results found. Try a different term...' : 'No automatic suggestions found.' }}</p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" wire:click="markAsNoMatch" wire:loading.attr="disabled" class="btn btn-warning">
                    <span wire:loading.remove wire:target="markAsNoMatch">No Matches Exist (Mark as New Group)</span>
                    <span wire:loading wire:target="markAsNoMatch"><i class="fas fa-spinner fa-spin mr-1"></i> Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

</section>




@push('styles')
<style>
.nav-tabs .nav-link {
    color: #495057;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 10px 20px;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background: transparent;
    border-bottom: 3px solid #007bff; /* Blue line under active tab */
}

.nav-tabs .nav-link:hover {
    border-bottom: 3px solid #dee2e6;
}

/* Make the badges slightly larger in the tabs */
.nav-tabs .badge {
    font-size: 0.8rem;
    vertical-align: middle;
}
.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}
.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}
.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 31px;
    margin: 0;
    border-radius: 2px;
}
.timeline > div {
    position: relative;
    margin-right: 10px;
    margin-bottom: 15px;
}
.timeline .time-label span {
    font-weight: 600;
    padding: 5px 10px;
    display: inline-block;
    background-color: #e9ecef;
    border-radius: 4px;
}
.timeline > div > .fa,
.timeline > div > .fas,
.timeline > div > .far,
.timeline > div > .fab,
.timeline > div > .glyphicon,
.timeline > div > .ion {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #666;
    background: #d2d6de;
    border-radius: 50%;
    text-align: center;
    left: 18px;
    top: 0;
}
.timeline > .time-label {
    position: relative;
    margin: 0 0 30px 0;
}
.timeline .timeline-item {
    margin-right: 15px;
    margin-left: 60px;
    margin-top: 0;
    background: #fff;
    color: #444;
    padding: 0;
    position: relative;
    border-radius: 4px;
}
.timeline .timeline-header {
    margin: 0;
    color: #555;
    border-bottom: 1px solid #f4f4f4;
    padding: 10px;
    font-size: 16px;
    line-height: 1.1;
}
.timeline .timeline-body,
.timeline .timeline-footer {
    padding: 10px;
}
.avatar-circle {
    width: 40px;
    height: 40px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #6c757d;
    font-size: 1.2rem;
}
</style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('livewire:init', function () {
            // Listen for success message
            Livewire.on('flash-success', ({ message }) => {
                toastr.success(message);
            });

            // Listen for error message
            Livewire.on('flash-error', ({message}) => {
                toastr.error(message);
            });
        });

        window.addEventListener('open-modal', event => {
            $('#' + event.detail.name).modal('show');
        });

        window.addEventListener('close-modal', event => {
            $('#' + event.detail.name).modal('hide');
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('error', (event) => {
                // This forces the UI to unlock if the server crashes
                console.error('Livewire Error:', event);
            });
        });
    </script>
@endpush
