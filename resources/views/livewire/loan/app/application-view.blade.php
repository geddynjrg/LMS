<section class="content">
    <div class="container-fluid">
        <!-- Header with Status & Actions -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-uwezo shadow-sm">
                    <div class="card-header border-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">

                            <div class="d-flex align-items-center mb-2">
                                <div class="mr-3 bg-light p-3 rounded shadow-sm border">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-uwezo"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0 font-weight-bold">{{ $application->application_id }}</h3>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="badge badge-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'info') }} px-2 py-1 shadow-xs">
                                            <i class="fas fa-circle x-small mr-1"></i>
                                            @if(!is_null($application->phaseApplication))
                                                {{ $this->getStatusBadgeText($application->phaseApplication->status) }}
                                            @else
                                                {{ $this->getStatusBadgeText($application->status) }}
                                            @endif
                                        </span>

                                        <span class="text-muted mx-2">|</span>

                                        <small class="text-muted font-weight-bold">
                                            <i class="fas fa-layer-group mr-1"></i>
                                            {{ is_null($application->phaseApplication) ? 'Not Assigned' : $application->phaseApplication->reviewPhase->phase }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group btn-group-sm d-flex flex-sm-row mb-2 mb-2">
                                <x-loading-button
                                    target="refreshData"
                                    class="btn-outline-primary btn-sm rounded-pill mr-2"
                                    icon="fas fa-sync-alt"
                                    label="Refresh Data"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="card-body py-2 bg-light-gray border-top mt-3">
                        <div class="row text-center text-md-left">
                            <div class="col-md-3 border-right">
                                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Submitted By:</small>
                                <span class="text-dark font-weight-bold text-truncate d-inline-block" style="max-width: 150px;">
                                    <i class="fas fa-user mr-1"></i>
                                    @if ($application->identifier_type == 'EMAIL')
                                        {{ $application->owner_identifier }}
                                    @else
                                        {{ $application->phone_number }}
                                    @endif
                                </span>
                            </div>
                            <div class="col-md-3 border-right">
                                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Group Name</small>
                                <span class="text-dark font-weight-bold text-truncate d-inline-block" style="max-width: 150px;">
                                    {{ $application->group_name ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="col-md-3 border-right">
                                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Amount Requested</small>
                                <span class="text-dark font-weight-bold">KES {{ number_format($this->application->loan_amount, 2) }}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Reporting Period</small>
                                <span class="badge badge-secondary">{{ $currentFY->full }} ({{ $currentFY->quarter_suffix }} Qtr)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9 order-last order-md-first" wire:ignore.self>
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
                        <div wire:loading.delay.shortest wire:target="activeTab"
                            class="w-100 h-100 position-absolute justify-content-center align-items-center"
                            style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 350px;">
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
                                        {{-- LEFT COLUMN: Profiles and Contacts --}}
                                        <div class="col-md-7">
                                            {{-- Group Profile Card --}}
                                            <div class="card card-outline card-primary">
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
                                                                    <span class="text-primary font-weight-bold text-uppercase">{{ $application->group_name }}</span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b>Registration No.:</b><br>
                                                                    <span class="text-dark font-weight-bold">{{ $application->group_registration_number }}</span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>Nature of Group:</b><br>
                                                                    <span class="badge badge-primary py-1 px-3 text-sm">{{ ucfirst($application->nature_of_group) }}</span>
                                                                </li>
                                                                @if($application->linked_status === 'linked')
                                                                    <li class="list-group-item border-bottom-0">
                                                                        <b>Group / GFS Codes:</b><br>
                                                                        <span class="badge badge-info mr-1">{{ $groupCode }}</span>
                                                                        <span class="badge badge-info">{{ $gfsCode }}</span>
                                                                    </li>
                                                                @endif
                                                            </ul>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <p class="text-muted text-uppercase small font-weight-bold mb-3">Activities & Location</p>
                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item">
                                                                    <b>Physical Location:</b><br>
                                                                    <span class="text-dark">Ward: <span class="text-muted font-weight-bold">{{ $application->ward_name }}</span></span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <b>Landmark:</b>
                                                                    <span class="float-right text-muted small">
                                                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i> Near {{ $application->nearest_landmark }}
                                                                    </span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <b>Key Activities:</b>
                                                                    <div class="mt-2 d-flex flex-wrap" style="gap: 5px;">
                                                                        @if(!empty($application->key_activities) && is_array($application->key_activities))
                                                                            @foreach($application->key_activities as $activity)
                                                                                <span class="badge badge-light border text-muted p-2 shadow-sm">{{ $activity }}</span>
                                                                            @endforeach
                                                                        @else
                                                                            <span class="text-muted small italic">None listed</span>
                                                                        @endif
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Business & Financial Assessment Card --}}
                                            <div class="card card-info card-outline">
                                                <div class="card-header">
                                                    <h3 class="card-title font-weight-bold">
                                                        <i class="fas fa-briefcase mr-2 text-info"></i>Financial Assessment
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6 border-right">
                                                            <p class="text-uppercase small font-weight-bold mb-3">Operating Profile</p>
                                                            <ul class="list-group list-group-unbordered">
                                                                <li class="list-group-item">
                                                                    <span class="text-muted">Nature:</span>
                                                                    <span class="float-right font-weight-bold">{{ $application->nature_of_business }}</span>
                                                                </li>
                                                                <li class="list-group-item">
                                                                    <span class="text-muted">Experience:</span>
                                                                    <span class="float-right font-weight-bold">{{ $application->years_in_operation }} Years</span>
                                                                </li>
                                                                <li class="list-group-item border-bottom-0">
                                                                    <span class="text-muted">Monthly Income:</span>
                                                                    <span class="float-right text-success font-weight-bold">KES {{ number_format($application->average_income, 2) }}</span>
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
                                                                <table class="table table-sm mb-2">
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
                                        </div>

                                        {{-- RIGHT COLUMN: Loan Details and Settlement --}}
                                        <div class="col-md-5">
                                            <div class="card card-outline card-success">
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
                                                            <p class="small text-muted mb-0">Interest</p>
                                                            <p class="font-weight-bold text-success">0%</p>
                                                        </div>
                                                    </div>

                                                    @if($application->phaseApplication?->amount_approved)
                                                        <p class="text-muted mb-1 small uppercase border-top mt-3 pt-2">Approved Amount</p>
                                                        <h2 class="text-primary font-weight-bold">KES {{ number_format($application->phaseApplication->amount_approved, 2) }}</h2>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="card card-outline card-success">
                                                <div class="card-header">
                                                    <h3 class="card-title font-weight-bold text-success">
                                                        <i class="fas fa-university mr-2"></i>Bank Settlement
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <ul class="list-group list-group-unbordered">
                                                        <li class="list-group-item">
                                                            <b>Bank:</b> <span class="float-right">{{ $application->bank_name }}</span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <b>A/C No:</b> <span class="float-right text-primary font-weight-bold">{{ $application->bank_account_number }}</span>
                                                        </li>
                                                        <li class="list-group-item border-bottom-0">
                                                            <b>Branch:</b> <span class="float-right text-muted">{{ $application->bank_branch }}</span>
                                                        </li>
                                                    </ul>
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
                                                                @php
                                                                    $previewable = [
                                                                        'application/pdf',
                                                                        'image/jpeg',
                                                                        'image/png',
                                                                        'image/gif',
                                                                        'image/webp'
                                                                    ];
                                                                    $mimeType = $member->photo_member_id['mime_type'] ?? null;
                                                                    $action = in_array($mimeType, $previewable) ? 'view' : 'download';
                                                                @endphp
                                                                @if($member->photo_member_id && is_array($member->photo_member_id))
                                                                    <a href="{{ route('const.app.application.file.download', [
                                                                            'county' => $county->slug ?? $constituency->county->slug,
                                                                            'constituency' => $constituency->slug,
                                                                            'applicationId' => $application->application_id,
                                                                            'action' => $action
                                                                        ]) . '?path=' . base64_encode($member->photo_member_id['path']) }}"
                                                                        @if($action === 'view') target="_blank" @endif
                                                                        class="btn btn-sm {{ $action === 'view' ? 'btn-outline-info' : 'btn-outline-primary' }}">

                                                                        <i class="fas {{ $action === 'view' ? 'fa-eye' : 'fa-download' }}"></i>

                                                                        {{ $action === 'view' ? 'View ID' : 'Download ID' }}
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
                                                                    label="More Info"
                                                                    :block="false"
                                                                    defaultLabel=""
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
                                        </div>
                                    @else
                                        <div class="text-center p-5">
                                            <p class="small text-success mb-3 text-bold">
                                                <i class="fas fa-exclamation-triangle"></i> Not yet linked to a Master Group record.
                                            </p>


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

                                       to implement
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

            <div class="col-md-3 order-first order-md-last d-flex flex-column">
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
                                <x-select2
                                    model="selectedPhaseId"
                                    label="Phase"
                                    :options="$activePhases"
                                    placeholder="Select a Phase"
                                    optionLabel="phase"
                                    optionValue="id"
                                />

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
                                    <span class="badge badge-warning px-3 py-2 mb-1">{{ $application->loan_type }}</span>
                                    <div class="small text-danger font-weight-bold">
                                        <i class="fas fa-search-location"></i> Attempted: No Link Found
                                    </div>
                                </div>
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
                                    <x-modal-trigger
                                        class="mb-2"
                                        target="openRejectModal"
                                        label="REJECT"
                                        icon="fas fa-times-circle"
                                        variant="danger"
                                        block="true"
                                    />
                                </div>
                            @else
                                <div class="text-center mb-3">
                                    @if(in_array($application->phaseApplication->status,['pending_submission_to_hq', 'pending_hq_approval', 'approved', 'in_traning', 'disbursed']))
                                        <div class="p-3 border border-success rounded bg-light">
                                            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                                            <h6 class="text-success font-weight-bold">APPLICATION APPROVED</h6>
                                        </div>
                                    @elseif($application->phaseApplication->status === 'rejected')
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
                                    <tr>
                                        <td colspan="2" class="text-muted pb-0">Remarks:</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="bg-light p-2 rounded text-dark font-weight-bold">
                                            "{{ $application->phaseApplication->comments?? $application->phaseApplication->rejection_reason}}"
                                        </td>
                                    </tr>
                                </table>

                                <hr>
                                @if (!in_array($application->phaseApplication->status, ['pending', 'rejected']))
                                    <x-loading-button
                                        target="exportInstitutionalInfo"
                                        class="btn-uwezo btn-sm"
                                        icon="fas fa-file-excel"
                                        size="sm"
                                        block="true"
                                        label="Institutional Information"
                                        loadingLabel="Generating..."
                                    />
                                @endif


                                {{-- review decision --}}
                                @if($application->phaseApplication->status === 'pending_submission_to_hq')
                                    <x-modal-trigger
                                        target="reviewApproval"
                                        label="Review Approval"
                                        icon="fas fa-edit"
                                        variant="outline-primary"
                                        block="true"
                                    />
                                @endif

                                @if($application->phaseApplication->action === 'declined_at_constituency')
                                    <x-modal-trigger
                                        target="reviewRejection"
                                        label="Review Rejection"
                                        icon="fas fa-edit"
                                        variant="outline-primary"
                                        block="true"
                                    />
                                @endif

                                <a class="btn btn-default btn-block btn-sm shadow-sm" href="{{ route('const.app.app_dashboard', [$county->slug, $constituency->slug]) }}" wire:navigate>
                                    <i class="fas fa-arrow-left mr-1"></i> Go to Applications
                                </a>


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
                            <small class="text-muted">Searching within {{ $constituency->constituency_name ?? 'Current Constituency' }}</small>
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
                                    <a href="{{ route('const.app.application.file.download', [
                                            'county' => $county->slug ?? $constituency->county->slug,
                                            'constituency' => $constituency->slug,
                                            'applicationId' => $application->application_id,
                                            'action' => 'download'
                                        ]) . '?path=' . base64_encode($selectedMember->photo_member_id['path']) }}"
                                        class="btn btn-app bg-light m-1 border btn-outline-info">

                                        <i class="fas fa-id-card"></i>

                                        ID Copy
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
    <div wire:ignore.self class="modal fade" id="modal-approve" role="dialog">
        <div class="modal-content modal-dialog modal-dialog-scrollable">
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

            // Livewire.on('confirm-action', (eventData) => {
            //     const data = Array.isArray(eventData) ? eventData[0] : eventData;
            //     Swal.fire({
            //         title: data.title || "Are you sure?",
            //         text: data.text || "This action cannot be undone!",
            //         icon: data.icon || "warning",
            //         showCancelButton: true,
            //         confirmButtonColor: "#3085d6",
            //         cancelButtonColor: "#d33",
            //         confirmButtonText: data.confirmText || "Yes, proceed!",
            //         reverseButtons: true // Places "Cancel" on the left
            //     }).then((result) => {
            //         if (result.isConfirmed) {
            //             // If confirmed, tell Livewire to call the actual function
            //             // 'componentId' and 'action' must be passed from the backend
            //             Livewire.find(data.componentId).call(data.action, data.params);
            //         }
            //     });
            // });

            Livewire.on('confirm-approve-action', (eventData) => {
                console.log('called')
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
                        $('#modal-approve').modal('hide');
                        // If confirmed, tell Livewire to call the actual function
                        // 'componentId' and 'action' must be passed from the backend
                        Livewire.find(data.componentId).call(data.action, data.params);
                    }
                });
            });

            Livewire.on('approval-completed', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                if (data.isPhaseComplete) {
                    // SCENARIO 1: PHASE COMPLETED
                    Swal.fire({
                        title: '<span class="text-success">Phase Fully Vetted!</span>',
                        html: `
                            <div class="py-3">
                                <i class="fas fa-check-circle fa-4x text-success mb-3 animate__animated animate__bounceIn"></i>
                                <p class="text-muted">Excellent! You have successfully processed all applications in this phase.</p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-file-export mr-1"></i> Proceed to Submission',
                        cancelButtonText: 'Stay on Page',
                        background: '#f8fafc',
                        customClass: {
                            title: 'font-weight-bold',
                            confirmButton: 'btn btn-success shadow-sm',
                            cancelButton: 'btn btn-light border'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Livewire.navigate(data.nextUrl);
                        }
                    });

                } else {
                    // SCENARIO 2: NEXT APPLICATION PREVIEW
                    Swal.fire({
                        title: 'Action Successful!',
                        html: `
                            <div class="text-left mt-3">
                                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded border">
                                    <div class="bg-success rounded-circle p-2 mr-3 text-white">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span class="text-dark font-weight-bold">Current application finalized.</span>
                                </div>

                                <h6 class="text-uppercase text-muted small font-weight-bold mb-2">Up Next:</h6>
                                <div class="list-group shadow-sm">
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-left-primary">
                                        <span><i class="fas fa-users text-primary mr-2"></i> Group</span>
                                        <span class="font-weight-bold">${data.nextSummary.group_name}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-hashtag text-primary mr-2"></i> App ID</span>
                                        <span class="badge badge-light border text-monospace px-2">${data.nextSummary.app_id}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                        <span><i class="fas fa-coins text-primary mr-2"></i> Amount Applied</span>
                                        <span class="text-success font-weight-bold">KES ${data.nextSummary.amount}</span>
                                    </div>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        showDenyButton: true,
                        confirmButtonColor: '#007bff',
                        denyButtonColor: '#6c757d',
                        confirmButtonText: 'Vet Next <i class="fas fa-arrow-right ml-1"></i>',
                        denyButtonText: 'Back to List',
                        customClass: {
                            popup: 'rounded-lg shadow-lg',
                            confirmButton: 'btn btn-primary px-4',
                            denyButton: 'btn btn-outline-secondary px-4'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Livewire.navigate(data.nextUrl);
                        } else {
                            $('#modal-approve').modal('show');
                        }
                    });
                }
            });

            Livewire.on('confirm-reject', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                Swal.fire({
                    title: data.title || "Reject Application",
                    text: data.text || "Please provide a detailed reason for declining:",
                    icon: "warning",
                    input: 'textarea',
                    inputPlaceholder: 'e.g., Missing supporting documents, invalid ID number...',
                    inputAttributes: {
                        'minlength': '10',
                        'autocapitalize': 'off',
                        'autocorrect': 'off'
                    },
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Confirm Rejection",
                    reverseButtons: true,

                    didOpen: () => {
                        const input = Swal.getInput();
                        input.oninput = () => {
                            Swal.resetValidationMessage();
                        };
                    },

                    // Advanced Validation
                    inputValidator: (value) => {
                        const trimmedValue = value.trim();

                        if (!trimmedValue) {
                            return 'The rejection reason cannot be empty.';
                        }

                        if (trimmedValue.length < 20) {
                            return `Reason too short. Enter at least 20 characters (Current: ${trimmedValue.length}).`;
                        }

                        if (/^(.)\1+$/.test(trimmedValue)) {
                            return 'Please provide a real explanation, not just repetitive characters.';
                        }

                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.showLoading();

                        const params = data.params || {};
                        params.reason = result.value;

                        Livewire.find(data.componentId).call(data.action, params);
                    }
                });
            });

            Livewire.on('review-rejection', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                window.ReviewAlert.fire({
                    title: 'Review Decision: Rejection',
                    html: `
                        <div class="text-left">
                            <p class="mb-2">Are you sure you want to <b>review your decision on</b> this application?</p>
                            <div class="p-3 bg-light border rounded mb-3">
                                <small class="text-muted d-block uppercase font-weight-bold mb-1">Reason for Rejection:</small>
                                <i class="text-danger italic">"${data.reason}"</i>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Review',
                    confirmButtonClass: 'btn btn-danger shadow-sm px-4 mx-2',
                    cancelButtonText: 'Cancel Review',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.showLoading();

                        const params = data.params || {};

                        Livewire.find(data.componentId)
                            .call(data.action, params.applicationId)
                            .then((result) => {
                                //
                            });
                    }
                });
            });

            Livewire.on('review-approval', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                window.ReviewAlert.fire({
                    title: 'Review Decision: Approval',
                    html: `
                        <div class="text-left">
                            <p class="mb-2">Are you sure you want to <b>review your decision on</b> this application?</p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Review',
                    confirmButtonClass: 'btn btn-danger shadow-sm px-4 mx-2',
                    cancelButtonText: 'Cancel Review',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.showLoading();

                        const params = data.params || {};

                        Livewire.find(data.componentId)
                            .call(data.action, params.applicationId)
                            .then((result) => {
                                //
                            });
                    }
                });
            });

        });

        if (typeof window.ReviewAlert === 'undefined') {
            window.ReviewAlert = Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success shadow-sm px-4 mx-2",
                    cancelButton: "btn btn-light border px-4 mx-2",
                    denyButton: "btn btn-danger shadow-sm px-4 mx-2",
                    popup: "rounded-lg"
                },
                buttonsStyling: false,
                reverseButtons: true
            });
        }

        window.addEventListener('open-modal', event => {
            $('#' + event.detail.name).modal('show');
        });

        window.addEventListener('close-modal', event => {
            $('#' + event.detail.name).modal('hide');
        });

    </script>
@endpush
