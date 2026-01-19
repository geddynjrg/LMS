<section class="content">
    <div class="container-fluid">

        <div class="row mb-4 align-items-end">
            <div class="col-md-7">
                <h1 class="m-0 text-dark font-weight-bold display-5">
                    <i class="fas fa-layer-group mr-2 text-success"></i>
                    {{ ucwords($phase->phase) ?? 'Review Phase' }}
                </h1>
                <div class="mt-2 d-flex align-items-center">
                    <span class="badge badge-pill badge-success mr-3 px-3">Active Phase</span>
                    <span class="text-muted small">
                        <i class="fas fa-calendar-alt mr-1"></i> Proposed Review:
                        <b class="text-dark">{{ $phase->review_date?->format('M d, Y') ?? 'Not set' }}</b>
                    </span>
                    <span class="mx-2 text-light">|</span>
                    <span class="text-muted small">
                        <i class="fas fa-map-marker-alt mr-1"></i> {{ $constituency->name }}
                    </span>
                </div>
            </div>

            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                <div class="btn-group shadow-sm">
                    <button class="btn btn-success btn-sm"
                            wire:click="$dispatchTo('add-apps-to-phase-modal', 'open-add-apps-modal', {
                                phaseId: {{ $phase->id }},
                                constituency_code: '{{ $constituency->constituency_code }}'
                            })">
                        <i class="fas fa-plus mr-1"></i> Add Applications
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-left-success h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Applications</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">{{ number_format($detailedPhase->total_applications ?? 0) }}</div>

                        <hr class="my-2">

                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success font-weight-bold">{{ $detailedPhase->pending_submission_to_hq_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Apprv.</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-pink font-weight-bold">{{ $detailedPhase->pending_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Pending</small>
                            </div>
                            <span class="text-gray-300">|</span>
                            <div>
                                <span class="text-danger font-weight-bold">{{ $detailedPhase->rejected_count ?? 0 }}</span>
                                <small class="text-muted text-xs">Decli.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-primary h-100 py-2">
                    <div class="card-body py-1">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Amount Requested</div>
                        <div class="h5 mb-1 font-weight-bold text-gray-800">KES {{ number_format($phase->total_requested ?? 0) }}</div>

                        <hr class="my-2">

                        <div class="row no-gutters align-items-center">
                            <div class="col">
                                <small class="d-block text-muted text-xs text-uppercase font-weight-bold">HQ Submission</small>
                                <span class="text-info font-weight-bold">{{ $phase->pending_submission_to_hq_count ?? 0 }}</span>
                                <small class="text-xs text-muted">Awaiting Submission</small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-export fa-2x text-gray-200"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm h-100 py-2">
                    <div class="card-body py-2">
                        <div class="row no-gutters align-items-center mb-2">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-info text-uppercase">Phase Vetting Completion</div>
                            </div>

                            <div class="col-auto">
                                <span class="badge {{ $phase->has_minutes && $detailedPhase->pending_count == 0 ? 'badge-success' : 'badge-warning' }} px-2 py-1">
                                    <i class="fas {{ $phase->has_minutes && $detailedPhase->pending_count == 0 ? 'fa-check-circle' : 'fa-clock' }} mr-1"></i>
                                    {{ $detailedPhase->pending_count == 0 ? ($phase->has_minutes ? 'Ready for submission' : 'Pending Minutes') : 'Pending Vetting' }}
                                </span>
                            </div>
                        </div>

                        <div class="row no-gutters align-items-center">
                            <div class="col-auto mr-3">
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($vettingPercentage ?? 0) }}%</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2 shadow-sm">
                                    <div class="progress-bar bg-info progress-bar-striped progress-bar-animated"
                                        role="progressbar"
                                        style="width: {{ number_format($vettingPercentage ?? 0) }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <div class="small">
                                <i class="fas fa-check-circle text-success mr-1"></i>
                                <span class="text-muted font-italic">Last updated: {{ now()->format('H:i') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-weight-bold text-dark text-uppercase">Approved by:</span>
                                <span class="ml-2 font-weight-bold text-success">{{ number_format($vettingPercentage ?? 0) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 order-last order-md-first">
                <div class="card shadow-none border">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="font-weight-bold mb-0">Applications in this Phase</h5>
                                <small class="text-muted">
                                    Total: <strong>{{ $detailedPhase->total_applications }}</strong> • Showing vetted and pending records
                                </small>
                            </div>
                            <div class="mt-2 mt-md-0">
                                    <div class="input-group w-100">
                                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search group or application id...">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                        </div>
                                    </div>

                            </div>
                        </div>

                        <div class="row pt-2 border-top">
                            <div class="col-md-3">
                                <label class="small font-weight-bold text-uppercase text-muted">Status</label>
                                <select wire:model.live="statusFilter" class="form-control form-control-sm border-gray">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="pending_submission_to_hq">Pending Submission</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="disbursed">Disbursed</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="small font-weight-bold text-uppercase text-muted">Risk Level</label>
                                <select wire:model.live="riskFilter" class="form-control form-control-sm border-gray">
                                    <option value="">All Risks</option>
                                    <option value="high">High Risk</option>
                                    <option value="medium">Medium Risk</option>
                                    <option value="low">Low Risk</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="small font-weight-bold text-uppercase text-muted">Per Page</label>
                                <select wire:model.live="perPage" class="form-control form-control-sm border-gray">
                                    <option value="10">10 Rows</option>
                                    <option value="15">15 Rows</option>
                                    <option value="25">25 Rows</option>
                                    <option value="50">50 Rows</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="small font-weight-bold text-uppercase text-muted">refresh</label>
                                <div class="btn-group">
                                    <x-loading-button
                                        target="refreshData"
                                        class="btn-default"
                                        icon="fas fa-sync-alt"
                                    />

                                    <x-loading-button
                                        target="resetFilters"
                                        class="btn-default"
                                        icon="fas fa-filter"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 position-relative">
                        <div wire:loading.delay.shortest
                            wire:target="nextPage, previousPage, gotoPage, refreshData, perPage, statusFilter, riskFilter, search"
                            class="w-100 h-100 position-absolute justify-content-center align-items-center"
                            style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 200px;">
                            <div class="text-center">
                                <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                                <p class="text-navy font-weight-bold mt-2">Loading Data...</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="border-0 text-xs text-uppercase pl-4" style="width: 35%;">Applicant Detail</th>
                                        <th class="border-0 text-xs text-uppercase" style="width: 25%;">Financials</th>
                                        <th class="border-0 text-xs text-uppercase" style="width: 20%;">Vetting Health</th>
                                        <th class="border-0 text-xs text-uppercase text-right pr-4" style="width: 20%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($applications as $app)
                                        <tr wire:key="app-row-{{ $app->id }}" class="{{ $selectedAppId == $app->id ? 'bg-light-blue' : '' }}">
                                            <td class="pl-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm mr-3 bg-{{ $app->tempApplication->risk_level == 'High Risk' ? 'danger' : 'pink-light' }} rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                        <span class="text-xs font-weight-bold">{{ substr($app->tempApplication->group_name, 0, 1) }}</span>
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-bold text-dark">{{ $app->tempApplication->group_name }}</div>
                                                         <div class="text-xs text-muted">
                                                            <span class="text-navy">{{ $app->tempApplication->application_id }}</span>
                                                            <br>
                                                            <i class="fas fa-map-marker-alt mr-1"></i>{{ $wardMap[$app->tempApplication->ward_id] ?? 'Unallocated' }}
                                                        </div>
                                                        <div class="mt-1">
                                                        @if($app->tempApplication->risk_level == 'High Risk')
                                                            <span class="text-xxs text-danger font-weight-bold">
                                                                <i class="fas fa-biohazard"></i> CRITICAL RISK
                                                            </span>
                                                        @elseif($app->tempApplication->risk_level == 'Medium Risk')
                                                            <span class="text-xxs text-warning font-weight-bold">
                                                                <i class="fas fa-exclamation-circle"></i> ELEVATED RISK
                                                            </span>
                                                        @elseif($app->tempApplication->risk_level == 'Low Risk')
                                                            <span class="text-xxs text-success font-weight-bold">
                                                                <i class="fas fa-check-shield"></i> LOW RISK
                                                            </span>
                                                        @endif
                                                    </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <div class="text-sm">
                                                        <span class="text-muted small">Applied:</span>
                                                        <span class="font-weight-bold text-dark">KES {{ number_format($app->tempApplication->loan_amount) }}</span>
                                                    </div>
                                                    @if($app->amount_approved > 0)
                                                    <div class="text-sm">
                                                        <span class="text-muted small">Recommended:</span>
                                                        <span class="font-weight-bold text-success">KES {{ number_format($app->amount_approved) }}</span>
                                                    </div>
                                                    @else
                                                    <div class="text-xs">Waiting Vetting</div>
                                                    @endif
                                                    <div class="mt-1">
                                                        <span class="badge badge-pill badge-light border text-xxs font-weight-normal">
                                                            {{ $app->tempApplication->loan_type }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusMap = [
                                                        'pending' => ['bg' => 'warning', 'icon' => 'clock'],
                                                        'approved' => ['bg' => 'success', 'icon' => 'check-circle'],
                                                        'pending_submission_to_hq' => ['bg' => 'info', 'icon' => 'paper-plane'],
                                                        'rejected' => ['bg' => 'danger', 'icon' => 'times-circle'],
                                                        'disbursed' => ['bg' => 'navy', 'icon' => 'money-bill-wave'],
                                                    ];
                                                    $config = $statusMap[$app->status] ?? ['bg' => 'secondary', 'icon' => 'info-circle'];
                                                @endphp


                                                <div class="text-xs text-capitalize">
                                                    <span class="text-xs font-weight-bold">Category: {{ $app->tempApplication->nature_of_group ?? 'N/A' }}</span><br>
                                                    <span class="text-xs">Sector:
                                                        @if(is_array($app->tempApplication->business_type))
                                                            @foreach($app->tempApplication->business_type as $type)
                                                            {{ $type }}
                                                            @endforeach
                                                        @endif
                                                    </span>

                                                </div>
                                                <div class="text-xs text-muted">
                                                    <span class="text-xs">GFS: {{ $app->gfs_code ?? 'N/A' }}</span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-{{ $config['bg'] }} px-2 py-1 mb-1 d-inline-block shadow-xs" style="width: fit-content;">
                                                        <i class="fas fa-{{ $config['icon'] }} mr-1"></i>
                                                        {{ strtoupper(str_replace('_', ' ', $app->status)) }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-right pr-4">
                                                <div class="btn-group">

                                                    <a href="{{ route('const.app.application-view', [
                                                        'county' => $constituency->county->slug,
                                                        'constituency' => $constituency->slug,
                                                        'applicationId' => $app->tempApplication->application_id
                                                    ]) }}"
                                                    class="btn btn-sm btn-white btn-outline-info" title="View Details" wire:navigate>
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-5 text-center">
                                                <div class="display-4 text-muted mb-3"><i class="fas fa-folder-open opacity-25"></i></div>
                                                <p class="text-muted font-italic">No applications matched your current filters.</p>
                                                <button wire:click="resetFilters" class="btn btn-sm btn-outline-navy">Clear Filters</button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer clearfix">
                        <div class="pagination-sm">
                            {{ $applications->links() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 order-first order-md-last d-flex flex-column" style="max-height: 115vh;">

                <div class="card bg-navy shadow-sm mb-3 flex-shrink-0">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <small class="text-uppercase opacity-75 text-xs font-weight-bold letter-spacing-1">Recommended Total</small>
                                <h3 class="font-weight-bold mb-0">KES {{ number_format($detailedPhase->total_approved) }}</h3>
                            </div>
                        </div>

                        <div class="progress progress-xxs my-2 bg-black-50">
                            <div class="progress-bar bg-success" style="width: {{ $vettingPercentage }}%"></div>
                        </div>

                        <div class="mt-2 text-xs opacity-75 d-flex justify-content-between">
                            <span>Applied: KES {{ number_format($phase->total_requested) }}</span>
                            <span class="text-white-50">Score: {{ number_format(($vettingPercentage), 1) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="sidebar-upload-zone scrollable-sidebar-content flex-grow-1 pr-1" style="overflow-y: auto; overflow-x: hidden; min-height: 0;">
                    @if(!$phase->has_minutes)
                        <div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #ffc107 !important; background-color: #fffdf5;">
                            <div class="card-header bg-transparent border-0 py-2">
                                <span class="text-xs font-weight-bold text-uppercase text-navy">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Missing Mandatory Document
                                </span>
                            </div>
                            <div class="card-body pt-1 pb-3">
                                <h6 class="font-weight-bold mb-2 text-dark">Vetting Minutes</h6>
                                <p class="text-xs text-muted mb-3">You must upload the signed committee minutes to finalize this batch.</p>

                                <div class="form-group mb-2">
                                    <div class="custom-file custom-file-sm">
                                        <input type="file"
                                            wire:model="minutesFile"
                                            class="custom-file-input"
                                            id="minutesFile"
                                            accept=".pdf, .doc, .docx, application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                        <label class="custom-file-label text-xs text-wrap shadow-sm {{ $minutesFile ? 'border-success' : '' }}" for="minutesFile">
                                            @if($minutesFile)
                                                @php
                                                    $ext = $minutesFile->getClientOriginalExtension();
                                                    $icon = match($ext) {
                                                        'pdf' => 'fa-file-pdf text-danger',
                                                        'doc', 'docx' => 'fa-file-word text-primary',
                                                        default => 'fa-file-alt text-muted'
                                                    };
                                                @endphp
                                                <i class="fas {{ $icon }} mr-1"></i> {{ $minutesFile->getClientOriginalName() }}
                                            @else
                                                <i class="fas fa-file-upload mr-1 text-muted"></i> Select Signed PDF or Word Doc
                                            @endif
                                        </label>
                                    </div>
                                    @error('minutesFile') <small class="text-danger d-block mt-1 font-weight-bold">{{ $message }}</small> @enderror
                                </div>

                                <button wire:click="uploadMinutes"
                                        wire:loading.attr="disabled"
                                        wire:target="minutesFile, uploadMinutes"
                                        class="btn btn-warning btn-sm btn-block shadow-sm font-weight-bold">
                                    <span wire:loading.remove wire:target="uploadMinutes">
                                        <i class="fas fa-cloud-upload-alt mr-1"></i> Upload & Unlock
                                    </span>
                                    <span wire:loading wire:target="uploadMinutes">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Processing...
                                    </span>
                                </button>

                                <div wire:loading wire:target="minutesFile" class="mt-2">
                                    <div class="progress progress-xxs">
                                        <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                    </div>
                                    <small class="text-xs text-muted">Staging file...</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="card-title font-weight-bold mb-0">Attach documents</h6>
                            <button type="button" wire:click="addUploadRow" class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-plus"></i> Add Document
                            </button>
                        </div>

                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($extraUploads as $index => $upload)
                                    <li class="list-group-item p-3 bg-light border-bottom">
                                        <div class="form-group mb-2">
                                            <input type="text"
                                                wire:model="extraUploads.{{ $index }}.title"
                                                class="form-control form-control-sm"
                                                placeholder="Document Title (e.g. Transfer of funds)">
                                            @error("extraUploads.$index.title") <small class="text-danger">{{ $message }}</small> @enderror
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <div class="custom-file custom-file-sm flex-grow-1">
                                                <input type="file"
                                                    wire:model="extraUploads.{{ $index }}.file"
                                                    class="custom-file-input"
                                                    id="file_{{ $index }}">

                                                <label class="custom-file-label text-xs text-truncate {{ isset($extraUploads[$index]['file']) ? 'border-success' : '' }}"
                                                    for="file_{{ $index }}">
                                                    @if(isset($extraUploads[$index]['file']))
                                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                                        {{ $extraUploads[$index]['file']->getClientOriginalName() }}
                                                    @else
                                                        <i class="fas fa-paperclip mr-1 text-muted"></i> Choose File
                                                    @endif
                                                </label>
                                            </div>

                                            <button type="button"
                                                wire:click="removeUploadRow({{ $index }})"
                                                class="btn btn-outline-danger btn-xs ml-2 rounded-circle"
                                                style="width: 24px; height: 24px; padding: 0;"
                                                title="Remove this row">
                                                <i class="fas fa-times" style="font-size: 10px;"></i>
                                            </button>
                                        </div>

                                        <div wire:loading wire:target="extraUploads.{{ $index }}.file" class="mt-1">
                                            <div class="progress progress-xxs">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
                                            </div>
                                            <small class="text-muted" style="font-size: 9px;">Uploading document...</small>
                                        </div>
                                        @error("extraUploads.$index.file") <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    </li>
                                @empty
                                    <li class="list-group-item text-center py-4 text-muted small">
                                        <i class="fas fa-info-circle mr-1"></i> No documents staged. Click "Add Document" to start.
                                    </li>
                                @endforelse
                            </ul>
                        </div>

                        @if(count($extraUploads) > 0)
                            <div class="card-footer bg-white border-top-0">
                                <button wire:click="uploadExtraDocuments"
                                        wire:loading.attr="disabled"
                                        class="btn btn-primary btn-sm btn-block shadow-sm">
                                    <span wire:loading.remove>
                                        <i class="fas fa-cloud-upload-alt mr-1"></i> Upload {{ count($extraUploads) }} {{ Str::plural('File', count($extraUploads)) }}
                                    </span>
                                    <span wire:loading>
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="card card-outline card-success shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">
                                <i class="fas fa-file-invoice-dollar mr-2 text-success"></i>Phase Documents & Reports
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-3">
                                <div class="alert bg-light border-0 text-xs text-muted mb-3">
                                    <i class="fas fa-sync-alt mr-1"></i> Reports sync automatically with vetting decisions.
                                </div>

                                <table class="table table-sm table-hover mb-0">
                                    <tbody>
                                        @php
                                            $reports = [
                                                ['id' => 'code_list', 'name' => 'Code List', 'meta' => 'GFS Codes, Loan Levels, Contact Persons & Financials'],
                                                ['id' => 'standard_vetted', 'name' => 'Standard Vetted Form', 'meta' => 'Full demographics, Bank info, & Sectors'],
                                                ['id' => 'disaggregated', 'name' => 'Disaggregated Data', 'meta' => 'Gender & PWD breakdown by Ward'],
                                                ['id' => 'disbursement', 'name' => 'Disbursement Report', 'meta' => 'Bank Accounts & Final Amounts'],
                                            ];
                                        @endphp
                                        @foreach($reports as $report)
                                            <tr>
                                                <td class="py-2">
                                                    <span class="font-weight-bold text-sm">{{ $report['name'] }}</span><br>
                                                    <small class="text-muted">{{ $report['meta'] }}</small>
                                                </td>
                                                <td class="text-right align-middle">
                                                    <x-loading-button
                                                        target="exportReport('{{ $report['id'] }}')"
                                                        class="btn-outline-success btn-sm"
                                                        icon="fas fa-file-excel"
                                                        label="XLSX"
                                                        loadingLabel="Generating..."
                                                    />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-light px-3 py-2 border-top border-bottom">
                                <small class="text-uppercase font-weight-bold text-muted" style="letter-spacing: 1px;">Uploaded Files</small>
                            </div>

                            <div class="p-0">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @forelse($phase->phaseApplicationDocuments as $doc)
                                            <tr>
                                                <td class="pl-3 py-2">
                                                    @php
                                                        $extension = strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION));
                                                        $icon = match(strtolower($extension)) {
                                                            'pdf' => 'fa-file-pdf text-danger',
                                                            'doc', 'docx' => 'fa-file-word text-primary',
                                                            'xls', 'xlsx' => 'fa-file-excel text-success',
                                                            default => 'fa-file-alt text-muted'
                                                        };
                                                    @endphp
                                                    <i class="fas {{ $icon }} mr-2"></i>
                                                    <span class="text-sm">{{ $doc->title }}</span>
                                                    <div class="text-xxs text-muted ml-4">
                                                        Uploaded {{ $doc->created_at->format('d M, H:i') }} • {{ number_format($doc->size / 1024, 1) }} KB
                                                    </div>
                                                </td>
                                                <td class="text-right align-middle pr-3">
                                                    <div class="btn-group">
                                                        @php

                                                            $canViewInline = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif']);

                                                        @endphp

                                                        @if($canViewInline)
                                                            <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'view']) }}"
                                                            target="_blank"
                                                            class="btn btn-xs btn-outline-info"
                                                            title="View in Browser">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endif

                                                        <a href="{{ route('phase.doc.serve', ['id' => $doc->id, 'action' => 'download']) }}"
                                                        class="btn btn-xs btn-outline-success"
                                                        title="Download File">
                                                            <i class="fas fa-download"></i>
                                                        </a>

                                                        @if($phase->status !== 'submitted_to_hq')
                                                            <button
                                                                wire:click="deleteDocument({{ $doc->id }})"
                                                                wire:confirm="Permanent action: Delete this file?"
                                                                class="btn btn-xs btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-muted">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block opacity-25"></i>
                                                    <small class="italic">No files uploaded yet.</small>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0">
                    @if ($phase->has_minutes && $detailedPhase->pending_count == 0)
                        <div class="card-body">
                            <p class="small text-muted">By submitting, you confirm that all committee minutes are signed and amounts match the Excel sheets.</p>
                            <x-modal-trigger
                                target="submitToHq"
                                class="shadow-sm"
                                icon="fas fa-paper-plane"
                                variant="success"
                                size="lg"
                                label="Submit to HQ"
                                :block="true"
                            />
                        </div>
                    @else
                    <button
                        disabled
                        title="{{ $detailedPhase->pending_count == 0 ? ($phase->has_minutes ? 'Ready' : 'Pending Minutes') : 'Pending Vetting' }}"
                        class="btn btn-success btn-lg btn-block shadow-sm"
                        {{ $phase->status !== 'draft' ? 'disabled' : '' }}>
                        <i class="fas fa-paper-plane mr-2"></i> Submit to HQ
                    </button>
                    @endif


                </div>


            </div>
        </div>
    </div>
