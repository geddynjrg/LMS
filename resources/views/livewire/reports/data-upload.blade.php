<section class="content">
    <div class="container-fluid">
        {{-- Import Process Guide --}}
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>Import Process Guide
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Important!</h5>
                                    Follow these steps <strong>in order</strong> to avoid errors. Each step depends on the previous one. Ensure all mandatory fields are filled <strong>(fields marked with (*))</strong>
                                </div>

                                <div class="timeline">
                                    {{-- Step 1: Loan Phases --}}
                                    <div class="time-label">
                                        <span class="bg-success">Step 1</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-clipboard-list bg-blue"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> First Step</span>
                                            <h3 class="timeline-header">
                                                <i class="fas fa-clipboard-list mr-2"></i>Import Loan Phases
                                            </h3>
                                            <div class="timeline-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h6>What are Loan Phases?</h6>
                                                        <p>Loan phases define the funding cycles in the constituency (e.g., Phase 1, Phase 2, Phase 3).</p>

                                                        <h6>Import Instructions:</h6>
                                                        <ul>
                                                            <li>Download the template</li>
                                                            <li>Fill in constituency codes and phase names</li>
                                                            <li>Upload the completed file</li>
                                                            <li>System will validate and import the data</li>
                                                        </ul>

                                                        <div class="alert alert-info small">
                                                            <i class="fas fa-lightbulb mr-2"></i>
                                                            <strong>Tip:</strong> Name your phases as "Phase 1, Phase 2, Phase 3... Do not include naming as New/Refinancing/Resubmition. Just the Phase Name" <br> Do not repeat naming.
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card card-outline card-success">
                                                            <div class="card-header">
                                                                <h6 class="card-title mb-0">Quick Actions</h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <button type="submit" class="btn btn-info"
                                                                        wire:click.prevent="downloadTemplate('loan-phases')"
                                                                        wire:loading.attr="disabled"
                                                                        wire:target="downloadTemplate('loan-phases')">
                                                                    <span wire:loading.remove wire:target="downloadTemplate">
                                                                        <i class="fas fa-download mr-1"></i>Download Template
                                                                    </span>
                                                                    <span wire:loading wire:target="downloadTemplate">
                                                                        <i class="fas fa-spinner fa-spin mr-1"></i> processing...
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Loan Phases Import Section --}}
                                                <div id="loanPhasesImportSection" class="mt-3">
                                                    <div class="card card-outline card-primary">
                                                        <div class="card-header">
                                                            <h6 class="card-title mb-0">
                                                                <i class="fas fa-upload mr-1"></i>Import Loan Phases
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="alert alert-info">
                                                                <h6><i class="fas fa-info-circle mr-2"></i>Template Format Requirements</h6>
                                                                <p class="mb-1">Your Excel file <strong>must</strong> have exactly 2 columns with these exact headers:</p>
                                                                <table class="table table-sm table-bordered mt-2 bg-white">
                                                                    <thead class="bg-light">
                                                                        <tr>
                                                                            <th class="text-center">S/No.</th>
                                                                            <th class="text-center">Phase*</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="text-center">1</td>
                                                                            <td class="text-center"><strong>Phase 1</strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="text-center">2</td>
                                                                            <td class="text-center"><strong>Phase 2</strong></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="text-center">3</td>
                                                                            <td class="text-center"><strong>Phase 3A</strong></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                                <div class="mt-2">
                                                                    <strong>Valid Phase Name Examples:</strong>
                                                                    <ul class="mb-1">
                                                                        <li>Phase 1, Phase 2, Phase 3</li>
                                                                        <li>Phase 2A, Phase 3B, Phase 4C</li>
                                                                    </ul>
                                                                </div>
                                                                <p class="mb-0 mt-2"><strong>Note:</strong> Phases will be imported for constituency: <strong>{{ $constituency->constituency_name }}</strong> ({{ $constituency->constituency_code }})</p>
                                                            </div>

                                                            <form id="loanPhasesForm" wire:submit.prevent="loanPhasesForm" class="import-form">
                                                                @csrf
                                                                <div class="form-group">
                                                                    <label for="loan_phases_file">Select Excel File</label>
                                                                    {{-- <div class="input-group">
                                                                        <div class="custom-file">
                                                                            <input type="file" wire:model="loanPhasesData" class="custom-file-input"
                                                                                id="loan_phases_file" accept=".xlsx,.xls,.csv"
                                                                                @if($importing) disabled @endif required>
                                                                            <label class="custom-file-label" for="loan_phases_file" id="loan_phases_file_label">
                                                                                @if($loanPhasesData)
                                                                                    {{ $loanPhasesData->getClientOriginalName() }}
                                                                                @else
                                                                                    No file chosen
                                                                                @endif
                                                                            </label>
                                                                        </div>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-outline-danger" type="button"
                                                                                    wire:click="removeFile('loanPhasesData')"
                                                                                    @if(!$loanPhasesData || $importing) disabled @endif>
                                                                                <i class="fas fa-times"></i> Remove
                                                                            </button>
                                                                        </div>
                                                                    </div> --}}
                                                                    <div class="input-group">
                                                                        <div class="custom-file">
                                                                            <input type="file" class="custom-file-input"
                                                                                id="loan_phases_file" accept=".xlsx,.xls,.csv" name="loanPhasesData" required
                                                                                onchange="uploadGroupFile(this)">
                                                                            <label wire:ignore class="custom-file-label" for="loan_phases_file" id="loan_phases_file_label">
                                                                                <span id="phasesFileName">No file chosen</span>
                                                                                <span id="phasesInputSpiner" hidden>
                                                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                                                                                </span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-outline-danger" type="button"
                                                                                    onclick="removeFile('loanPhasesData')"
                                                                                    @if(!$loanPhasesData) disabled @endif>
                                                                                <i class="fas fa-times"></i> Remove
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <small class="form-text text-muted">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</small>
                                                                    @error('loanPhasesData') <span class="error text-danger">{{ $message }}</span> @enderror

                                                                    {{-- Show file info when selected --}}
                                                                    @if($loanPhasesData)
                                                                        <div class="mt-2 alert alert-info py-2">
                                                                            <small>
                                                                                <i class="fas fa-file-excel mr-1 text-success"></i>
                                                                                Selected: <strong>{{ $loanPhasesData->getClientOriginalName() }}</strong>
                                                                                ({{ number_format(($loanPhasesData->getSize() / 1024) / 1024, 2) }} MiB)
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                {{-- Progress Bar --}}

                                                                <div wire:loading wire:target="loanPhasesForm" class="mt-3 w-100">
                                                                    <div class="progress progress-sm rounded">
                                                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                                            role="progressbar" style="width: {{ $importProgress }}%">
                                                                            <span class="sr-only">{{ $importProgress }}% Complete</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-center mt-1">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-check mr-1"></i> {{ $importMessage }}... {{ $importProgress }}%
                                                                        </small>
                                                                    </div>
                                                                </div>

                                                                {{-- Submit Button --}}
                                                                @if($loanPhasesData)
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <button type="submit" class="btn btn-info"
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="loanPhasesForm">
                                                                        <span wire:loading.remove wire:target="loanPhasesForm">
                                                                            <i class="fas fa-upload mr-1"></i>Start Import
                                                                        </span>
                                                                        <span wire:loading wire:target="loanPhasesForm">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i> Importing...
                                                                        </span>
                                                                    </button>
                                                                </div>
                                                                @endif
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>



                                                {{-- Import Results --}}
                                                @if($importResult)
                                                    @if($importResult['success'])
                                                        <div class="alert alert-success mt-3">
                                                            <h6><i class="fas fa-check-circle mr-2"></i>Import Successful!</h6>
                                                            <p class="mb-2">{{ $importResult['message'] }}</p>
                                                            @if(isset($importResult['stats']))
                                                                <div class="row text-center mt-2">
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-success">{{ $importResult['stats']['imported'] }}</div>
                                                                            <small class="text-muted">New Inserted Phases</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-warning">{{ $importResult['stats']['skipped'] }}</div>
                                                                            <small class="text-muted">Skipped (Existing)</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-primary">{{ $importResult['stats']['total'] }}</div>
                                                                            <small class="text-muted">Total Processed</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Show skipped rows details --}}
                                                                @if(isset($importResult['stats']['skipped_rows']) && count($importResult['stats']['skipped_rows']) > 0)
                                                                    <div class="mt-3">
                                                                        <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="collapse" data-target="#skippedPhases">
                                                                            <i class="fas fa-eye mr-1"></i> Show Skipped Phases Details
                                                                        </button>

                                                                        <div class="collapse mt-2" id="skippedPhases">
                                                                            <div class="card card-body">
                                                                                <h6 class="mb-2">Skipped Phases (Already Exist):</h6>
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-sm table-bordered">
                                                                                        <thead class="text-body">
                                                                                            <tr>
                                                                                                <th>Row</th>
                                                                                                <th>Phase Name</th>
                                                                                                <th>Reason</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="text-black-50">
                                                                                            @foreach($importResult['stats']['skipped_rows'] as $skipped)
                                                                                            <tr>
                                                                                                <td>{{ $skipped['row'] }}</td>
                                                                                                <td>{{ $skipped['phase'] }}</td>
                                                                                                <td>{{ $skipped['reason'] }}</td>
                                                                                            </tr>
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="alert alert-danger mt-3">
                                                            <h6><i class="fas fa-exclamation-triangle mr-2"></i>Import Failed!</h6>
                                                            <p class="mb-2">{{ $importResult['message'] }}</p>
                                                            @if(isset($importResult['errors']) && count($importResult['errors']) > 0)
                                                                <div class="mt-2">
                                                                    <strong>Errors:</strong>
                                                                    <ul class="mb-0 pl-3">
                                                                        @foreach($importResult['errors'] as $error)
                                                                            <li>{{ $error }}</li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Step 2: Groups --}}
                                    <div class="time-label">
                                        <span class="bg-success">Step 2</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-users bg-green"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> Second Step</span>
                                            <h3 class="timeline-header">
                                                <i class="fas fa-users mr-2"></i>Import Groups
                                            </h3>
                                            <div class="timeline-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h6>Groups</h6>
                                                        <p>These are Groups that have applied for loans over the period. Each group belongs to a specific ward within the constituency.</p>
                                                        <p>Note that this group data is only for <strong>New groups for every loan phase</strong></p>

                                                        <div class="alert alert-danger small">
                                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                                            <strong>Important: GROUP CODE ACCURACY CRITICAL.</strong> The <u>Group Code</u> is a critical and unique identifier for every group. It must never be duplicated or entered incorrectly. Using a duplicate or wrong Group Code will cause import failures and may result in serious data inconsistencies — such as creating duplicate groups, linking financial transactions to the wrong group, and associating members with incorrect records. Always double-check the Group Code before importing.
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card card-outline card-info">
                                                            <div class="card-header">
                                                                <h6 class="card-title mb-0">Quick Actions</h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <button type="submit" class="btn btn-info"
                                                                        wire:click.prevent="downloadTemplate('groups')"
                                                                        wire:loading.attr="disabled"
                                                                        wire:target="downloadTemplate('groups')">
                                                                    <span wire:loading.remove wire:target="downloadTemplate">
                                                                        <i class="fas fa-download mr-1"></i>Download Template
                                                                    </span>
                                                                    <span wire:loading wire:target="downloadTemplate">
                                                                        <i class="fas fa-spinner fa-spin mr-1"></i> processing...
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Groups Import Section --}}
                                                <div id="groupsImportSection" class="mt-3">
                                                    <div class="card card-outline card-info">
                                                        <div class="card-header">
                                                            <h6 class="card-title mb-0">
                                                                <i class="fas fa-upload mr-1"></i>Import Groups
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="alert alert-info">
                                                                <h6><i class="fas fa-info-circle mr-2"></i>Template Requirements</h6>
                                                                <p class="mb-1">Your Excel file <strong>must</strong> have these columns:</p>
                                                                <table class="table table-sm table-responsive table-bordered mt-2 bg-white table-hover text-nowrap">
                                                                    <thead class="bg-light">
                                                                        <tr>
                                                                            <th>S/No*</th>
                                                                            <th>Ward*</th>
                                                                            <th>Name*</th>
                                                                            <th>Code*</th>
                                                                            <th>GFS Code*</th>
                                                                            <th>Identifier</th>
                                                                            <th>Reg Number*</th>
                                                                            <th>Category*</th>
                                                                            <th>Activity*</th>
                                                                            <th>Date Formed</th>
                                                                            <th>Date Registered</th>
                                                                            <th>Bank</th>
                                                                            <th>Account</th>
                                                                            <th>Activity Desc</th>
                                                                            <th>Location</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1</td>
                                                                            <td><strong>Kasigau</strong></td>
                                                                            <td>MZAWO WOMEN GROUP</td>
                                                                            <td><strong>001</strong></td>
                                                                            <td>4-006-026-4110202-201-001</td>
                                                                            <td>MZA001</td>
                                                                            <td>TTA/CD/2/3677</td>
                                                                            <td><strong>Women</strong></td>
                                                                            <td><strong>Service</strong></td>
                                                                            <td>2016-02-02</td>
                                                                            <td>2016-02-10</td>
                                                                            <td>Equity Bank</td>
                                                                            <td>123456</td>
                                                                            <td>Salons/Cosmetics</td>
                                                                            <td>Kasigau</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>

                                                                <div class="mt-2">
                                                                    <strong>Important Instructions:</strong>
                                                                    <ul class="mb-0">
                                                                        <li>Fields marked with * are <strong>mandatory</strong></li>
                                                                        <li>Ward names must exist in {{ $constituency->constituency_name }} constituency</li>
                                                                        <li>Available wards:
                                                                            @foreach($constituency->wards as $ward)
                                                                                <strong>{{ $ward->ward_name }},</strong>
                                                                            @endforeach
                                                                        </li>
                                                                        <li>Category must be: <strong>Youth, Women, or PWD</strong></li>
                                                                        <li>Activity must be: <strong>Trade, Service, Manufacturing, Agriculture, or Other</strong></li>
                                                                        <li>Group Code, GFS Code, and Registration Number must be <strong>unique</strong></li>
                                                                        <li>All rows are validated before any data is saved to database</li>
                                                                    </ul>
                                                                </div>
                                                            </div>

                                                            <form id="groupsForm" wire:submit.prevent="groupsForm" class="import-form">
                                                                @csrf
                                                                <div class="form-group">
                                                                    <label for="groups_file">Select Excel File</label>
                                                                    <div class="input-group">
                                                                        <div class="custom-file">
                                                                            <input type="file" class="custom-file-input"
                                                                                id="groups_file" accept=".xlsx,.xls,.csv" name="groupsData" required
                                                                                onchange="uploadGroupFile(this)">
                                                                            <label wire:ignore class="custom-file-label" for="groups_file" id="groups_file_label">
                                                                                <span id="groupsFileName">No file chosen</span>
                                                                                <span id="groupsInputSpiner" hidden>
                                                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                                                                                </span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-outline-danger" type="button"
                                                                                    onclick="removeFile('groupsData')"
                                                                                    @if(!$groupsData) disabled @endif>
                                                                                <i class="fas fa-times"></i> Remove
                                                                            </button>
                                                                        </div>

                                                                    </div>
                                                                    <small class="form-text text-muted">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</small>
                                                                    @error('groupsData') <span class="error text-danger">{{ $message }}</span> @enderror

                                                                    {{-- Show file info when selected --}}
                                                                    @if($groupsData)
                                                                        <div class="mt-2 alert alert-info py-2">
                                                                            <small>
                                                                                <i class="fas fa-file-excel mr-1 text-success"></i>
                                                                                Selected: <strong>{{ $groupsData->getClientOriginalName() }}</strong>
                                                                                ({{ number_format(($groupsData->getSize() / 1024) / 1024, 2) }} MiB)
                                                                            </small>
                                                                        </div>
                                                                    @endif

                                                                    {{-- Show loading state separately --}}
                                                                    <div wire:loading wire:target="groupsData" class="mt-2 w-100">
                                                                        <div class="progress" style="height: 4px;">
                                                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                                                                                style="width: 100%">
                                                                            </div>
                                                                        </div>
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-upload mr-1"></i> loading file...
                                                                        </small>
                                                                    </div>
                                                                </div>

                                                                {{-- Progress Bar --}}

                                                                <div wire:loading wire:target="groupsForm" class="mt-3 w-100">
                                                                    <div class="progress progress-sm rounded">
                                                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                                            role="progressbar" style="width: {{ $importProgressGroups }}%">
                                                                            <span class="sr-only">{{ $importProgressGroups }}% Complete</span>
                                                                        </div>
                                                                    </div>
                                                                     <div class="text-center mt-2">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                                                            {{ $importMessageGroups }}
                                                                        </small>
                                                                    </div>

                                                                    <div class="text-center mt-1">
                                                                        <small class="text-muted">
                                                                            Progress: {{ $importProgressGroups }}%
                                                                        </small>
                                                                    </div>
                                                                </div>

                                                                @if($groupsData && !$importProgressGroups)
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <button type="submit" class="btn btn-info"
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="groupsForm">
                                                                        <span wire:loading.remove wire:target="groupsForm">
                                                                            <i class="fas fa-upload mr-1"></i>Start Import
                                                                        </span>
                                                                        <span wire:loading wire:target="groupsForm">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i> Importing...
                                                                        </span>
                                                                    </button>
                                                                </div>
                                                                @endif
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Results Section --}}
                                                @if($groupsImportResult)
                                                    @if($groupsImportResult['success'])
                                                        <div class="alert alert-success mt-3">
                                                            <h6><i class="fas fa-check-circle mr-2"></i>Import Successful!</h6>
                                                            <p class="mb-2">{{ $groupsImportResult['message'] }}</p>
                                                            @if(isset($groupsImportResult['stats']))
                                                                <div class="row text-center mt-2">
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-success">{{ $groupsImportResult['stats']['imported'] }}</div>
                                                                            <small class="text-muted">New Inserted Groups</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-warning">{{ $groupsImportResult['stats']['skipped'] }}</div>
                                                                            <small class="text-muted">Skipped (Existing)</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-primary">{{ $groupsImportResult['stats']['total'] }}</div>
                                                                            <small class="text-muted">Total Processed</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Show skipped rows details --}}
                                                                @if(isset($groupsImportResult['stats']['skipped_rows']) && count($groupsImportResult['stats']['skipped_rows']) > 0)
                                                                    <div class="mt-3">
                                                                        <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="collapse" data-target="#skippedPhases">
                                                                            <i class="fas fa-eye mr-1"></i> Show Skipped Phases Details
                                                                        </button>

                                                                        <div class="collapse mt-2" id="skippedPhases">
                                                                            <div class="card card-body">
                                                                                <h6 class="mb-2">Skipped Phases (Already Exist):</h6>
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-sm table-bordered">
                                                                                        <thead class="text-body">
                                                                                            <tr>
                                                                                                <th>Row</th>
                                                                                                <th>Phase Name</th>
                                                                                                <th>Reason</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="text-black-50">
                                                                                            @foreach($groupsImportResult['stats']['skipped_rows'] as $skipped)
                                                                                            <tr>
                                                                                                <td>{{ $skipped['row'] }}</td>
                                                                                                <td>{{ $skipped['phase'] }}</td>
                                                                                                <td>{{ $skipped['reason'] }}</td>
                                                                                            </tr>
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="alert alert-danger mt-3">
                                                            <h6><i class="fas fa-exclamation-triangle mr-2"></i>
                                                                Import Failed!
                                                            </h6>

                                                            @if($groupsImportResult['total_errors'] > 0)
                                                                <div class="mt-3">
                                                                    <button class="btn btn-sm btn-default btn-outline-warning" type="button" data-toggle="collapse" data-target="#groupvalidationErrors">
                                                                        <i class="fas fa-list mr-1"></i>
                                                                        Show Validation Errors ({{ $groupsImportResult['total_errors'] ?? count($groupsImportResult['errors']) }} errors found)
                                                                    </button>

                                                                    <div class="collapse mt-2" id="groupvalidationErrors">
                                                                        <div class="card card-body">
                                                                            <h6 class="mb-2 text-danger">
                                                                                <i class="fas fa-times-circle mr-1"></i>
                                                                                Validation Errors Summary
                                                                                @if(($groupsImportResult['total_errors'] ?? 0) > 20)
                                                                                    <span class="text-sm ml-2">(Showing first 20 errors)</span>
                                                                                @endif
                                                                            </h6>

                                                                            <div class="table-responsive" style="max-height: 300px;">
                                                                                <table class="table table-sm table-bordered table-hover table-head-fixed text-nowrap">
                                                                                    <thead class="text-body">
                                                                                        <tr>
                                                                                            <th width="15%" class="text-center">Row #</th>
                                                                                            <th width="85%">Error Description</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody class="text-black-50">
                                                                                        @foreach($groupsImportResult['display_errors'] ?? array_slice($groupsImportResult['errors'], 0, 20) as $error)
                                                                                        <tr>
                                                                                            <td class="text-center">
                                                                                                <span class="badge badge-info font-weight-normal">{{ $error['row'] }}</span>
                                                                                            </td>
                                                                                            <td class="small">
                                                                                                <div class="d-flex align-items-start">
                                                                                                    <i class="fas fa-exclamation-triangle text-info mr-2 mt-1"></i>
                                                                                                    <span>{{ $error['message'] ?? $error }}</span>
                                                                                                </div>
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
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Step 3: Group Loans --}}
                                    <div class="time-label">
                                        <span class="bg-success">Step 3</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-hand-holding-usd bg-purple"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> Third Step</span>
                                            <h3 class="timeline-header">
                                                <i class="fas fa-hand-holding-usd mr-2"></i>Import Group Loans
                                                <span class="badge badge-warning ml-2">Requires First and Second Step</span>
                                            </h3>
                                            <div class="timeline-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h6>What are Group Loans?</h6>
                                                        <p>Group loans are the actual loan applications submitted by groups. Each loan is associated with a specific loan phase.</p>

                                                        <div class="alert alert-default-warning small">
                                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                                            <strong>Important:</strong> Groups must be imported before creating loans! Ensure to check on the loan level for every loan phase
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card card-outline card-purple">
                                                            <div class="card-header">
                                                                <h6 class="card-title mb-0">Quick Actions</h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <button type="submit" class="btn btn-info"
                                                                        wire:click.prevent="downloadTemplate('group-loans')"
                                                                        wire:loading.attr="disabled"
                                                                        wire:target="downloadTemplate">
                                                                    <span wire:loading.remove wire:target="downloadTemplate">
                                                                        <i class="fas fa-download mr-1"></i>Download Template
                                                                    </span>
                                                                    <span wire:loading wire:target="downloadTemplate">
                                                                        <i class="fas fa-spinner fa-spin mr-1"></i> processing...
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Import Section --}}
                                                <div id="groupLoansImportSection" class="mt-3">
                                                    <div class="card card-outline card-purple">
                                                        <div class="card-header">
                                                            <h6 class="card-title mb-0">
                                                                <i class="fas fa-upload mr-1"></i>Import Group Loans
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="alert alert-info">
                                                                <h6><i class="fas fa-info-circle mr-2"></i>Template Requirements</h6>
                                                                <p class="mb-1">Your Excel file <strong>must</strong> have these columns:</p>
                                                                <table class="table table-sm table-responsive table-bordered mt-2 bg-white table-hover text-nowrap">
                                                                    <thead class="bg-light">
                                                                        <tr>
                                                                            <th>S/No*</th>
                                                                            <th>Phase*</th>
                                                                            <th>Group*</th>
                                                                            <th>Code*</th>
                                                                            <th>level*</th>
                                                                            <th>loan_status*</th>
                                                                            <th>disbursement_date*</th>
                                                                            <th>cheque_no</th>
                                                                            <th>amount_applied</th>
                                                                            <th>amount_approved*</th>
                                                                            <th>amount_issued*</th>
                                                                            <th>amount_repaid*</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1</td>
                                                                            <td><strong>Phase 1</strong></td>
                                                                            <td><strong>MZAWO WOMEN GROUP</strong></td>
                                                                            <td><strong>001</strong></td>
                                                                            <td><strong>New</strong></td>
                                                                            <td><strong>financed</strong></td>
                                                                            <td><strong>2016-02-02</strong></td>
                                                                            <td>001/0010</td>
                                                                            <td>100,000</td>
                                                                            <td><strong>100,000</strong></td>
                                                                            <td><strong>100,000</strong></td>
                                                                            <td><strong>95,000</strong></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>

                                                                <div class="mt-2">
                                                                    <strong>Important Instructions:</strong>
                                                                    <ul class="mb-0">
                                                                        <li>Fields marked with * are <strong>mandatory</strong></li>
                                                                        <li>Phases must exist in {{ $constituency->constituency_name }} constituency</li>
                                                                        <li>Available Phases:
                                                                            @foreach($constituency->phases as $phase)
                                                                                <strong>{{ $phase->phase }},</strong>
                                                                            @endforeach
                                                                        </li>
                                                                        <li>Level must be: <strong>New, Refinancing, Resubmission or Reallocation</strong></li>
                                                                        <li>Loan status must be: <strong>Financed or Cancelled</strong></li>
                                                                        <li>Ensure to capture correct financial details.</li>
                                                                        <li>All rows are validated before any data is saved to database</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <form id="groupLoansForm" wire:submit.prevent="groupLoansForm" class="import-form">
                                                                @csrf
                                                                <div class="form-group">
                                                                    <label for="group_loans_file">Select Excel File</label>
                                                                    <div class="input-group">
                                                                        <div class="custom-file">
                                                                            <input type="file" class="custom-file-input"
                                                                                id="group_loans_file" accept=".xlsx,.xls,.csv" name="groupLoansData" required
                                                                                onchange="uploadGroupFile(this)">
                                                                            <label wire:ignore class="custom-file-label" for="group_loans_file" id="group_loans_file_label">
                                                                                <span id="groupLoansFileName">No file chosen</span>
                                                                                <span id="groupLoansInputSpiner" hidden>
                                                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                                                                                </span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-outline-danger" type="button"
                                                                                    onclick="removeFile('groupLoansData')"
                                                                                    @if(!$groupLoansData) disabled @endif>
                                                                                <i class="fas fa-times"></i> Remove
                                                                            </button>
                                                                        </div>

                                                                    </div>
                                                                    <small class="form-text text-muted">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</small>
                                                                    @error('groupLoansData') <span class="error text-red">{{ $message }}</span> @enderror
                                                                    @if($groupLoansData)
                                                                        <div class="mt-2 alert alert-info py-2">
                                                                            <small>
                                                                                <i class="fas fa-file-excel mr-1 text-success"></i>
                                                                                Selected: <strong>{{ $groupLoansData->getClientOriginalName() }}</strong>
                                                                                ({{ number_format(($groupLoansData->getSize() / 1024) / 1024, 2) }} MiB)
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                {{-- Progress Bar --}}
                                                                <div wire:loading wire:target="groupLoansForm" class="mt-3 w-100">
                                                                    <div class="progress progress-sm rounded">
                                                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                                            role="progressbar" style="width: {{ $importProgressGroupLoans }}%">
                                                                            <span class="sr-only">{{ $importProgressGroupLoans }}% Complete</span>
                                                                        </div>
                                                                    </div>
                                                                     <div class="text-center mt-2">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                                                            {{ $importMessageGroupLoans }}
                                                                        </small>
                                                                    </div>

                                                                    <div class="text-center mt-1">
                                                                        <small class="text-muted">
                                                                            Progress: {{ $importProgressGroupLoans }}%
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                                @if($groupLoansData && !$importProgressGroupLoans)
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <button type="submit" class="btn btn-info"
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="groupLoansForm">
                                                                        <span wire:loading.remove wire:target="groupLoansForm">
                                                                            <i class="fas fa-upload mr-1"></i>Start Import
                                                                        </span>
                                                                        <span wire:loading wire:target="groupLoansForm">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i> Importing...
                                                                        </span>
                                                                    </button>
                                                                </div>
                                                                @endif
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Results Section --}}
                                                @if($groupLoansImportResult)
                                                    @if($groupLoansImportResult['success'])
                                                        <div class="alert alert-success mt-3">
                                                            <h6><i class="fas fa-check-circle mr-2"></i>Import Successful!</h6>
                                                            <p class="mb-2">{{ $groupLoansImportResult['message'] }}</p>
                                                            @if(isset($groupLoansImportResult['stats']))
                                                                <div class="row text-center mt-2">
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-success">{{ $groupLoansImportResult['stats']['imported'] }}</div>
                                                                            <small class="text-muted">New Inserted Group Loans</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-warning">{{ $groupLoansImportResult['stats']['skipped'] }}</div>
                                                                            <small class="text-muted">Skipped (Existing)</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-primary">{{ $groupLoansImportResult['stats']['total'] }}</div>
                                                                            <small class="text-muted">Total Processed</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Show skipped rows details --}}
                                                                @if(isset($groupLoansImportResult['stats']['skipped_rows']) && count($groupLoansImportResult['stats']['skipped_rows']) > 0)
                                                                    <div class="mt-3">
                                                                        <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="collapse" data-target="#skippedPhases">
                                                                            <i class="fas fa-eye mr-1"></i> Show Skipped Phases Details
                                                                        </button>

                                                                        <div class="collapse mt-2" id="skippedPhases">
                                                                            <div class="card card-body">
                                                                                <h6 class="mb-2">Skipped Phases (Already Exist):</h6>
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-sm table-bordered">
                                                                                        <thead class="text-body">
                                                                                            <tr>
                                                                                                <th>Row</th>
                                                                                                <th>Phase Name</th>
                                                                                                <th>Reason</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="text-black-50">
                                                                                            @foreach($groupLoansImportResult['stats']['skipped_rows'] as $skipped)
                                                                                            <tr>
                                                                                                <td>{{ $skipped['row'] }}</td>
                                                                                                <td>{{ $skipped['phase'] }}</td>
                                                                                                <td>{{ $skipped['reason'] }}</td>
                                                                                            </tr>
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="alert alert-danger mt-3">
                                                            <h6><i class="fas fa-exclamation-triangle mr-2"></i>
                                                                Import Failed!
                                                            </h6>

                                                            @if($groupLoansImportResult['total_errors'] > 0)
                                                                <div class="mt-3">
                                                                    <button class="btn btn-sm btn-default btn-outline-warning" type="button" data-toggle="collapse" data-target="#groupvalidationErrors">
                                                                        <i class="fas fa-list mr-1"></i>
                                                                        Show Validation Errors ({{ $groupLoansImportResult['total_errors'] ?? count($groupLoansImportResult['errors']) }} errors found)
                                                                    </button>

                                                                    <div class="collapse mt-2" id="groupvalidationErrors">
                                                                        <div class="card card-body">
                                                                            <h6 class="mb-2 text-danger">
                                                                                <i class="fas fa-times-circle mr-1"></i>
                                                                                Validation Errors Summary
                                                                                @if(($groupLoansImportResult['total_errors'] ?? 0) > 20)
                                                                                    <span class="text-sm ml-2">(Showing first 20 errors)</span>
                                                                                @endif
                                                                            </h6>

                                                                            <div class="table-responsive" style="max-height: 300px;">
                                                                                <table class="table table-sm table-bordered table-hover table-head-fixed text-nowrap">
                                                                                    <thead class="text-body">
                                                                                        <tr>
                                                                                            <th width="15%" class="text-center">Row #</th>
                                                                                            <th width="85%">Error Description</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody class="text-black-50">
                                                                                        @foreach($groupLoansImportResult['display_errors'] ?? array_slice($groupLoansImportResult['errors'], 0, 20) as $error)
                                                                                        <tr>
                                                                                            <td class="text-center">
                                                                                                <span class="badge badge-info font-weight-normal">{{ $error['row'] }}</span>
                                                                                            </td>
                                                                                            <td class="small">
                                                                                                <div class="d-flex align-items-start">
                                                                                                    <i class="fas fa-exclamation-triangle text-info mr-2 mt-1"></i>
                                                                                                    <span>{{ $error['message'] ?? $error }}</span>
                                                                                                </div>
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
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>


                                    {{-- Step 4 --}}
                                    <div class="time-label">
                                        <span class="bg-success">Step 4</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-friends bg-orange"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> Fourth</span>
                                            <h3 class="timeline-header">Import Members</h3>
                                            <span class="badge badge-warning ml-2">Requires third Step</span>
                                            <div class="timeline-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h6>Members</h6>
                                                        <p>These are the participants of respective groups in every loaning phase</p>

                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card card-outline card-purple">
                                                            <div class="card-header">
                                                                <h6 class="card-title mb-0">Quick Actions</h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <a href="" wire:click.prevent="downloadTemplate('members')" class="mb-2">
                                                                    <i class="fas fa-download mr-1"></i>Download Template
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Import Section --}}
                                                <div id="groupMembersImportSection" class="mt-3">
                                                    <div class="card card-outline card-purple">
                                                        <div class="card-header">
                                                            <h6 class="card-title mb-0">
                                                                <i class="fas fa-upload mr-1"></i>Import Group Members
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="alert alert-info">
                                                                <h6><i class="fas fa-info-circle mr-2"></i>Template Requirements</h6>
                                                                <p class="mb-1">Your Excel file <strong>must</strong> have these columns:</p>
                                                                <table class="table table-sm table-responsive table-bordered mt-2 bg-white table-hover text-nowrap">
                                                                    <thead class="bg-light">
                                                                        <tr>
                                                                            <th>S/No*</th>
                                                                            <th>Phase*</th>
                                                                            <th>Level*</th>
                                                                            <th>Group*</th>
                                                                            <th>Code*</th>
                                                                            <th>Name*</th>
                                                                            <th>Member ID*</th>
                                                                            <th>Role*</th>
                                                                            <th>Number*</th>
                                                                            <th>Gender*</th>
                                                                            <th>Disabled*</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1</td>
                                                                            <td><strong>Phase 1</strong></td>
                                                                            <td><strong>New</strong></td>
                                                                            <td>MZAWO WOMEN GROUP</td>
                                                                            <td><strong>001</strong></td>
                                                                            <td>Defence Shali</td>
                                                                            <td><strong>3931995</strong></td>
                                                                            <td><strong>Chairperson</strong></td>
                                                                            <td>0716183561</td>
                                                                            <td><strong>Female</strong></td>
                                                                            <td><strong>No</strong></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>

                                                                <div class="mt-2">
                                                                    <strong>Important Instructions:</strong>
                                                                    <ul class="mb-0">
                                                                        <li>Fields marked with * are <strong>mandatory</strong></li>
                                                                        <li>Groups must be imported before adding members</li>
                                                                        <li>Group loans must exist for the specified Phase + Level + Group combination</li>
                                                                        <li>Level must be: <strong>New, Refinancing, Resubmission, or Reallocation</strong></li>
                                                                        <li>Role must be: <strong>Chairperson, Secretary, Tresurer, or Member</strong></li>
                                                                        <li>Gender must be: <strong>Male or Female</strong></li>
                                                                        <li>Disabled must be: <strong>Yes or No</strong></li>
                                                                        <li>Member ID must be <strong>unique numeric identifier</strong> (numbers only)</li>
                                                                        <li>Phone number should be in valid format (e.g., 0712345678)</li>
                                                                        <li>Same member can be in multiple groups but only once per group loan</li>
                                                                        <li>All rows are validated before any data is saved to database</li>
                                                                    </ul>
                                                                </div>

                                                                <div class="mt-3 alert alert-default-warning">
                                                                    <h6><i class="fas fa-exclamation-triangle mr-2"></i>Critical Data Integrity Notes</h6>
                                                                    <ul class="mb-0">
                                                                        <li><strong>Member ID is permanent:</strong> Once a member is created with an ID, it cannot be changed</li>
                                                                        <li><strong>Group Loan Verification:</strong> Ensure the Phase + Level + Group combination exists before importing members</li>
                                                                        <li><strong>Role Accuracy:</strong> Each group loan should have only one Chairperson, Secretary, and Tresurer</li>
                                                                        <li><strong>Phone Numbers:</strong> Use the member's primary phone number for communication</li>
                                                                        <li><strong>Disability Status:</strong> Mark "Yes" only for officially recognized PWD members</li>
                                                                    </ul>
                                                                </div>

                                                                <div class="mt-2">
                                                                    <strong>Common Import Errors to Avoid:</strong>
                                                                    <ul class="mb-0">
                                                                        <li>Using non-existent Phase, Group, or Level combinations</li>
                                                                        <li>Duplicate Member IDs within the same group loan</li>
                                                                        <li>Invalid phone number formats</li>
                                                                        <li>Misspelled roles (e.g., "Treasurer" instead of "Tresurer")</li>
                                                                        <li>Inconsistent Group names or codes</li>
                                                                    </ul>
                                                                </div>
                                                            </div>

                                                            <form id="groupMembersForm" wire:submit.prevent="groupMembersForm" class="import-form">
                                                                @csrf
                                                                <div class="form-group">
                                                                    <label for="group_loans_file">Select Excel File</label>

                                                                    <div class="input-group">
                                                                        <div class="custom-file">
                                                                            <input type="file" class="custom-file-input"
                                                                                id="group_members_file" accept=".xlsx,.xls,.csv" name="groupMembersData" required
                                                                                onchange="uploadGroupFile(this)">
                                                                            <label wire:ignore class="custom-file-label" for="group_members_file" id="group_members_file_label">
                                                                                <span id="groupMembersFileName">No file chosen</span>
                                                                                <span id="groupMembersInputSpiner" hidden>
                                                                                    <i class="fas fa-spinner fa-spin mr-1"></i> Uploading...
                                                                                </span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-outline-danger" type="button"
                                                                                    onclick="removeFile('groupMembersData')"
                                                                                    @if(!$groupMembersData) disabled @endif>
                                                                                <i class="fas fa-times"></i> Remove
                                                                            </button>
                                                                        </div>

                                                                    </div>
                                                                    <small class="form-text text-muted">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</small>
                                                                    @error('groupMembersData') <span class="error text-red">{{ $message }}</span> @enderror
                                                                    @if($groupMembersData)
                                                                        <div class="mt-2 alert alert-info py-2">
                                                                            <small>
                                                                                <i class="fas fa-file-excel mr-1 text-success"></i>
                                                                                Selected: <strong>{{ $groupMembersData->getClientOriginalName() }}</strong>
                                                                                ({{ number_format(($groupMembersData->getSize() / 1024) / 1024, 2) }} MiB)
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                {{-- Progress Bar --}}
                                                                <div wire:loading wire:target="groupMembersForm" class="mt-3 w-100">
                                                                    <div class="progress progress-sm rounded">
                                                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                                            role="progressbar" style="width: {{ $importProgressGroupMembers }}%">
                                                                            <span class="sr-only">{{ $importProgressGroupMembers }}% Complete</span>
                                                                        </div>
                                                                    </div>
                                                                     <div class="text-center mt-2">
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                                                            {{ $importMessageGroupMembers }}
                                                                        </small>
                                                                    </div>

                                                                    <div class="text-center mt-1">
                                                                        <small class="text-muted">
                                                                            Progress: {{ $importProgressGroupMembers }}%
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                                @if($groupMembersData)
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <button type="submit" class="btn btn-info"
                                                                            wire:loading.attr="disabled"
                                                                            wire:target="groupMembersForm">
                                                                        <span wire:loading.remove wire:target="groupMembersForm">
                                                                            <i class="fas fa-upload mr-1"></i>Start Import
                                                                        </span>
                                                                        <span wire:loading wire:target="groupMembersForm">
                                                                            <i class="fas fa-spinner fa-spin mr-1"></i> Importing...
                                                                        </span>
                                                                    </button>
                                                                </div>
                                                                @endif
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Results Section --}}
                                                @if($groupMembersImportResult)
                                                    @if($groupMembersImportResult['success'])
                                                        <div class="alert alert-success mt-3">
                                                            <h6><i class="fas fa-check-circle mr-2"></i>Import Successful!</h6>
                                                            <p class="mb-2">{{ $groupMembersImportResult['message'] }}</p>
                                                            @if(isset($groupMembersImportResult['stats']))
                                                                <div class="row text-center mt-2">
                                                                    <div class="col-6">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-success">{{ $groupMembersImportResult['stats']['imported_members'] }}</div>
                                                                            <small class="text-muted">New Inserted Members</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="border rounded p-2 bg-light">
                                                                            <div class="h5 mb-0 text-primary">{{ $groupMembersImportResult['stats']['total_processed'] }}</div>
                                                                            <small class="text-muted">Total Processed</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Show skipped rows details --}}
                                                                {{-- @if(isset($groupMembersImportResult['stats']['skipped_members']))
                                                                    <div class="mt-3">
                                                                        <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="collapse" data-target="#skippedPhases">
                                                                            <i class="fas fa-eye mr-1"></i> Show Skipped Phases Details
                                                                        </button>

                                                                        <div class="collapse mt-2" id="skippedPhases">
                                                                            <div class="card card-body">
                                                                                <h6 class="mb-2">Skipped Phases (Already Exist):</h6>
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-sm table-bordered">
                                                                                        <thead class="text-body">
                                                                                            <tr>
                                                                                                <th>Row</th>
                                                                                                <th>Phase Name</th>
                                                                                                <th>Reason</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="text-black-50">
                                                                                            @foreach($groupMembersImportResult['stats']['skipped_members'] as $skipped)
                                                                                            <tr>
                                                                                                <td>{{ $skipped['row'] }}</td>
                                                                                                <td>{{ $skipped['phase'] }}</td>
                                                                                                <td>{{ $skipped['reason'] }}</td>
                                                                                            </tr>
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif --}}
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="alert alert-danger mt-3">
                                                            <h6><i class="fas fa-exclamation-triangle mr-2"></i>
                                                                Import Failed!
                                                            </h6>

                                                            @if($groupMembersImportResult['total_errors'] > 0)
                                                                <div class="mt-3">
                                                                    <button class="btn btn-sm btn-default btn-outline-warning" type="button" data-toggle="collapse" data-target="#groupvalidationErrors">
                                                                        <i class="fas fa-list mr-1"></i>
                                                                        Show Validation Errors ({{ $groupMembersImportResult['total_errors'] ?? count($groupMembersImportResult['errors']) }} errors found)
                                                                    </button>

                                                                    <div class="collapse mt-2" id="groupvalidationErrors">
                                                                        <div class="card card-body">
                                                                            <h6 class="mb-2 text-danger">
                                                                                <i class="fas fa-times-circle mr-1"></i>
                                                                                Validation Errors Summary
                                                                                @if(($groupMembersImportResult['total_errors'] ?? 0) > 20)
                                                                                    <span class="text-sm ml-2">(Showing first 20 errors)</span>
                                                                                @endif
                                                                            </h6>

                                                                            <div class="table-responsive" style="max-height: 300px;">
                                                                                <table class="table table-sm table-bordered table-hover table-head-fixed text-nowrap">
                                                                                    <thead class="text-body">
                                                                                        <tr>
                                                                                            <th width="15%" class="text-center">Row #</th>
                                                                                            <th width="85%">Error Description</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody class="text-black-50">
                                                                                        @foreach($groupMembersImportResult['display_errors'] ?? array_slice($groupMembersImportResult['errors'], 0, 20) as $error)
                                                                                        <tr>
                                                                                            <td class="text-center">
                                                                                                <span class="badge badge-info font-weight-normal">{{ $error['row'] }}</span>
                                                                                            </td>
                                                                                            <td class="small">
                                                                                                <div class="d-flex align-items-start">
                                                                                                    <i class="fas fa-exclamation-triangle text-info mr-2 mt-1"></i>
                                                                                                    <span>{{ $error['message'] ?? $error }}</span>
                                                                                                </div>
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
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Import Section --}}



    </div>

