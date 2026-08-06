<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-0 font-weight-bold text-dark">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            {{ ucwords(strtolower($constituency->constituency_name)) }} Constituency
                        </h3>
                        <p class="text-muted mb-0">Data Upload</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-info px-3 py-2 shadow-sm">
                            <i class="fas fa-calendar-alt mr-1"></i> {{ $currentFY->full }} - {{ $currentFY->quarter_suffix }} Quarter
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm b-radius-15">
            <div class="card-body">
                {{-- 1. Roadmap Banner --}}
                <div class="d-flex align-items-stretch mb-4">
                    <div class="bg-info d-flex align-items-center justify-content-center px-3 b-radius-15-left" style="min-width: 60px;">
                        <i class="fas fa-route fa-lg text-white"></i>
                    </div>
                    <div class="p-3 bg-soft-info w-100 b-radius-15-right">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-1 font-weight-bold text-info-emphasis">
                                <i class="fas fa-clipboard-check mr-2"></i>Upload Roadmap
                            </h5>
                            <span class="badge badge-info shadow-sm rounded-pill">Sequential Action Required</span>
                        </div>
                        <p class="mb-0 text-muted mt-1">
                            To maintain data integrity for <strong>{{ $constituency->constituency_name }}</strong>, please process these steps in numerical order.
                            <span class="text-info-emphasis font-weight-bold">Step 2 (Groups)</span> relies on the successful completion of <span class="text-info-emphasis font-weight-bold">Step 1 (Phases)</span>.
                        </p>
                        <div class="mt-2 align-items-center">
                            <span class="extra-small text-uppercase font-weight-bold text-muted mr-3">
                                <i class="fas fa-exclamation-circle text-danger mr-1"></i> Reference the approved codelist
                            </span> <br>
                            <span class="extra-small text-uppercase font-weight-bold text-muted mr-3">
                                <i class="fas fa-asterisk text-danger mr-1"></i> Are Mandatory Fields
                            </span>
                            {{-- <div class="progress flex-grow-1" style="height: 4px; background-color: rgba(0,0,0,0.05);">
                                @php
                                    $progress = 0;
                                    if($importResult && $importResult['success']) $progress += 50;
                                    if($groupsImportResult && $groupsImportResult['success']) $progress += 50;
                                @endphp
                                <div class="progress-bar bg-info transition-all" style="width: {{ $progress }}%"></div>
                            </div> --}}
                        </div>
                    </div>
                </div>

                <div class="timeline">
                    {{-- Step 1: Loan Phases --}}
                    <div class="time-label">
                        <span class="bg-info">Step 1</span>
                    </div>
                    <div>
                        <i class="fas fa-layer-group bg-info"></i>
                        <div class="timeline-item shadow-sm border-0 b-radius-15"
                            x-data="{ dragging: false, step: @entangle('loanPhasesData').live ? 2 : 1 }">

                            <span class="time"><i class="fas fa-clock"></i> First Step</span>

                            <h3 class="timeline-header font-weight-bold">
                                <i class="fas fa-layer-group mr-2 text-info"></i>Import Loan Phases
                                <span class="text-muted small ml-2 d-none d-sm-inline">Define funding cycles for {{ $constituency->constituency_name }}</span>
                            </h3>

                            <div class="timeline-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="font-weight-bold">Phase Requirements</h6>
                                        <p class="text-muted">Establish the specific loan distribution periods. Each phase acts as a container for your groups and members.</p>

                                        <div class="bg-light p-3 rounded border-left-indigo shadow-none mb-3">
                                            <ul class="list-unstyled mb-0">
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                                    Exactly 2 columns: <strong>S/No.</strong> and <strong>Phase*</strong>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                                    Phase naming: e.g., "Phase 1", "Phase 2"
                                                </li>
                                                <li class="text-danger font-weight-bold">
                                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                                    Avoid "Refinancing/Resubmission" in names.
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm b-radius-15 h-100 bg-gradient-light">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <p class="text-center small mb-3 px-2">
                                                    Use our pre-formatted Excel template to ensure data compatibility.
                                                </p>

                                                <button type="button"
                                                        class="btn btn-outline-info btn-sm btn-block shadow-sm rounded-pill py-2 font-weight-bold"
                                                        wire:click.prevent="downloadTemplate('loan-phases')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="downloadTemplate('loan-phases')">

                                                    <span wire:loading.remove wire:target="downloadTemplate('loan-phases')">
                                                        <i class="fas fa-file-excel mr-2"></i>Download Template
                                                    </span>

                                                    <span wire:loading wire:target="downloadTemplate('loan-phases')">
                                                        <i class="fas fa-spinner fa-spin mr-1"></i> Preparing...
                                                    </span>
                                                </button>

                                                <div class="mt-2 text-center">
                                                    <span class="badge badge-soft-indigo extra-small">
                                                        <i class="fas fa-info-circle mr-1"></i> .XLSX format
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4" style="border-top: 1px dashed #ddd;">

                                {{-- Horizontal Step Tracker inside the Timeline Body --}}
                                <div class="row mb-4 text-center px-lg-5">
                                    <div class="col-4">
                                        <div class="step-circle" :class="step >= 1 ? 'active' : ''">1</div>
                                        <div class="step-label small" :class="step >= 1 ? 'font-weight-bold' : ''">Select File</div>
                                    </div>
                                    <div class="col-4 border-left">
                                        <div class="step-circle" :class="step >= 2 ? 'active' : ''">2</div>
                                        <div class="step-label small" :class="step >= 2 ? 'font-weight-bold' : ''">Verify</div>
                                    </div>
                                    <div class="col-4 border-left">
                                        <div class="step-circle" :class="step >= 3 ? 'active' : ''">3</div>
                                        <div class="step-label small" :class="step >= 3 ? 'font-weight-bold' : ''">Complete</div>
                                    </div>
                                </div>

                                {{-- Import Form --}}
                                <form wire:submit.prevent="loanPhasesForm" @submit="step = 3">
                                    <div class="form-group">
                                        <div class="upload-zone p-4 text-center transition-all b-radius-15"
                                            :class="dragging ? 'border-indigo bg-soft-indigo' : ''"
                                            @dragover.prevent="dragging = true"
                                            @dragleave.prevent="dragging = false"
                                            @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.phaseInput.files = $event.dataTransfer.files; $refs.phaseInput.dispatchEvent(new Event('change', { bubbles: true })); }"
                                            @click="$refs.phaseInput.click()"
                                            style="cursor: pointer; border: 2px dashed #cbd5e0;">

                                            <input type="file" x-ref="phaseInput" class="d-none" wire:model="loanPhasesData" accept=".xlsx,.xls,.csv">

                                            @if(!$loanPhasesData || is_array($loanPhasesData))
                                                <div class="py-2">
                                                    <div class="icon-shape bg-soft-indigo text-indigo mb-3">
                                                        <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                                    </div>
                                                    <h6 class="mb-1 font-weight-bold">Select Phase Excel File</h6>
                                                    <p class="text-muted small mb-0">Drag and drop or click to browse</p>
                                                </div>
                                            @else
                                                <div class="py-2">
                                                    <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                                    <h6 class="mb-1 text-success font-weight-bold text-truncate">
                                                        {{ is_object($loanPhasesData) ? $loanPhasesData->getClientOriginalName() : 'Selected file' }}
                                                    </h6>
                                                    <button type="button" class="btn btn-sm btn-link text-danger" @click.stop="$wire.cancelUpload('loanPhasesData'); $wire.set('loanPhasesData', null); step = 1">
                                                        <i class="fas fa-trash-alt mr-1"></i> Remove
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        @error('loanPhasesData')
                                            <div class="alert alert-danger mt-2 py-2 small shadow-sm">
                                                <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                                            </div>
                                        @enderror

                                        @if($loanPhasesData && !is_array($loanPhasesData))
                                            <button type="submit" class="btn btn-info btn-block btn-lg mt-3 shadow-sm rounded-pill py-2"
                                                    wire:loading.attr="disabled" wire:target="loanPhasesForm">
                                                <span wire:loading.remove wire:target="loanPhasesForm">
                                                    <i class="fas fa-play-circle mr-1"></i> Start Group Import
                                                </span>
                                                <span wire:loading wire:target="loanPhasesForm">
                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Processing...
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </form>

                                {{-- Results Section --}}
                                @if($importResult)
                                    <div class="mt-4 animate__animated animate__fadeIn" x-data="{ showSkipped: false }">
                                        @if($importResult['success'])
                                            <div class="card border-success bg-soft-success shadow-none">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="icon-shape bg-success text-white mr-3 shadow-sm">
                                                            <i class="fas fa-check"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 font-weight-bold text-success">Import Successful!</h6>
                                                            <p class="text-muted small mb-0">{{ $importResult['message'] }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="row no-gutters shadow-sm rounded border bg-white overflow-hidden">
                                                        <div class="col-4 p-2 border-right text-center">
                                                            <div class="h5 mb-0 font-weight-bold text-success">{{ $importResult['stats']['imported'] }}</div>
                                                            <div class="extra-small text-muted">NEW</div>
                                                        </div>
                                                        <div class="col-4 p-2 border-right text-center">
                                                            <div class="h5 mb-0 font-weight-bold text-warning">{{ $importResult['stats']['skipped'] }}</div>
                                                            <div class="extra-small text-muted">SKIPPED</div>
                                                        </div>
                                                        <div class="col-4 p-2 text-center">
                                                            <div class="h5 mb-0 font-weight-bold text-indigo">{{ $importResult['stats']['total'] }}</div>
                                                            <div class="extra-small text-muted">TOTAL</div>
                                                        </div>
                                                    </div>

                                                    @if(isset($importResult['stats']['skipped_rows']) && count($importResult['stats']['skipped_rows']) > 0)
                                                        <button @click="showSkipped = !showSkipped" class="btn btn-sm btn-link text-warning-emphasis font-weight-bold mt-2">
                                                            <i class="fas" :class="showSkipped ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                            <span x-text="showSkipped ? 'Hide Review Issues' : 'Review Issues Found'"></span>
                                                        </button>

                                                        <div x-show="showSkipped"
                                                            x-transition:enter="transition ease-out duration-300"
                                                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                                                            class="mt-3">

                                                            <div class="card border-warning-light shadow-none bg-white overflow-hidden" style="border: 1px solid #ffeeba;">
                                                                <div class="bg-soft-warning px-3 py-2 d-flex justify-content-between align-items-center">
                                                                    <span class="small font-weight-bold text-warning-emphasis">
                                                                        <i class="fas fa-info-circle mr-1"></i> Review Issues
                                                                    </span>
                                                                    <span class="badge badge-warning">{{ count($importResult['stats']['skipped_rows']) }} Issues found</span>
                                                                </div>

                                                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                                                    <table class="table table-hover mb-0">
                                                                        <thead class="bg-light sticky-top">
                                                                            <tr class="text-muted extra-small text-uppercase">
                                                                                <th class="px-3 py-2 border-top-0" style="width: 80px;">Row #</th>
                                                                                <th class="py-2 border-top-0">Data Found</th>
                                                                                <th class="py-2 border-top-0 text-right px-3">Resolution Reason</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($importResult['stats']['skipped_rows'] as $skipped)
                                                                                <tr>
                                                                                    <td class="px-3 align-middle font-weight-bold text-dark">
                                                                                        <span class="badge badge-light border">{{ $skipped['row'] }}</span>
                                                                                    </td>
                                                                                    <td class="align-middle font-weight-bold text-dark">
                                                                                        <span>{{ $skipped['phase'] ?: '[Empty Cell]' }}</span>
                                                                                    </td>
                                                                                    <td class="align-middle text-right px-3">
                                                                                        <span class="font-weight-bold">
                                                                                            {{ $skipped['reason'] }}
                                                                                        </span>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>

                                                                <div class="card-footer bg-white border-top-0 py-2">
                                                                    <p class="small text-muted mb-0">
                                                                        <i class="fas fa-lightbulb mr-1 text-info"></i>
                                                                        <strong>Tip:</strong> Fix these rows in your Excel and re-upload to complete the import.
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            {{-- Error Alert styled for Timeline --}}
                                            <div class="alert alert-soft-danger border-left-danger shadow-sm">
                                                <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle mr-2"></i>Import Failed</h6>
                                                <p class="small mb-0">{{ $importResult['message'] }}</p>
                                                @if(isset($importResult['errors']))
                                                    <ul class="small mt-2 pl-3">
                                                        @foreach($importResult['errors'] as $error) <li>{{ $error }}</li> @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Groups --}}
                    <div class="time-label">
                        <span class="bg-info">Step 2</span>
                    </div>
                    <div>
                        <i class="fas fa-users bg-info"></i>
                        <div class="timeline-item shadow-sm border-0 b-radius-15">
                            <span class="time"><i class="fas fa-clock"></i> Second Step</span>
                            <h3 class="timeline-header font-weight-bold">
                                <i class="fas fa-users mr-2 text-info"></i>Upload Groups Data With Their Financial Records
                            </h3>

                            <div class="timeline-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="icon-shape bg-soft-green text-green mr-3 rounded-circle">
                                                <i class="fas fa-users-cog"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-weight-bold mb-0">Groups Overview</h6>
                                                <p class="text-muted small mb-0">Entity management & ward-based categorization</p>
                                            </div>
                                        </div>

                                        {{-- Structured Info Grid --}}
                                        <div class="row no-gutters mb-3">
                                            <div class="col-sm-6 pr-sm-2">
                                                <div class="p-2 border rounded bg-light mb-2">
                                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Primary Link</small>
                                                    <span class="small font-weight-bold text-dark">Constituency Wards and Loan Phases</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 pl-sm-2">
                                                <div class="p-2 border rounded bg-light mb-2">
                                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Import Type</small>
                                                    <span class="small font-weight-bold text-dark">Beneficiary Groups Only <code>(approved code list)</code></span>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="text-muted small mb-3">
                                            Each group represents a primary loan applicant entity. Data must be mapped correctly to specific wards, loan phase and loan type to maintain localized reporting.
                                        </p>

                                        {{-- High-Impact Critical Warning --}}
                                        <div class="card border-0 bg-soft-danger shadow-none mb-0">
                                            <div class="card-body p-3">
                                                <div class="d-flex">
                                                    <div class="mr-3">
                                                        <i class="fas fa-fingerprint fa-2x text-danger opacity-50"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-danger font-weight-bold mb-1 small text-uppercase">Critical: Unique Identifier Rule</h6>
                                                        <p class="small text-danger-emphasis mb-0" style="line-height: 1.4;">
                                                            The <strong>Group GFS Code</strong> acts as the system's DNA.
                                                            Duplicate codes or manual overrides will corrupt financial history, member links, and disbursement tracking.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm b-radius-15 h-100 bg-gradient-light">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <p class="text-center small mb-3 px-2">
                                                    Use our pre-formatted Excel template to ensure data compatibility.
                                                </p>

                                                <button type="button"
                                                        class="btn btn-outline-info btn-sm btn-block shadow-sm rounded-pill py-2 font-weight-bold"
                                                        wire:click.prevent="downloadTemplate('groups')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="downloadTemplate('groups')">

                                                    <span wire:loading.remove wire:target="downloadTemplate('groups')">
                                                        <i class="fas fa-file-excel mr-2"></i>Download Template
                                                    </span>

                                                    <span wire:loading wire:target="downloadTemplate('groups')">
                                                        <i class="fas fa-spinner fa-spin mr-1"></i> Preparing...
                                                    </span>
                                                </button>

                                                <div class="mt-2 text-center">
                                                    <span class="badge badge-soft-indigo extra-small">
                                                        <i class="fas fa-info-circle mr-1"></i> Group_Loan_Template.xlsx
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Requirements Table --}}
                                <div class="bg-soft-info p-4 rounded-xl mb-4 mt-3 border border-info-light shadow-sm">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon-shape bg-info text-white rounded shadow-sm mr-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-table"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0">Template Requirements</h6>
                                            <p class="text-muted small mb-0">Ensure your Excel file matches this structure exactly.</p>
                                        </div>
                                    </div>

                                    {{-- 1. Visual Table Header Preview --}}
                                    <div class="table-responsive rounded shadow-sm mb-4 border bg-white">
                                        <table class="table table-sm mb-0 text-nowrap small">
                                            <thead>
                                                <tr class="bg-light text-muted extra-small text-uppercase">
                                                    <th class="px-3 border-bottom-0">S/No*</th>
                                                    <th class="border-bottom-0">Date of Disbursement*</th>
                                                    <th class="border-bottom-0">Name of Group*</th>
                                                    <th class="border-bottom-0">Ward*</th>
                                                    <th class="border-bottom-0">Loan Phase*</th>
                                                    <th class="border-bottom-0">Loan Type*</th>
                                                    <th class="border-bottom-0">GFS Code*</th>
                                                    <th class="border-bottom-0">Idetifier</th>
                                                    <th class="border-bottom-0">Category*</th>
                                                    <th class="border-bottom-0">Sector*</th>
                                                    <th class="border-bottom-0">Amount Approved*</th>
                                                    <th class="border-bottom-0">Amount Issued*</th>
                                                    <th class="border-bottom-0">Amount Repaid*</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="bg-soft-success-light">
                                                    <td class="px-3">1</td>
                                                    <td>2016-02-10</td>
                                                    <td class="font-weight-bold">WAMAMA WOMEN</td>
                                                    <td><span class="badge badge-outline-info">{{ $constituency->wards[0]->ward_name ?? 'Kasigau'}}</span></td>
                                                    <td><span class="badge badge-outline-info">Phase 1</span></td>
                                                    <td><span class="badge badge-outline-info">New</span></td>
                                                    <td class="">4-{{ $constituency->county_code }}-{{ $constituency->constituency_code }}-4110202-201-001</td>
                                                    <td>MZW001</td>
                                                    <td>Women</td>
                                                    <td>Trade</td>
                                                    <td>100000</td>
                                                    <td>90000</td>
                                                    <td>80000</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- 2. Validation Rules Grid --}}
                                    <div class="row align-items-stretch">
                                        {{-- Ward Constraints --}}
                                        <div class="col-md-6 border-right">
                                            {{-- Header --}}
                                            <div class="mb-3">
                                                <h6 class="extra-small font-weight-bold text-uppercase text-info tracking-wider mb-1">
                                                    <i class="fas fa-check-double mr-1"></i> Data Validation Rules
                                                </h6>
                                                <p class="extra-small text-muted mb-0">The following values must match your Excel file exactly.</p>
                                            </div>

                                            {{-- 1. Wards Section --}}
                                            <div class="constraint-group mb-3">
                                                <label class="extra-small font-weight-bold text-muted text-uppercase d-block mb-2">
                                                    <i class="fas fa-map-marker-alt mr-1"></i> 1. Permitted Wards
                                                </label>
                                                <div class="d-flex flex-wrap bg-light-info-subtle p-2 rounded border-dashed">
                                                    @foreach($constituency->wards as $ward)
                                                        <span class="badge badge-white border border-info-light text-info-emphasis mr-1 mb-1 shadow-xs py-1 px-2">
                                                            {{ $ward->ward_name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>

                                            {{-- 2. Phases Section --}}
                                            <div class="constraint-group mb-3">
                                                <label class="extra-small font-weight-bold text-muted text-uppercase d-block mb-2">
                                                    <i class="fas fa-layer-group mr-1"></i> 2. Permitted Loan Phases
                                                </label>
                                                <div class="d-flex flex-wrap bg-light-indigo-subtle p-2 rounded border-dashed">
                                                    @forelse($this->getPhases as $phase)
                                                        <span class="badge badge-white border border-info-light text-info-emphasis mr-1 mb-1 shadow-xs py-1 px-2">
                                                            {{ $phase->phase }}
                                                        </span>
                                                    @empty
                                                        <span class="extra-small text-muted italic">No phases defined yet. Complete Step 1 first.</span>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="constraint-group">
                                                <label class="extra-small font-weight-bold text-muted text-uppercase d-block mb-2">
                                                    <i class="fas fa-table mr-1"></i> 3. Permitted Data fields and type
                                                </label>
                                                <div class="d-flex flex-wrap bg-light-indigo-subtle p-2 rounded border-dashed">
                                                    <ul class="list-unstyled mb-0 ">
                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Date format: </strong>
                                                            <span class="font-italic">YYYY/MM/DD</span>
                                                        </div>
                                                    </li>
                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Loan Type: </strong>
                                                            <span class="font-italic">New, Refinancing, Resubmission or Reallocation</span>
                                                        </div>
                                                    </li>
                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Category: </strong>
                                                            <span class="font-italic">Youth, Women, or PWD</span>
                                                        </div>
                                                    </li>

                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Sector:</strong>
                                                            <span class="font-italic">Trade, Service, Manufacturing, Agriculture, Other</span>
                                                        </div>
                                                    </li>
                                                </ul>
                                                </div>
                                            </div>

                                            {{-- Warning Footer --}}
                                            {{-- <div class="mt-3 p-2 bg-soft-warning rounded d-flex align-items-center">
                                                <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                                                <span class="extra-small font-weight-bold text-warning-emphasis">
                                                    Case-sensitive check active. Use "Kasigau", not "kasigau".
                                                </span>
                                            </div> --}}
                                        </div>

                                        <div class="col-md-6 pl-md-4">
                                            {{-- Header --}}
                                            <div class="mb-1">
                                                <h6 class="extra-small font-weight-bold text-uppercase text-danger tracking-wider mb-1">
                                                    <i class="fas fa-file-invoice-dollar mr-1"></i> Financial Audit Rules
                                                </h6>
                                                <p class="text-muted mb-0">Values will be cross-referenced against financial records.</p>
                                            </div>

                                            {{-- Financial Integrity Card --}}
                                            <div class="card border-0 bg-soft-danger shadow-none mb-1">
                                                <div class="card-body p-3">
                                                    <h6 class="font-weight-bold text-danger text-uppercase mb-2">
                                                        <i class="fas fa-shield-alt mr-1"></i> Integrity Warning
                                                    </h6>
                                                    <p class="text-danger-emphasis mb-0" style="line-height: 1.5;">
                                                        Data in <strong>Approved</strong>, <strong>Issued</strong>, and <strong>Repaid</strong> columns must be purely numerical.
                                                        Misreporting financial data constitutes a compliance breach and will result in the <strong>immediate flagging</strong> of the data record.
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- Rules List --}}
                                            <div class="bg-white rounded p-3 border shadow-xs">
                                                <ul class="list-unstyled mb-0 ">
                                                    <li class="mb-2 d-flex align-items-center">
                                                        <i class="fas fa-hashtag text-muted mr-2"></i>
                                                        <span><strong>Format:</strong> Use numbers only (e.g., <code>50000</code>). No commas or currency symbols.</span>
                                                    </li>
                                                    <li class="mb-2 d-flex align-items-center">
                                                        <i class="fas fa-balance-scale text-muted mr-2"></i>
                                                        <span><strong>Logic:</strong> 1 - <code>Issued</code> cannot exceed <code>Approved</code>. 2 - <code>Refinancing</code> cannot be recorded before <code>New</code>.</span>
                                                    </li>
                                                    <li class="d-flex align-items-center">
                                                        <i class="fas fa-history text-muted mr-2"></i>
                                                        <span><strong>Verification:</strong> Subject to audit against bank and financial accounts records.</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Import Form with x-ref pattern --}}
                                <div id="groupsImportSection" x-data="{ dragging: false }">
                                    <form wire:submit.prevent="groupsForm">
                                        <div class="form-group">
                                            <div
                                                class="upload-zone p-5 text-center transition-all b-radius-15"
                                                :class="dragging ? 'border-info bg-soft-info' : 'border-light'"
                                                @dragover.prevent="dragging = true"
                                                @dragleave.prevent="dragging = false"
                                                @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.groupInput.files = $event.dataTransfer.files; $refs.groupInput.dispatchEvent(new Event('change', { bubbles: true })); }"
                                                @click="$refs.groupInput.click()"
                                                style="cursor: pointer; border: 2px dashed #cbd5e0;"
                                            >
                                                {{-- The Alpine Ref and Wire Model --}}
                                                <input type="file"
                                                    x-ref="groupInput"
                                                    class="d-none"
                                                    wire:model="groupsData"
                                                    accept=".xlsx,.xls,.csv">

                                                @if(!$groupsData || is_array($groupsData))
                                                    <div class="py-2">
                                                        <div class="icon-shape bg-soft-info text-info mb-3">
                                                            <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                                        </div>
                                                        <h6 class="mb-1 font-weight-bold">Select Groups Excel File</h6>
                                                        <p class="text-muted small mb-0">Drag and drop or click to browse (Max 10MB)</p>
                                                    </div>
                                                @else
                                                    <div class="py-2">
                                                        <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                                        <h6 class="mb-1 text-success font-weight-bold text-truncate">
                                                            {{ is_object($groupsData) ? $groupsData->getClientOriginalName() : 'Selected file' }}
                                                        </h6>
                                                        <p class="small text-muted mb-2">{{ number_format($groupsData->getSize() / 1024 / 1024, 2) }} MB</p>
                                                        <button type="button" class="btn btn-sm btn-link text-danger" @click.stop="$wire.cancelUpload('groupsData'); $wire.set('groupsData', null)">
                                                            <i class="fas fa-trash-alt mr-1"></i> Remove File
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Validation Errors --}}
                                            @error('groupsData')
                                                <div class="alert alert-danger mt-2 py-2 small shadow-sm">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                                                </div>
                                            @enderror

                                            {{-- Upload Progress (File hitting server) --}}
                                            <div wire:loading wire:target="groupsData" class="mt-3 text-center">
                                                <div class="progress progress-xxs mb-2">
                                                    <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                                </div>
                                                <small class="text-muted italic">Uploading to server... please wait</small>
                                            </div>
                                        </div>

                                        {{-- Processing Progress (Database import) --}}
                                        @if($importProgressGroups > 0)
                                            <div class="mt-4 p-3 border rounded bg-light">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="font-weight-bold">{{ $importMessageGroups }}</small>
                                                    <small class="font-weight-bold text-info">{{ $importProgressGroups }}%</small>
                                                </div>
                                                <div class="progress progress-sm rounded-pill shadow-sm">
                                                    <div class="progress-bar bg-info progress-bar-striped progress-bar-animated"
                                                        role="progressbar" :style="'width: ' + {{ $importProgressGroups }} + '%'">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($groupsData && !is_array($groupsData) && $importProgressGroups == 0)
                                            <button type="submit" class="btn btn-info btn-block btn-lg mt-3 shadow-sm rounded-pill py-2"
                                                    wire:loading.attr="disabled" wire:target="groupsForm">
                                                <span wire:loading.remove wire:target="groupsForm">
                                                    <i class="fas fa-play-circle mr-1"></i> Start Group Import
                                                </span>
                                                <span wire:loading wire:target="groupsForm">
                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Processing...
                                                </span>
                                            </button>
                                        @endif
                                    </form>
                                </div>

                                {{-- Results Section --}}
                                @if($groupsImportResult)
                                    <div class="mt-4 animate__animated animate__fadeIn">
                                        @if($groupsImportResult['success'])
                                            <div class="card border-success bg-soft-success">
                                                <div class="card-body py-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                                                        <div>
                                                            <h6 class="mb-0 font-weight-bold text-success">Import Successful!</h6>
                                                            <small class="text-muted">{{ $groupsImportResult['message'] }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-6 text-center border-right">
                                                            <div class="h5 mb-0 font-weight-bold text-success">{{ $groupsImportResult['stats']['new'] }}</div>
                                                            <div class="extra-small text-uppercase text-muted">New {{ Str::plural('Group', $groupsImportResult['stats']['new']) }} Created</div>
                                                        </div>
                                                        <div class="col-6 text-center">
                                                            <div class="h5 mb-0 font-weight-bold text-info">{{ $groupsImportResult['stats']['imported'] }}</div>
                                                            <div class="extra-small text-uppercase text-muted">Total Entries</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            {{-- Error Table for Groups --}}
                                            <div class="card border-danger shadow-none overflow-hidden" x-data="{ open: true }">
                                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-2" @click="open = !open" style="cursor: pointer;">
                                                    <h6 class="mb-0 small font-weight-bold"><i class="fas fa-times-circle mr-2"></i> Import Failed: {{ $groupsImportResult['total_errors'] }} Errors</h6>
                                                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                                </div>
                                                <div x-show="open" class="table-responsive" style="max-height: 300px;">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead class="bg-light sticky-top">
                                                            <tr class="extra-small text-uppercase text-muted">
                                                                <th class="text-center px-3" width="15%">Row</th>
                                                                <th>Error Description</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="">
                                                            @foreach($groupsImportResult['errors'] as $error)
                                                                <tr>
                                                                    <td class="text-center align-middle font-weight-bold text-danger">
                                                                        #{{ $error['row'] }}
                                                                    </td>
                                                                    <td class="py-2 text-muted">
                                                                        <i class="fas fa-info-circle text-info mr-1"></i>
                                                                        {{-- Use whitespace-pre-wrap to show the "Current/Existing" group indenting --}}
                                                                        <span style="white-space: pre-wrap; font-family: inherit;">{{ $error['message'] }}</span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: Members Data --}}
                    <div class="time-label">
                        <span class="bg-info">Step 3</span>
                    </div>
                    <div>
                        <i class="fas fa-user-plus bg-info"></i>
                        <div class="timeline-item shadow-sm border-0 b-radius-15">
                            <span class="time"><i class="fas fa-clock"></i> Third Step</span>
                            <h3 class="timeline-header font-weight-bold">
                                <i class="fas fa-user-tie mr-2 text-info"></i>Import Individual Group Members
                            </h3>

                            <div class="timeline-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="icon-shape bg-soft-primary text-primary mr-3 rounded-circle">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-weight-bold mb-0">Members Onboarding</h6>
                                                <p class="text-muted small mb-0">Linking individuals to their respective GFS-coded groups</p>
                                            </div>
                                        </div>

                                        {{-- Structured Info Grid --}}
                                        <div class="row no-gutters mb-3">
                                            <div class="col-sm-6 pr-sm-2">
                                                <div class="p-2 border rounded bg-light mb-2">
                                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Primary Link</small>
                                                    <span class="small font-weight-bold text-dark">Phase, Group Name, GFS Code & ID Numbers</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 pl-sm-2">
                                                <div class="p-2 border rounded bg-light mb-2">
                                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.65rem;">Import Scope</small>
                                                    <span class="small font-weight-bold text-dark">Individual Beneficiaries Only</span>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="text-muted small mb-3">
                                            Members must be mapped to existing groups using the <strong>Phase + Loan Type + Group Name + Group GFS Code </strong>. Ensure every member has a valid National ID and unique phone number to prevent KYC (Know Your Customer) conflicts.
                                        </p>

                                        {{-- Critical Warning --}}
                                        <div class="card border-0 bg-soft-warning shadow-none mb-0">
                                            <div class="card-body p-3">
                                                <div class="d-flex">
                                                    <div class="mr-3">
                                                        <i class="fas fa-exclamation-triangle fa-2x text-warning opacity-50"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-warning-emphasis font-weight-bold mb-1 small text-uppercase">Member Linkage Rule</h6>
                                                        <p class="small text-muted mb-0" style="line-height: 1.4;">
                                                            If the <strong>Group GFS Code</strong> in your Excel does not exist in the system, those members will be rejected. Import groups (Step 2) before importing members.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm b-radius-15 h-100 bg-gradient-light">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <p class="text-center small mb-3 px-2">
                                                    Use the specific Member Template to capture bio-data correctly.
                                                </p>

                                                <button type="button"
                                                        class="btn btn-outline-info btn-sm btn-block shadow-sm rounded-pill py-2 font-weight-bold"
                                                        wire:click.prevent="downloadTemplate('members')"
                                                        wire:loading.attr="disabled">
                                                    <span wire:loading.remove>
                                                        <i class="fas fa-file-excel mr-2"></i>Download Template
                                                    </span>
                                                    <span wire:loading>
                                                        <i class="fas fa-spinner fa-spin mr-1"></i> Preparing...
                                                    </span>
                                                </button>

                                                <div class="mt-2 text-center">
                                                    <span class="badge badge-soft-primary extra-small">
                                                        <i class="fas fa-info-circle mr-1"></i> Member_Template.xlsx
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Requirements Table --}}
                                <div class="bg-soft-info p-4 rounded-xl mb-4 mt-3 border border-primary-light shadow-sm">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon-shape bg-primary text-white rounded shadow-sm mr-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-list-ol"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0">Member Template Requirements</h6>
                                            <p class="text-muted small mb-0">Required columns for individual group linkage.</p>
                                        </div>
                                    </div>

                                    {{-- Table Preview --}}
                                    <div class="table-responsive rounded shadow-sm mb-4 border bg-white">
                                        <table class="table table-sm mb-0 text-nowrap small">
                                            <thead>
                                                <tr class="bg-light text-muted extra-small text-uppercase">
                                                    <th class="px-3 border-bottom-0">S/No*</th>
                                                    <th class="border-bottom-0">Phase*</th>
                                                    <th class="border-bottom-0">Loan Type*</th>
                                                    <th class="border-bottom-0">Group Name*</th>
                                                    <th class="border-bottom-0">GFS Code*</th>
                                                    <th class="border-bottom-0">Member Name*</th>
                                                    <th class="border-bottom-0">Member ID*</th>
                                                    <th class="border-bottom-0">Member Role*</th>
                                                    <th class="border-bottom-0">Phone Number*</th>
                                                    <th class="border-bottom-0">Gender*</th>
                                                    <th class="border-bottom-0">Disabled*</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="bg-soft-success-light">
                                                    <td class="px-3">1</td>
                                                    <td><span class="badge badge-outline-info">Phase 1</span></td>
                                                    <td><span class="badge badge-outline-info">New</span></td>
                                                    <td class="font-weight-bold">WAMAMA WOMEN</td>
                                                    <td class="">4-{{ $constituency->county_code }}-{{ $constituency->constituency_code }}-4110202-201-001</td>
                                                    <td class="font-weight-bold">JANE DOE</td>
                                                    <td>12345678</td>
                                                    <td><span class="badge badge-outline-info">Chairperson</span></td>
                                                    <td>0712345678</td>
                                                    <td><span class="badge badge-outline-info">Female</span></td>
                                                    <td><span class="badge badge-outline-info">No</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Validation Rules --}}
                                    <div class="row align-items-stretch">
                                        <div class="col-md-6 border-right">
                                            <div class="mb-3">
                                                <h6 class="extra-small font-weight-bold text-uppercase text-info tracking-wider mb-1">
                                                    <i class="fas fa-user-check mr-1"></i> Bio-Data Validation
                                                </h6>
                                            </div>

                                            <div class="constraint-group mb-3">
                                                <label class="extra-small font-weight-bold text-muted text-uppercase d-block mb-2">
                                                    <i class="fas fa-venus-mars mr-1"></i> 1. Permitted Designations
                                                </label>
                                                <div class="d-flex flex-wrap bg-light-info-subtle p-2 rounded border-dashed">
                                                    <span class="badge badge-white border border-info-light text-info mr-1 mb-1 shadow-xs py-1 px-2">Chairperson</span>
                                                    <span class="badge badge-white border border-info-light text-info mr-1 mb-1 shadow-xs py-1 px-2">Secretary</span>
                                                    <span class="badge badge-white border border-info-light text-info mr-1 mb-1 shadow-xs py-1 px-2">Treasurer</span>
                                                    <span class="badge badge-white border border-info-light text-info mr-1 mb-1 shadow-xs py-1 px-2">Member</span>
                                                </div>
                                            </div>

                                            {{-- 2. Phases Section --}}
                                            <div class="constraint-group mb-3">
                                                <label class="extra-small font-weight-bold text-muted text-uppercase d-block mb-2">
                                                    <i class="fas fa-layer-group mr-1"></i> 2. Permitted Loan Phases
                                                </label>
                                                <div class="d-flex flex-wrap bg-light-indigo-subtle p-2 rounded border-dashed">
                                                    @forelse($this->getPhases as $phase)
                                                        <span class="badge badge-white border border-info-light text-info-emphasis mr-1 mb-1 shadow-xs py-1 px-2">
                                                            {{ $phase->phase }}
                                                        </span>
                                                    @empty
                                                        <span class="extra-small text-muted italic">No phases defined yet. Complete Step 1 first.</span>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="constraint-group">
                                                <label class="extra-small font-weight-bold text-muted text-uppercase d-block mb-2">
                                                    <i class="fas fa-table mr-1"></i> 3. Permitted Data fields and type
                                                </label>
                                                <div class="d-flex flex-wrap bg-light-indigo-subtle p-2 rounded border-dashed">
                                                    <ul class="list-unstyled mb-0 ">
                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>ID Number: </strong>
                                                            <span class="font-italic">Must be 6-8 digits. No letters.</span>
                                                        </div>
                                                    </li>
                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Phone Number: </strong>
                                                            <span class="font-italic">Start with 07... or 01... (10 digits). </span>
                                                            <code>Start with 7... or 1... for (9 digits).</code>
                                                        </div>
                                                    </li>
                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Gender: </strong>
                                                            <span class="font-italic">Female or Male.</span>
                                                        </div>
                                                    </li>

                                                    <li class="mb-2 d-flex align-items-start">
                                                        <span class="text-success mr-2">●</span>
                                                        <div>
                                                            <strong>Disability Status:</strong>
                                                            <span class="font-italic">Yes or No.</span>
                                                        </div>
                                                    </li>
                                                </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 pl-md-4">
                                            <div class="mb-1">
                                                <h6 class="extra-small font-weight-bold text-uppercase text-danger tracking-wider mb-1">
                                                    <i class="fas fa-link mr-1"></i> Integrity & Duplication
                                                </h6>
                                            </div>

                                            <div class="card border-0 bg-soft-danger shadow-none mb-1">
                                                <div class="card-body p-3">
                                                    <p class="text-danger-emphasis mb-0 small" style="line-height: 1.5;">
                                                        The system performs a <strong>Global Identity Check</strong>. An ID number cannot belong to multiple different members.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="bg-white rounded p-3 border shadow-xs">
                                                <ul class="list-unstyled mb-0 extra-small">
                                                    <li class="mb-2 d-flex align-items-center">
                                                        <i class="fas fa-fingerprint text-muted mr-2"></i>
                                                        <span><strong>Unique ID:</strong> System flags duplicate National ID numbers on different members.</span>
                                                    </li>
                                                    <li class="d-flex align-items-center">
                                                        <i class="fas fa-network-wired text-muted mr-2"></i>
                                                        <span><strong>GFS Matching:</strong> Exact string match required for Group linkage.</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Import Form --}}
                                <div id="membersImportSection" x-data="{ dragging: false }">
                                    <form wire:submit.prevent="groupMembersForm">
                                        <div class="form-group">
                                            <div class="upload-zone p-5 text-center transition-all b-radius-15"
                                                :class="dragging ? 'border-info bg-soft-info' : 'border-light'"
                                                @dragover.prevent="dragging = true"
                                                @dragleave.prevent="dragging = false"
                                                @drop.prevent="dragging = false; if ($event.dataTransfer.files.length) { $refs.memberInput.files = $event.dataTransfer.files; $refs.memberInput.dispatchEvent(new Event('change', { bubbles: true })); }"
                                                @click="$refs.memberInput.click()"
                                                style="cursor: pointer; border: 2px dashed #4e73df;">

                                                <input type="file" x-ref="memberInput" class="d-none"
                                                    wire:model="groupMembersData" accept=".xlsx,.xls,.csv">

                                                @if(!$groupMembersData || is_array($groupMembersData))
                                                    <div class="py-2">
                                                        <div class="icon-shape bg-soft-info text-info mb-3">
                                                            <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                                        </div>
                                                        <h6 class="mb-1 font-weight-bold">Select Members Excel File</h6>
                                                        <p class="text-muted small mb-0">Drag and drop or click to browse</p>
                                                    </div>
                                                @else
                                                    <div class="py-2">
                                                        <i class="fas fa-file-excel fa-3x text-info mb-3"></i>
                                                        <h6 class="mb-1 text-info font-weight-bold">
                                                            {{ is_object($groupMembersData) ? $groupMembersData->getClientOriginalName() : 'Selected file' }}
                                                        </h6>
                                                        <button type="button" class="btn btn-sm btn-link text-danger" @click.stop="$wire.cancelUpload('groupMembersData'); $wire.set('groupMembersData', null)">
                                                            <i class="fas fa-trash-alt mr-1"></i> Remove
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if($importProgressGroupMembers > 0 && $importingGroupMembers)
                                            <div class="mt-4 p-3 border rounded bg-light">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="font-weight-bold">{{ $importMessageGroupMembers }}</small>
                                                    <small class="font-weight-bold text-info">{{ $importProgressGroupMembers }}%</small>
                                                </div>
                                                <div class="progress progress-sm rounded-pill shadow-sm">
                                                    <div class="progress-bar bg-info progress-bar-striped progress-bar-animated"
                                                        role="progressbar" style="width: {{ $importProgressGroupMembers }}%">
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-2">Large files are processed in the background — this page will update automatically.</small>
                                            </div>
                                        @endif

                                        @if($groupMembersData && !is_array($groupMembersData) && !$importingGroupMembers)
                                            <button type="submit" class="btn btn-info btn-block btn-lg mt-3 shadow-sm rounded-pill py-2"
                                                    wire:loading.attr="disabled"
                                                    wire:target="groupMembersData, groupMembersForm">
                                                <span wire:loading.remove wire:target="groupMembersData, groupMembersForm">
                                                    <i class="fas fa-upload mr-1"></i> Start Member Import
                                                </span>
                                                <span wire:loading wire:target="groupMembersData, groupMembersForm">
                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                                                </span>
                                            </button>
                                        @endif
                                    </form>
                                </div>

                                {{-- Results Section --}}
                                @if($groupMembersImportResult)
                                    <div class="mt-4 animate__animated animate__fadeIn">
                                        @if($groupMembersImportResult['success'])
                                            <div class="card border-success bg-soft-success">
                                                <div class="card-body py-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                                                        <div>
                                                            <h6 class="mb-0 font-weight-bold text-success">Import Successful!</h6>
                                                            <small class="text-muted">{{ $groupMembersImportResult['message'] }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-6 text-center border-right">
                                                            <div class="h5 mb-0 font-weight-bold text-success">{{ $groupMembersImportResult['stats']['new'] }}</div>
                                                            <div class="extra-small text-uppercase text-muted">New {{ Str::plural('Member', $groupMembersImportResult['stats']['new']) }} Created</div>
                                                        </div>
                                                        <div class="col-6 text-center">
                                                            <div class="h5 mb-0 font-weight-bold text-info">{{ $groupMembersImportResult['stats']['imported'] }}</div>
                                                            <div class="extra-small text-uppercase text-muted">Total Entries</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            {{-- Error Table for Members --}}
                                            <div class="card border-danger shadow-none overflow-hidden" x-data="{ open: true }">
                                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-2" @click="open = !open" style="cursor: pointer;">
                                                    <h6 class="mb-0 small font-weight-bold"><i class="fas fa-times-circle mr-2"></i> Import Failed: {{ $groupMembersImportResult['total_errors'] }} Errors</h6>
                                                    <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                                </div>
                                                <div x-show="open" class="table-responsive" style="max-height: 500px;">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead class="bg-light sticky-top">
                                                            <tr class="extra-small text-uppercase text-muted">
                                                                <th class="text-center px-3" width="15%">Row</th>
                                                                <th>Error Description</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="">
                                                            @foreach($groupMembersImportResult['errors'] as $error)
                                                                <tr>
                                                                    <td class="text-center align-middle font-weight-bold text-danger">
                                                                        #{{ $error['row'] }}
                                                                    </td>
                                                                    <td class="py-2 text-muted">
                                                                        <i class="fas fa-info-circle text-info mr-1"></i>
                                                                        {{-- Use whitespace-pre-wrap to show the "Current/Existing" group indenting --}}
                                                                        <span style="white-space: pre-wrap; font-family: inherit;">{{ $error['message'] }}</span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div><i class="fas fa-check-double bg-gray"></i></div>
                </div>
            </div>
        </div>
    </div>

</section>

@push('styles')
    <style>
        .upload-zone {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            position: relative;
            transition: all 0.3s ease;
            background: #f9fafb;
            cursor: pointer;
        }
        .upload-zone:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .upload-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            opacity: 0;
            cursor: pointer;
        }
        .icon-shape {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
        .bg-soft-primary { background-color: #e7f1ff; color: #007bff; }
        .bg-soft-success { background-color: #e6fffa; color: #38b2ac; }


        :root {
            --indigo-primary: #4e73df;
            --indigo-hover: #224abe;
            --indigo-soft: #f0f3ff;
        }

        /* Custom Palette */
        .text-indigo { color: var(--indigo-primary) !important; }
        .bg-soft-indigo { background-color: var(--indigo-soft) !important; }
        .border-indigo { border-color: var(--indigo-primary) !important; }
        .btn-indigo { background: var(--indigo-primary); color: white; border: none; }
        .btn-indigo:hover { background: var(--indigo-hover); color: white; }
        .btn-indigo-outline { border: 1px solid var(--indigo-primary); color: var(--indigo-primary); background: transparent; }

        /* Step Circle Styling */
        .step-circle {
            width: 32px; height: 32px; border-radius: 50%;
            background: #edf2f7; color: #a0aec0;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 8px; transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .step-circle.active {
            background: var(--indigo-primary); color: white;
            box-shadow: 0 4px 6px rgba(78, 115, 223, 0.2);
        }

        /* Border accent for Requirements */
        .border-left-indigo {
            border-left: 4px solid var(--indigo-primary);
        }

        .transition-all { transition: all 0.2s ease-in-out; }

        .rounded-xl { border-radius: 12px !important; }
        .shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .bg-soft-success-light { background-color: rgba(40, 167, 169, 0.03); }
        .border-info-light { border-color: rgba(23, 162, 184, 0.2) !important; }

        .badge-outline-info {
            color: #17a2b8;
            border: 1px solid #17a2b8;
            background: transparent;
        }
        .border-dashed {
            border: 1px dashed rgba(0,0,0,0.1) !important;
        }
        .bg-light-info-subtle {
            background-color: rgba(23, 162, 184, 0.03);
        }

        .bg-light-indigo-subtle {
            background-color: rgba(102, 16, 242, 0.03);
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            let membersPollTimer = null;

            const stopMembersPolling = () => {
                if (membersPollTimer) {
                    clearInterval(membersPollTimer);
                    membersPollTimer = null;
                }
            };

            Livewire.on('start-members-polling', () => {
                stopMembersPolling();
                membersPollTimer = setInterval(() => {
                    Livewire.dispatch('poll-members-progress');
                }, 2000);
            });

            Livewire.on('stop-members-polling', () => {
                stopMembersPolling();
            });
        });
    </script>
@endpush
