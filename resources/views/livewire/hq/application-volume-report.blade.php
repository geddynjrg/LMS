<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h3 class="font-weight-bold mb-1">{{ $pageTitle }}</h3>
                <p class="text-muted mb-0">
                    View application volumes by constituency, then preview or download parameterized loan-return reports.
                </p>
            </div>
            @hasanyrole('hq_officer|admin|super-admin')
                <a href="{{ route('hq.reports.index') }}" class="btn btn-sm btn-outline-secondary">National Reports</a>
            @endhasanyrole
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                @include('livewire.partials.application-volume-ranking', ['title' => $pageTitle])
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 font-weight-bold">
                    <i class="fas fa-file-export mr-2 text-success"></i>
                    Preview / download report
                </h5>
                <p class="text-muted small mb-0 mt-1">
                    Choose a report type and optional filters. Preview on screen, then download Excel when ready.
                </p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold">Report type</label>
                        <select wire:model.live="reportType" class="form-control">
                            @foreach($reportTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($scopeConstituencyCode === '')
                        <div class="col-md-4 mb-3">
                            <label class="small font-weight-bold">County (optional)</label>
                            <select wire:model.live="countyFilter" class="form-control">
                                <option value="">All counties</option>
                                @foreach($counties as $county)
                                    <option value="{{ $county->county_code }}">{{ $county->county_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small font-weight-bold">Constituency (optional)</label>
                            <select wire:model.live="constituencyFilter" class="form-control">
                                <option value="">All constituencies</option>
                                @foreach($constituencies as $constituency)
                                    <option value="{{ $constituency->constituency_code }}">{{ $constituency->constituency_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(in_array($reportType, ['phases', 'groups', 'members'], true))
                        <div class="col-md-4 mb-3">
                            <label class="small font-weight-bold">Phase {{ $reportType === 'groups' || $reportType === 'members' ? '(recommended)' : '(optional)' }}</label>
                            <select wire:model.live="phaseFilter" class="form-control">
                                <option value="">All phases</option>
                                @foreach($phases as $phase)
                                    <option value="{{ $phase->id }}">{{ $phase->phase }} ({{ $phase->status }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($reportType === 'members')
                        <div class="col-md-4 mb-3">
                            <label class="small font-weight-bold">Group loan ID (optional)</label>
                            <input type="number"
                                   wire:model.live.debounce.400ms="groupLoanFilter"
                                   class="form-control"
                                   placeholder="e.g. 8907"
                                   min="1">
                        </div>
                    @endif
                </div>

                @if(session('application_volume_error'))
                    <div class="alert alert-warning">{{ session('application_volume_error') }}</div>
                @endif
                @if($previewError)
                    <div class="alert alert-warning">{{ $previewError }}</div>
                @endif

                @php $exportParams = $this->exportFormParams(); @endphp
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <button type="button"
                            class="btn btn-success"
                            wire:click="previewReport"
                            wire:loading.attr="disabled"
                            wire:target="previewReport">
                        <span wire:loading.remove wire:target="previewReport">
                            <i class="fas fa-eye mr-1"></i> Preview table
                        </span>
                        <span wire:loading wire:target="previewReport">Loading preview…</span>
                    </button>

                    <form method="POST" action="{{ route('reports.application-volume.preview-pdf') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="type" value="{{ $exportParams['type'] }}">
                        <input type="hidden" name="county_code" value="{{ $exportParams['county_code'] }}">
                        <input type="hidden" name="constituency_code" value="{{ $exportParams['constituency_code'] }}">
                        <input type="hidden" name="phase_id" value="{{ $exportParams['phase_id'] }}">
                        <input type="hidden" name="group_loan_id" value="{{ $exportParams['group_loan_id'] }}">
                        <button type="submit"
                                class="btn btn-outline-success"
                                @if(! $libreOfficeAvailable) disabled @endif
                                title="{{ $libreOfficeAvailable ? 'Convert with LibreOffice and preview as PDF' : 'LibreOffice is not installed on the server' }}">
                            <i class="fas fa-file-pdf mr-1"></i> Preview PDF (LibreOffice)
                        </button>
                    </form>

                    <form method="POST" action="{{ route('reports.application-volume.download') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="type" value="{{ $exportParams['type'] }}">
                        <input type="hidden" name="county_code" value="{{ $exportParams['county_code'] }}">
                        <input type="hidden" name="constituency_code" value="{{ $exportParams['constituency_code'] }}">
                        <input type="hidden" name="phase_id" value="{{ $exportParams['phase_id'] }}">
                        <input type="hidden" name="group_loan_id" value="{{ $exportParams['group_loan_id'] }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-download mr-1"></i> Download Excel
                        </button>
                    </form>
                </div>
                @unless($libreOfficeAvailable)
                    <p class="text-muted small mt-2 mb-0">
                        LibreOffice is not available on this server, so PDF preview is disabled. Table preview and Excel download still work.
                    </p>
                @endunless

                
                @if($previewPdfToken)
                    <hr>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0 font-weight-bold">
                                <i class="fas fa-file-pdf text-danger mr-1"></i>
                                {{ $previewPdfTitle }} (LibreOffice PDF)
                            </h6>
                            <small class="text-muted">{{ number_format($previewPdfTotal) }} row(s) converted for preview</small>
                        </div>
                        <div class="d-flex flex-wrap" style="gap: 8px;">
                            <a class="btn btn-sm btn-outline-secondary"
                               target="_blank"
                               href="{{ route('reports.application-volume.preview-file', ['token' => $previewPdfToken, 'format' => 'pdf']) }}">
                                Open PDF in new tab
                            </a>
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ route('reports.application-volume.preview-file', ['token' => $previewPdfToken, 'format' => 'xlsx', 'download' => 1]) }}">
                                Download source Excel
                            </a>
                        </div>
                    </div>
                    <div class="border rounded bg-light overflow-hidden" style="height: 70vh;">
                        <iframe
                            title="LibreOffice PDF preview"
                            src="{{ route('reports.application-volume.preview-file', ['token' => $previewPdfToken, 'format' => 'pdf']) }}"
                            style="width: 100%; height: 100%; border: 0;"></iframe>
                    </div>
                @endif

                @if($hasPreview)
                    <hr>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0 font-weight-bold">{{ $previewTitle }}</h6>
                            <small class="text-muted">
                                {{ number_format($previewTotal) }} row(s)
                                @if($previewTruncated)
                                    · preview shows first {{ number_format(count($previewRows)) }} (download for full file)
                                @endif
                            </small>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 55vh;">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="bg-light" style="position: sticky; top: 0;">
                                <tr>
                                    @foreach($previewHeadings as $heading)
                                        <th>{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewRows as $row)
                                    <tr>
                                        @foreach($row as $cell)
                                            <td>{{ is_float($cell) ? number_format($cell) : $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