</section>

@push('scripts')
    <!-- bs-custom-file-input -->

<script>
    document.addEventListener('livewire:init', function () {

        Livewire.on('import-completed', (e) => {
            document.getElementById(e.form).reset();
            switch (e.form){
                case 'loanPhasesForm':
                    document.getElementById('phasesFileName').textContent = 'No file chosen';
                    break;
                case 'groupsForm':
                    document.getElementById('groupsFileName').textContent = 'No file chosen';
                    break;
                case 'groupLoansForm':
                    document.getElementById('groupLoansFileName').textContent = 'No file chosen';
                    break;
                case 'groupMembersForm':
                    document.getElementById('groupMembersFileName').textContent = 'No file chosen';
                    break;
            }
            console.log('Import completed');

        });

        // Also poll when the component is active
        Livewire.on('import-started', () => {
            console.log('Groups import started');
        });

        let pollingInterval = null;

        // Start polling when import begins
        Livewire.on('start-groups-polling', () => {
            console.log('Starting groups import polling');

            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            pollingInterval = setInterval(() => {
                Livewire.dispatch('poll-groups-progress');
            }, 500);
        });

        Livewire.on('change', (change) => {
            console.log(change)
        })

        // Stop polling when import completes
        Livewire.on('stop-groups-polling', () => {
            console.log('Stopping groups import polling');
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        });

        // Clean up
        Livewire.hook('component.removed', (component) => {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        });

        // // Listen for when file is removed/reset
        // Livewire.on('file-reset', () => {
        //     const label = document.getElementById('groups_file_label');
        //     if (label) {
        //         label.textContent = 'No file chosen';
        //     }
        // });
    });

    // function updateFileName(input) {
    //     console.log(input.id)
    //     switch (input.id) {
    //         case 'groups_file':
    //             document.getElementById('groupsFileName').style.display = 'none';
    //             document.getElementById('groupsInputSpiner').style.display = 'inline';

    //             console.log('groups input');
    //             break;
    //     }

    //     const label = input.nextElementSibling;
    //     const fileName = input.files[0]?.name;
    //     label.textContent = fileName;
    // }
    // function updateFileName(input) {
    //     const fileName = input.files[0]?.name || 'No file chosen';
    //     document.getElementById('groupsFileName').textContent = fileName;

    //     // 🔥 Update Livewire property manually (without wire:model)
    //     if (fileName !== 'No file chosen') {
    //         //@this.set('groupsData', input.files[0]);
    //         // @this.set('groupsFileName', fileName);
    //         Livewire.dispatch('groupUp')
    //     }
    // }

    function inputChange(inputName, fileNameId, spinnerId, file) {
        const fileNameSpan = document.getElementById(fileNameId);
        const spinner = document.getElementById(spinnerId);

        if (!file) {
            fileNameSpan.textContent = 'No file choosen.';
            @this.set(inputName, null);
            return;
        }

        fileNameSpan.textContent = file.name;
        fileNameSpan.hidden = true;
        spinner.hidden = false;

        @this.upload(inputName, file,
            (uploadedFilename) => {
                // Success
                spinner.hidden = true;
                fileNameSpan.hidden = false;
            },
            (error) => {
                // Error
                spinner.hidden = true;
                fileNameSpan.hidden = false;
            }
        );

    }

    function uploadGroupFile(input) {
        const file = input.files[0];

        switch (input.name){
            case 'groupsData':
                @this.set('groupsImportResult', null)
                inputChange('groupsData', 'groupsFileName', 'groupsInputSpiner', file)
                break;
            case 'loanPhasesData':
                @this.set('importResult', null)
                inputChange('loanPhasesData', 'phasesFileName', 'phasesInputSpiner', file);
                break;
            case 'groupLoansData':
                @this.set('groupLoansImportResult', null)
                inputChange('groupLoansData', 'groupLoansFileName', 'groupLoansInputSpiner', file);
                break;
            case 'groupMembersData':
                @this.set('groupMembersImportResult', null)
                inputChange('groupMembersData', 'groupMembersFileName', 'groupMembersInputSpiner', file);
                break;

        }
    }

    function removeFile(fileName) {
        switch (fileName) {
            case 'groupsData':
                @this.set('groupsImportResult', null)
                @this.set(fileName, null);
                document.getElementById('groupsFileName').textContent = 'No file chosen';
                document.getElementById('groupsForm').reset();
                break;
            case 'loanPhasesData':
                @this.set(fileName, null);
                @this.set('importResult', null)
                document.getElementById('phasesFileName').textContent = 'No file chosen';
                document.getElementById('loanPhasesForm').reset();
                break;
            case 'groupLoansData':
                @this.set(fileName, null);
                @this.set('groupLoansImportResult', null)
                document.getElementById('groupLoansFileName').textContent = 'No file chosen';
                document.getElementById('groupLoansForm').reset();
                break;
            case 'groupMembersData':
                @this.set(fileName, null);
                @this.set('groupMembersImportResult', null)
                document.getElementById('groupMembersFileName').textContent = 'No file chosen';
                document.getElementById('groupMembersForm').reset();
                break;

        }
    }
</script>

@endpush