</section>
@push('styles')
    <style>
        .bg-light-blue { background-color: #f0f7ff !important; border-left: 3px solid #007bff; }
        .bg-pink-light { background-color: #fff0f5; color: #d63384; }
        .bg-blue-light { background-color: #e7f3ff; color: #007bff; }
        .text-xxs { font-size: 0.65rem; }
    </style>
    <style>
        /* Custom Scrollbar for better UX */
        .scrollable-sidebar-content::-webkit-scrollbar {
            width: 4px;
        }
        .scrollable-sidebar-content::-webkit-scrollbar-thumb {
            background: #ced4da;
            border-radius: 10px;
        }
        .scrollable-sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }
        .text-xxs { font-size: 0.65rem; }

        .custom-swal-container {
            margin: 1em 0 0 !important; /* Adjusts spacing around the scrollable area */
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

            // Listen for error message
            Livewire.on('flash-info', ({message}) => {
                toastr.info(message);
            });

            Livewire.on('error', (event) => {
                // This forces the UI to unlock if the server crashes
                console.error('Livewire Error:', event);
            });

            Livewire.on('confirm-submission-to-hq', (eventData) => {
                const data = Array.isArray(eventData) ? eventData[0] : eventData;

                Swal.fire({
                    title: data.title,
                    html: data.html,
                    icon: data.icon,
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Danger Red
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: data.confirmText,
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        htmlContainer: 'custom-swal-container',
                    },
                    width: '550px', // Slightly wider for the table
                    preConfirm: () => {
                        // Optional: Grab remarks if you added the textarea
                        const remarks = document.getElementById('submissionRemarks').value;
                        return { remarks: remarks };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.find(data.componentId).call(data.action, result.value);
                    }
                });
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

            // Livewire.on('confirm-approve-action', (eventData) => {
            //     const data = Array.isArray(eventData) ? eventData[0] : eventData;
            //     Swal.fire({
            //         title: data.title || "Are you sure?",
            //         html: data.html || "This action cannot be undone!",
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

            // Livewire.on('approval-completed', (eventData) => {
            //     const data = Array.isArray(eventData) ? eventData[0] : eventData;

            //     // Hide the previous confirmation/modal
            //     $('#modal-approve').modal('hide');

            //     if (data.isPhaseComplete) {
            //         Swal.fire({
            //             title: 'Phase Fully Vetted!',
            //             text: 'Great job! You have reached the end of this phase list.',
            //             icon: 'success',
            //             confirmButtonText: '<i class="fas fa-file-export mr-1"></i> Proceed to Submission',
            //             showCancelButton: true
            //         }).then((result) => {
            //             if (result.isConfirmed) window.location.href = '/phases/submission';
            //         });
            //     } else {
            //         // SCENARIO: Provide summary of the NEXT application
            //         Swal.fire({
            //             title: 'Approved!',
            //             html: `
            //                 <div class="alert alert-success py-2">Success! Current application finalized.</div>
            //                 <div class="card card-outline card-primary mt-3">
            //                     <div class="card-header"><h3 class="card-title">Up Next:</h3></div>
            //                     <div class="card-body text-left">
            //                         <strong>Group:</strong> ${data.nextSummary.group_name}<br>
            //                         <strong>Application ID:</strong> ${data.nextSummary.app_id}<br>
            //                         <strong>Amount Applied:</strong> KES ${data.nextSummary.amount}
            //                     </div>
            //                 </div>
            //             `,
            //             icon: 'success',
            //             showDenyButton: true,
            //             confirmButtonColor: '#28a745',
            //             denyButtonColor: '#6c757d',
            //             confirmButtonText: 'Vet Next <i class="fas fa-chevron-right ml-1"></i>',
            //             denyButtonText: 'Close',
            //         }).then((result) => {
            //             if (result.isConfirmed) {
            //                 window.location.href = '/applications/' + data.nextId + '/vetting';
            //             }
            //         });
            //     }
            // });

        });

        window.addEventListener('open-modal', event => {
            $('#' + event.detail.name).modal('show');
        });

        window.addEventListener('close-modal', event => {
            $('#' + event.detail.name).modal('hide');
        });


    </script>
@endpush
