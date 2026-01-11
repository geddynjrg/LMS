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
                            <div class="btn-group-vertical w-100">
                                <x-modal-trigger class="mb-2"
                                    target="approve"
                                    label="APPROVE"
                                    icon="fas fa-check-circle"
                                    :disabled="$application->link_status === 'not_attempted'"
                                    variant="success"
                                    block="true"
                                />

                                <button wire:click="confirmReject"
                                        class="btn btn-danger"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove><i class="fas fa-times-circle"></i> REJECT</span>
                                    <span wire:loading><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>

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
            <div class="col-md-9" wire:ignore.self>
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
                                <button class="nav-link {{ $activeTab === 'loan-history' ? 'active font-weight-bold' : '' }}"
                                        wire:click="$set('activeTab', 'loan-history')" type="button">
                                    <i class="fas fa-history mr-1"></i>
                                    Loan History
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
                    <div class="card-body position-relative">
                        <!-- Tab Content -->
                        <div wire:loading.flex wire:target="activeTab"
                            class="w-100 h-100 position-absolute justify-content-center align-items-center"
                            style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 100px;">
                            <div class="text-center">
                                <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                                <p class="text-navy font-weight-bold mt-2"> Loading Application Data...</p>
                            </div>
                        </div>
                        <div class="tab-content mt-4">
                            <!-- Tab 1: Group & Loan -->
                            @if($activeTab === 'group-loan')
                                <div class="tab-pane fade show active">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="card card-outline card-primary shadow-sm">
                                                <div class="card-header">
                                                    <h3 class="card-title font-weight-bold">
                                                        <i class="fas fa-id-card mr-2 text-primary"></i>Group Profile
                                                    </h3>
                                                    <div class="card-tools">
                                                        <span class="badge badge-light border py-2 px-3">
                                                            <i class="far fa-calendar-alt mr-1 text-primary"></i>
                                                            Registered: {{ \Carbon\Carbon::parse($application->date_registered)->diffForHumans() }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="card-body pt-0">
                                                    <div class="row mt-3">
                                                        <div class="col-md-6 border-right">
                                                            <p class="text-muted text-uppercase small font-weight-bold mb-3">Identification</p>
                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item">
                                                                    <b>Group Name:</b><br>
                                                                    <span class="text-primary font-weight-bold text-uppercase">
                                                                        {{ $application->group_name }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b>Registration No.:</b><br>
                                                                    <span class="text-muted text-dark font-weight-bold">
                                                                        {{ $application->group_registration_number }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>Nature of Group:</b><br>
                                                                    <span class="badge badge-primary py-1 px-3 text-sm">
                                                                        {{ ucfirst($application->nature_of_group) }}
                                                                    </span>
                                                                </li>
                                                                @if($application->linked_status === 'linked')
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>Group code:</b><br>
                                                                    <span class="badge badge-primary py-1 px-3 text-sm">
                                                                        {{ $groupCode }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>GFS code:</b><br>
                                                                    <span class="badge badge-primary py-1 px-3 text-sm">
                                                                        {{ $gfsCode }}
                                                                    </span>
                                                                </li>
                                                                @endif
                                                            </ul>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <p class="text-muted text-uppercase small font-weight-bold mb-3">Activities & Location</p>
                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item">
                                                                    <b>Physical Location:</b><br>
                                                                    <span class="text-left text-dark font-weight-bold">
                                                                        <span>Ward: <span class="text-muted">{{ $application->ward_name }}</span></span>
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b>Landmark</b>
                                                                    <span class="float-right text-muted italic font-weight-bold">
                                                                        <i class="fas fa-map-marker-alt text-danger mr-1 "></i> Near {{ $application->nearest_landmark }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>Key Activities</b>
                                                                    <div class="mt-2 d-flex flex-wrap" style="gap: 4px;">
                                                                        @if(is_array($application->key_activities))
                                                                            @foreach($application->key_activities as $activity)
                                                                                <ul class="text-sm shadow-sm border-bottom-2">
                                                                                    <li class="p-2 text-muted font-weight-bold">{{ $activity }}</li>
                                                                                </ul>
                                                                            @endforeach
                                                                        @endif
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card card-info card-outline shadow-sm">
                                                <div class="card-header">
                                                    <h3 class="card-title font-weight-bold">
                                                        <i class="fas fa-briefcase mr-2 text-info"></i>Business & Financial Assessment
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6 border-right">
                                                            <p class="text-uppercase small font-weight-bold mb-3">Operating Profile</p>
                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item">
                                                                    <b class="text-muted">Nature of Business:</b>
                                                                    <span class="float-right text-dark font-weight-bold">{{ $application->nature_of_business }}</span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b class="text-muted">Years in Operation:</b>
                                                                    <span class="float-right text-dark font-weight-bold">{{ $application->years_in_operation }} Years</span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b class="text-muted">Primary Income:</b>
                                                                    <span class="float-right text-success font-weight-bold">
                                                                        KES {{ number_format($application->average_income, 2) }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b class="text-muted">Business Sector</b>
                                                                    <div class="mt-2">
                                                                        @if(is_array($application->business_type))
                                                                            @foreach($application->business_type as $type)
                                                                                <span class="badge badge-secondary py-1 px-2 text-sm mr-1">{{ $type }}</span>
                                                                            @endforeach
                                                                        @endif
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>

                                                        <div class="col-md-6">
                                                            @php
                                                                $totalExpenses = collect($processedExpenses)->sum('amount');
                                                                $netIncome = $application->average_income - $totalExpenses;
                                                                $expenseRatio = $application->average_income > 0 ? ($totalExpenses / $application->average_income) * 100 : 0;
                                                            @endphp

                                                            <p class="text-uppercase small font-weight-bold mb-3">Monthly Expense Breakdown</p>

                                                            @if(count($processedExpenses) > 0)
                                                                <table class="table table-sm table-borderless mb-2">
                                                                    <tbody>
                                                                        @foreach($processedExpenses as $expense)
                                                                            <tr>
                                                                                <td class="py-1 text-muted">{{ $expense['name'] }}</td>
                                                                                <td class="py-1 text-right font-weight-bold">KES {{ number_format($expense['amount'], 2) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            @else
                                                                <p class="text-muted small text-center my-3">No expenses recorded.</p>
                                                            @endif

                                                            <hr class="my-2">

                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item py-1 border-top-0">
                                                                    <span class="text-muted">Total Monthly Expenses</span>
                                                                    <span class="float-right text-danger font-weight-bold">
                                                                        - KES {{ number_format($totalExpenses, 2) }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item py-2 bg-light px-2 mt-2 rounded">
                                                                    <b class="text-uppercase small">Net Monthly Surplus</b>
                                                                    <span class="float-right {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }} h5 mb-0 font-weight-bold">
                                                                        KES {{ number_format($netIncome, 2) }}
                                                                    </span>
                                                                </li>
                                                            </ul>

                                                            <div class="mt-3">
                                                                <div class="d-flex justify-content-between small mb-1">
                                                                    <span class="text-muted">Expense-to-Income Ratio</span>
                                                                    <span class="font-weight-bold {{ $expenseRatio > 70 ? 'text-danger' : 'text-primary' }}">
                                                                        {{ number_format($expenseRatio, 1) }}%
                                                                    </span>
                                                                </div>
                                                                <div class="progress progress-xxs">
                                                                    <div class="progress-bar {{ $expenseRatio > 70 ? 'bg-danger' : 'bg-primary' }}"
                                                                        style="width: {{ min($expenseRatio, 100) }}%"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card card-outline card-primary shadow-sm mt-3">
                                                <div class="card-header">
                                                    <h3 class="card-title font-weight-bold">
                                                        <i class="fas fa-user-shield mr-2 text-primary"></i>Primary Group Contacts
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <div class="list-group list-group-unbordered">
                                                        @foreach([
                                                            'chairperson' => ['label' => 'Chairperson', 'icon' => 'user-tie', 'color' => 'primary'],
                                                            'secretary'   => ['label' => 'Secretary',   'icon' => 'pen-nib',  'color' => 'info'],
                                                            'treasurer'   => ['label' => 'Treasurer',   'icon' => 'wallet',   'color' => 'success']
                                                        ] as $key => $meta)
                                                            <div class="list-group-item px-0 py-3 border-top-0">
                                                                <div class="row align-items-center">
                                                                    <div class="col-md-3">
                                                                        <span class="text-uppercase small font-weight-bold text-{{ $meta['color'] }}">
                                                                            <i class="fas fa-{{ $meta['icon'] }} mr-1"></i> {{ $meta['label'] }}
                                                                        </span>
                                                                    </div>

                                                                    <div class="col-md-9">
                                                                        @if(isset($groupOfficials[$key]))
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <div>
                                                                                    <span class="h6 font-weight-bold mb-0 text-dark">{{ $groupOfficials[$key]->name }}</span>

                                                                                </div>
                                                                                <div class="text-muted mt-1 font-weight-bold">
                                                                                    <span class="mr-3"><i class="fas fa-phone-alt"></i> {{ $groupOfficials[$key]->phone_number ?? 'N/A' }}</span> <br>
                                                                                    <span><i class="fas fa-id-card text-sm"></i> {{ $groupOfficials[$key]->id_number ?? 'N/A' }}</span>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <span class="text-muted small italic">Official information not captured for this role.</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
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
                                                    <h2 class="font-weight-bold mb-3">KES {{ number_format($application->loan_amount, 2) }}</h2>

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
                                                    <p class="text-muted mb-1 small uppercase border-top mt-3 pt-2">Approved Amount</p>
                                                    <h2 class="text-black-50 font-weight-bold mb-3">KES {{ number_format($application->phaseApplication?->amount_approved, 2) }}</h2>
                                                </div>
                                            </div>
                                            <div class="card card-outline card-success shadow-sm">
                                                <div class="card-header">
                                                    <h3 class="card-title font-weight-bold text-success">
                                                        <i class="fas fa-university mr-2"></i>Bank Settlement Details
                                                    </h3>
                                                </div>
                                                <div class="card-body pt-0">
                                                    {{-- <div class="row align-items-center"> --}}
                                                        <div class="">
                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item">
                                                                    <b>Bank Name:</b>
                                                                    <span class="float-right text-dark font-weight-bold">
                                                                        {{ $application->bank_name }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b>Account Number:</b>
                                                                    <span class="float-right text-primary font-weight-bold" style="letter-spacing: 1px;">
                                                                        {{ $application->bank_account_number }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>Branch Location:</b>
                                                                    <span class="float-right text-muted">
                                                                        {{ $application->bank_branch }}
                                                                    </span>
                                                                </li>
                                                            </ul>
                                                        </div>

                                                        {{-- <div class="col-md-5 text-center py-3">
                                                            <div class="d-none d-md-block">
                                                                <i class="fas fa-piggy-bank fa-3x text-success mb-2 opacity-25" style="opacity: 0.3;"></i>
                                                                <p class="small text-muted mb-0">Verified Disbursement Account</p>
                                                                <span class="badge badge-success px-3 py-1 mt-2">Active Channel</span>
                                                            </div>
                                                        </div> --}}
                                                    {{-- </div> --}}
                                                </div>

                                                <div class="card-footer bg-light py-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-shield-alt mr-1 text-success"></i>
                                                        {{-- Ensure account name matches the registered Group Name before approval. --}}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Tab 2: Members -->
                            @if($activeTab === 'members')
                                <div class="tab-pane fade show active">
                                    <div class="card card-warning card-outline">
                                        <div class="card-header">
                                            <h3 class="card-title">Group Members ({{ $application->groupMembers->count() }})</h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-sm table-bordered" wire:key="members-table-{{ $application->id }}-{{ $this->memberConflictCount }}">
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
                                                                    <div class="avatar-circle mr-2 flex-shrink-0">
                                                                        {{ substr($member->name, 0, 1) }}
                                                                    </div>
                                                                    <div>
                                                                        <strong>{{ $member->name }}</strong><br>
                                                                        <small class="text-muted">{{ $member->id_number }}</small> <br>
                                                                        @if($member->person_with_disability)
                                                                            <small class="badge badge-info">PWD</small>
                                                                        @endif
                                                                        @if($member->is_defaulter)
                                                                            <span class="badge badge-danger mb-1">Defaulter</span>
                                                                            <div class="small text-danger">
                                                                                <strong>Default History:</strong>
                                                                                <ul class="list-unstyled mb-0 mt-1">
                                                                                    @foreach($member->default_history as $history)
                                                                                        <li>
                                                                                            <i class="fas fa-exclamation-triangle mr-1" style="font-size: 0.8rem;"></i>
                                                                                            {{ $history->groupLoan->group->name }}
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </div>
                                                                        @else
                                                                            <span class="badge badge-success">Clean Record</span>
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
                                                                <x-modal-trigger
                                                                    target="viewMember({{ $member->id }})"
                                                                    icon="fas fa-eye"
                                                                    variant="outline-info"
                                                                    size="sm"
                                                                    label=""
                                                                    :block="false"
                                                                />
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($activeTab === 'loan-history')
                            <div class="tab-pane fade show active">
                                <div class="card card-secondary card-outline">
                                    @if ($this->application->link_status === 'linked')
                                        <div class="card-header">
                                            <h3 class="card-title">Prior Loans ({{ count($linkedGroup->groupLoans) }})</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="tab-pane" id="loan-history">
                                                <div class="timeline timeline-inverse mt-3">
                                                    @forelse ($linkedGroup->groupLoans as $loan)
                                                        <div class="time-label">
                                                            <span class="bg-{{ $loan->total_arrears <= 0 ? 'success' : 'danger' }}">
                                                                {{$loan->loanPhase->phase}}: {{ date('l, j F, Y', strtotime($loan->disbursement_date)) }}
                                                                <br>Amount Issued: {{ number_format($loan->amount_issued, 0) }}/=
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <i class="fas fa-history bg-gradient-orange"></i>
                                                            <div class="timeline-item">
                                                                <span class="time"><i class="fas fa-clock"> </i></span>
                                                                <h3 class="timeline-header text-primary text-bold">Amount Repaid - KES {{ number_format($loan->amount_repaid, 0) }}/=
                                                                    <br>
                                                                    <small class="text-gray text-bold">Balance - KES {{ number_format($loan->amount_issued - $loan->amount_repaid, 0) }}/=</small>
                                                                </h3>
                                                                <div class="timeline-body">
                                                                    <div class="card collapsed-card {{$loan->amount_repaid >= $loan->amount_issued?'card-success':'card-danger'}}">
                                                                        <div class="card-header">
                                                                        <div class="row">
                                                                            <div class="col-12">
                                                                            <h3 class="card-title">Receipts</h3>

                                                                            <div class="card-tools float-right">
                                                                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                                                                                </button>
                                                                                <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                                                    <i class="fas fa-expand"></i>
                                                                                </button>

                                                                            </div>
                                                                            <!-- /.card-tools -->
                                                                            </div>

                                                                        </div>

                                                                        </div>
                                                                        <!-- /.card-header -->
                                                                        <div class="card-body table-responsive p-1">
                                                                            <table class="table table-sm">
                                                                                <thead>
                                                                                <tr>
                                                                                    <th style="">#</th>
                                                                                    <th>Reciept</th>
                                                                                    <th>Date</th>
                                                                                    <th>Amount</th>
                                                                                    <th>Ref No.</th>
                                                                                </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                @php
                                                                                    $j = 1;
                                                                                @endphp
                                                                                @foreach ($loan->loanReceipts as $repayment)
                                                                                    <tr>
                                                                                        <td>{{$j++}}.</td>
                                                                                        <td>{{$repayment->receipt_number}}</td>
                                                                                        <td>{{$repayment->transaction_date}}</td>
                                                                                        <td>{{ number_format($repayment->amount, 0)}}/=</td>
                                                                                        <td>{{$repayment->reference_number}}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                        <!-- /.card-body -->
                                                                    </div>
                                                                    <!-- /.card -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty

                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($this->application->link_status === 'attempted_no_match')
                                        <div class="text-center p-5">
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
                                        </div>
                                    @else
                                        <div class="text-center p-5">
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
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Tab 4: Documents -->
                            @if($activeTab === 'documents')
                                <div class="tab-pane fade show active">
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
                            @endif

                           {{-- Tab 5: Audit Trail --}}
                            @if($activeTab === 'audit')
                                <div class="tab-pane active">
                                    <div class="timeline timeline-inverse mt-3">

                                        {{-- 1. Loop through Status/Phase History --}}
                                        @forelse($application->audit_logs ?? [] as $log)
                                            {{-- Note: Replace 'audit_logs' with your actual relationship name, e.g., 'activities' or 'logs' --}}
                                            <div class="time-label">
                                                <span class="bg-gray small">{{ $log->created_at->format('d M, Y') }}</span>
                                            </div>

                                            <div>
                                                {{-- Determine Icon based on action --}}
                                                @php
                                                    $icon = 'fas fa-info-circle bg-primary';
                                                    if(str_contains($log->action, 'Approved')) $icon = 'fas fa-check bg-success';
                                                    if(str_contains($log->action, 'Rejected')) $icon = 'fas fa-times bg-danger';
                                                    if(str_contains($log->action, 'Phase')) $icon = 'fas fa-step-forward bg-info';
                                                @endphp

                                                <i class="{{ $icon }}"></i>
                                                <div class="timeline-item shadow-sm border">
                                                    <span class="time"><i class="fas fa-clock"></i> {{ $log->created_at->format('H:i A') }}</span>

                                                    <h3 class="timeline-header no-border">
                                                        <span class="text-primary font-weight-bold">{{ $log->user->name ?? 'System' }}</span>
                                                        {{ $log->action }}
                                                    </h3>

                                                    @if($log->remarks)
                                                        <div class="timeline-body bg-light m-2 rounded small text-muted">
                                                            <i class="fas fa-comment-dots mr-1"></i> "{{ $log->remarks }}"
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            {{-- 2. Fallback: Show Basic Application Milestones if no logs exist --}}
                                            <div class="time-label">
                                                <span class="bg-success">{{ $application->created_at->format('d M, Y') }}</span>
                                            </div>
                                            <div>
                                                <i class="fas fa-file-import bg-blue"></i>
                                                <div class="timeline-item shadow-sm border">
                                                    <span class="time"><i class="fas fa-clock"></i> {{ $application->created_at->format('H:i A') }}</span>
                                                    <h3 class="timeline-header no-border">Application Submitted via Portal</h3>
                                                </div>
                                            </div>

                                            @if($application->phaseApplication)
                                                <div>
                                                    <i class="fas fa-route bg-info"></i>
                                                    <div class="timeline-item shadow-sm border">
                                                        <span class="time"><i class="fas fa-clock"></i> {{ $application->phaseApplication->created_at->format('H:i A') }}</span>
                                                        <h3 class="timeline-header no-border">
                                                            Assigned to Phase: <span class="badge badge-info">{{ $application->phaseApplication->reviewPhase->name }}</span>
                                                        </h3>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforelse
                                        @forelse($application->activities()->latest()->get() as $activity)
                {{-- Date Label --}}
                <div class="time-label">
                    <span class="bg-gray-light border px-2 small">
                        {{ $activity->created_at->format('d M, Y') }}
                    </span>
                </div>

                <div>
                    {{-- Logic for Icons/Colors --}}
                    @php
                        $icon = 'fas fa-edit bg-primary';
                        if($activity->description === 'created') $icon = 'fas fa-plus bg-success';
                        if($activity->description === 'deleted') $icon = 'fas fa-trash bg-danger';
                        if(isset($activity->properties['attributes']['status'])) $icon = 'fas fa-sync bg-info';
                    @endphp

                    <i class="{{ $icon }}"></i>

                    <div class="timeline-item shadow-sm">
                        <span class="time"><i class="fas fa-clock"></i> {{ $activity->created_at->format('H:i') }}</span>

                        <h3 class="timeline-header no-border">
                            <span class="text-primary font-weight-bold">
                                {{ $activity->causer->name ?? 'System' }}
                            </span>
                            {{ ucfirst($activity->description) }} application
                        </h3>

                        @if($activity->properties->has('attributes'))
                            <div class="timeline-body p-2">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                    <tr class="bg-light">
                                        <th>Field</th>
                                        <th>Previous</th>
                                        <th>New Value</th>
                                    </tr>
                                    @foreach($activity->properties['attributes'] as $key => $value)
                                        <tr>
                                            <td class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td class="text-muted">{{ $activity->properties['old'][$key] ?? 'N/A' }}</td>
                                            <td class="text-success">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center p-5">
                    <p class="text-muted">No audit logs found for this application.</p>
                </div>
            @endforelse

                                        {{-- Final Icon --}}
                                        <div>
                                            <i class="fas fa-dot-circle bg-gray"></i>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="tab-pane fade show active">
                                <div wire:loading wire:target="$set('activeTab', 'members')" class="text-center p-5">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2">Analyzing member risk profiles...</p>
                                </div>

                                <div wire:loading.remove wire:target="$set('activeTab', 'members')">
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
    <div wire:ignore.self class="modal fade shadow-lg" id="modal-group-linker" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-header {{ $isManualSearch ? 'bg-primary' : 'bg-info' }}">
                    <h5 class="modal-title text-white">
                        <i class="fas {{ $isManualSearch ? 'fa-search' : 'fa-magic' }} mr-2"></i>
                        {{ $isManualSearch ? 'Manual Group Search' : 'Group Linking Suggestions' }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body position-relative">
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
                                    <th>Group Code</th>
                                    <th>Current Phase</th>
                                    <th>Basis of Match (Why?)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <div wire:loading.flex wire:target="searchTerm, toggleSearchMode, markAsNoMatch"
                                    class="w-100 h-100 position-absolute justify-content-center align-items-center"
                                    style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 100px;">
                                    <div class="text-center">
                                        <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                                        <p class="text-navy font-weight-bold mt-2"> Loading...</p>
                                    </div>
                                </div>
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
                                        <td>{{ $match->code }}</td>
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

    {{-- member modal --}}
    <div wire:ignore.self class="modal fade shadow-lg" id="memberModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content border-0">
                @if($selectedMember)
                <div class="modal-header bg-light border-bottom-0">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm mr-3"
                            style="width: 50px; height: 50px; border-radius: 50%; font-size: 1.2rem; font-weight: bold;">
                            {{ substr($selectedMember->name, 0, 1) }}
                        </div>
                        <div>
                            <h5 class="modal-title font-weight-bold">{{ $selectedMember->name }}</h5>
                            <span class="text-muted small">ID Number: {{ $selectedMember->id_number }}</span>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <p class="text-muted text-uppercase small font-weight-bold mb-3">Personal Details</p>
                            <ul class="list-group list-group-unbordered">
                                <li class="list-group-item">
                                    <b>Phone Number</b> <span class="float-right text-dark">{{ $selectedMember->phone_number }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>Gender</b> <span class="float-right text-dark">{{ ucfirst($selectedMember->gender) }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b>Group Position</b> <span class="float-right badge badge-info">{{ $selectedMember->position ?? 'Member' }}</span>
                                </li>
                                <li class="list-group-item border-bottom-0">
                                    <b>PWD Status</b>
                                    <span class="float-right">
                                        @if($selectedMember->person_with_disability)
                                            <span class="badge badge-warning"><i class="fas fa-wheelchair mr-1"></i> PWD Registered</span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <p class="text-muted text-uppercase small font-weight-bold mb-3">Credit Risk Assessment</p>

                            @if($selectedMember->is_defaulter)
                                <div class="alert alert-danger shadow-sm border-danger py-3 mb-3" style="background-color: #fff5f5;">
                                    <h6 class="text-danger font-weight-bold small mb-2">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> HIGH RISK: DEFAULT HISTORY DETECTED
                                    </h6>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr class="text-muted border-bottom">
                                                    <th>Group Name</th>
                                                    <th>Role in Group</th>
                                                    <th class="text-right">Default Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($selectedMember->default_history as $history)
                                                    <tr>
                                                        <td class="">

                                                            <a href="{{ route('const.groups.group', [
                                                                'county' => $county->slug,
                                                                'constituency' => $constituency->slug,
                                                                'group' => $history->groupLoan->group->slug
                                                            ]) }}" class="font-weight-bold text-dark">
                                                                {{ $history->groupLoan->group->name }}
                                                            </a>
                                                            <small class="text-uwezo">Constituency: {{ $history->groupLoan->group->ward->constituency->constituency_name }}</small> <br>
                                                            <small class="text-uwezo">Ward: {{ $history->groupLoan->group->ward->ward_name }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light border">
                                                                {{ ucfirst($history->role ?? 'Member') }}

                                                            </span>
                                                            <br>
                                                            <small class="text-uwezo">{{ $history->groupLoan->loanPhase->phase }}</small>
                                                        </td>
                                                        <td class="text-right text-danger font-weight-bold">
                                                            KES {{ number_format($history->groupLoan->total_arrears ?? 0) }}
                                                            <br>
                                                            <small class="text-uwezo">Amount Issued: {{ number_format($history->groupLoan->amount_issued) }}</small>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="border-top">
                                                    <td colspan="2" class="text-success font-weight-bold">Total Exposure</td>
                                                    <td class="text-right text-danger font-weight-bold h6">
                                                        KES {{ number_format($selectedMember->default_history->sum(fn($h) => $h->groupLoan->total_arrears ?? 0)) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-success-light border-success py-3 mb-3 shadow-sm" style="background-color: #f0fff4;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                                        <div>
                                            <span class="text-success d-block font-weight-bold">Clean Credit Record</span>
                                            <small class="text-muted">No prior default history found across the system for this ID.</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <p class="text-muted text-uppercase small font-weight-bold mb-2">Attached Documents</p>
                            <div class="d-flex flex-wrap">
                                @if($selectedMember->photo_member_id)
                                    <a href="#" class="btn btn-app bg-light m-1 border">
                                        <i class="fas fa-id-card text-primary"></i> ID Copy
                                    </a>
                                @endif

                                @if($selectedMember->person_with_disability && $selectedMember->pwd_certificate)
                                    <a href="#" class="btn btn-app bg-light m-1 border">
                                        <i class="fas fa-file-medical text-warning"></i> PWD Cert
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">Close Profile</button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- modal approve --}}
    <div wire:ignore.self class="modal fade" id="modal-approve" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md">
            <form wire:submit.prevent="confirmApproval">
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i> Approve Application</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            <small><i class="fas fa-info-circle mr-1"></i>
                                Applied Amount: <strong>KES {{ number_format($application->loan_amount, 2) }}</strong>

                            </small>
                        </div>

                        {{-- CASE 1: NOT LINKED - Needs Group Identification --}}
                        @if($application->link_status !== 'linked')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Group Code <span class="text-danger">*</span></label>
                                        <input type="text" wire:model.blur="groupCode" class="form-control form-control-sm" placeholder="Enter Code">
                                        @error('groupCode') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>GFS Code: </label> <code>AUTO-GENERATED</code>
                                        <input type="text" class="form-control form-control-sm" value="{{ $gfsCode.$groupCode ?? 'AUTO-GEN' }}" disabled>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        @endif

                        @if ($application->ward_id === null)
                        <div class="form-group">
                            <label>Ward <span class="text-danger">*</span> <code>{{ $application->ward_name }}</code></label>
                            <select wire:model.live="selectedWardId" class="form-control">
                                <option value="">-- Select Correct Ward --</option>
                                {{-- Accessing the eager-loaded relationship --}}
                                @foreach($constituency->wards as $ward)
                                    <option value="{{ $ward->id }}">{{ $ward->ward_name }}</option>
                                @endforeach
                            </select>
                            @error('selectedWardId') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        {{-- SHARED FIELDS --}}
                        <div class="form-group">
                            <label>Approved Amount (KES) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold">KES</span>
                                </div>
                                <input type="number" wire:model.live="approvalAmount" class="form-control form-control-lg text-success font-weight-bold">
                            </div>
                            @error('approvalAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Comments / Approval Remarks</label>
                            <textarea wire:model="approvalComments" class="form-control" rows="3" placeholder="Optional notes for this approval..."></textarea>
                            @error('approvalComments') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>

                        @if($eligibility['can_borrow'])
                            {{-- Show Active Button if Eligible --}}
                            <x-btn-submit
                                label="Approve"
                                icon="fas fa-check-circle"
                                color="success"
                                target="confirmApproval"
                            />
                        @else
                            {{-- Show Reason Button if NOT Eligible --}}
                            <div class="d-flex flex-column align-items-end">
                                <button type="button" class="btn btn-secondary" disabled>
                                    Uncleared Balance: Kes {{ number_format($eligibility['balance']) }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </form>
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

            Livewire.on('error', (event) => {
                // This forces the UI to unlock if the server crashes
                console.error('Livewire Error:', event);
            });

            Livewire.on('confirm-action', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;
                Swal.fire({
                    title: data.title || "Are you sure?",
                    text: data.text || "This action cannot be undone!",
                    icon: data.icon || "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: data.confirmText || "Yes, proceed!",
                    reverseButtons: true // Places "Cancel" on the left
                }).then((result) => {
                    if (result.isConfirmed) {
                        // If confirmed, tell Livewire to call the actual function
                        // 'componentId' and 'action' must be passed from the backend
                        Livewire.find(data.componentId).call(data.action, data.params);
                    }
                });
            });

            Livewire.on('confirm-approve-action', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;
                Swal.fire({
                    title: data.title || "Are you sure?",
                    html: data.html || "This action cannot be undone!",
                    icon: data.icon || "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: data.confirmText || "Yes, proceed!",
                    reverseButtons: true // Places "Cancel" on the left
                }).then((result) => {
                    if (result.isConfirmed) {
                        // If confirmed, tell Livewire to call the actual function
                        // 'componentId' and 'action' must be passed from the backend
                        Livewire.find(data.componentId).call(data.action, data.params);
                    }
                });
            });

            Livewire.on('approval-completed', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                // Hide the previous confirmation/modal
                $('#modal-approve').modal('hide');

                if (data.isPhaseComplete) {
                    Swal.fire({
                        title: 'Phase Fully Vetted!',
                        text: 'Great job! You have reached the end of this phase list.',
                        icon: 'success',
                        confirmButtonText: '<i class="fas fa-file-export mr-1"></i> Proceed to Submission',
                        showCancelButton: true
                    }).then((result) => {
                        if (result.isConfirmed) window.location.href = '/phases/submission';
                    });
                } else {
                    // SCENARIO: Provide summary of the NEXT application
                    Swal.fire({
                        title: 'Approved!',
                        html: `
                            <div class="alert alert-success py-2">Success! Current application finalized.</div>
                            <div class="card card-outline card-primary mt-3">
                                <div class="card-header"><h3 class="card-title">Up Next:</h3></div>
                                <div class="card-body text-left">
                                    <strong>Group:</strong> ${data.nextSummary.group_name}<br>
                                    <strong>Application ID:</strong> ${data.nextSummary.app_id}<br>
                                    <strong>Amount Applied:</strong> KES ${data.nextSummary.amount}
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        showDenyButton: true,
                        confirmButtonColor: '#28a745',
                        denyButtonColor: '#6c757d',
                        confirmButtonText: 'Vet Next <i class="fas fa-chevron-right ml-1"></i>',
                        denyButtonText: 'Close',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '/applications/' + data.nextId + '/vetting';
                        }
                    });
                }
            });

        });

        window.addEventListener('open-modal', event => {
            $('#' + event.detail.name).modal('show');
        });

        window.addEventListener('close-modal', event => {
            $('#' + event.detail.name).modal('hide');
        });


    </script>
@endpush
