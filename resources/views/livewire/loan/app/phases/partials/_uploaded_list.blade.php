<div class="mt-4">
    @if(is_null($phase->documents) > 0)
        <h6 class="text-xs font-weight-bold text-uppercase text-muted mb-2">Attached Documents (2)</h6>
    @endif

    <ul class="list-group list-group-flush border-top">
        @forelse($phase->phaseApplications as $doc)
            <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent">
                <div class="d-flex align-items-center" style="overflow: hidden;">
                    <div class="mr-3 text-center" style="width: 32px;">
                        {{-- Dynamic Icon based on file extension --}}
                        @if(Str::endsWith(strtolower($doc->file_name), '.pdf'))
                            <i class="fas fa-file-pdf text-danger fa-2x"></i>
                        @elseif(Str::contains(strtolower($doc->file_name), ['jpg', 'jpeg', 'png']))
                            <i class="fas fa-file-image text-info fa-2x"></i>
                        @elseif(Str::contains(strtolower($doc->file_name), ['xls', 'xlsx', 'csv']))
                            <i class="fas fa-file-excel text-success fa-2x"></i>
                        @else
                            <i class="fas fa-file-alt text-secondary fa-2x"></i>
                        @endif
                    </div>

                    <div style="min-width: 0;">
                        <span class="d-block text-sm font-weight-bold text-truncate text-dark" style="max-width: 180px;" title="{{ $doc->file_name }}">
                            {{ $doc->file_name }}
                        </span>
                        <div class="text-xs text-muted">
                            {{ number_format($doc->file_size / 1024, 1) }} KB
                            <span class="mx-1">&bull;</span>
                            {{ $doc->created_at->format('M d') }}
                            <span class="mx-1">&bull;</span>
                            {{ $doc->uploader->name ?? 'Officer' }}
                        </div>
                    </div>
                </div>

                <div class="ml-2">
                    <div class="btn-group shadow-sm">
                        {{-- View / Download Button --}}
                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-xs btn-default" title="View Document">
                            <i class="fas fa-eye text-primary"></i>
                        </a>

                        {{-- Delete Button (Only visible in Draft mode) --}}
                        @if($phase->status === 'draft')
                            <button wire:click="deleteDocument({{ $doc->id }})"
                                    wire:confirm="Are you sure you want to permanently delete '{{ $doc->file_name }}'?"
                                    class="btn btn-xs btn-default"
                                    title="Remove File">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </li>
        @empty
            {{-- Empty State --}}
            <li class="list-group-item text-center py-4 bg-light rounded mt-2 border-0">
                <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                    <i class="fas fa-folder-open fa-2x mb-2 opacity-50"></i>
                    <h6 class="mb-1 text-sm font-weight-bold">No Evidence Attached</h6>
                    <p class="text-xs mb-0 px-3">
                        Please upload the signed <b>Vetting Minutes</b> and <b>Transfer Request</b> to proceed.
                    </p>
                </div>
            </li>
        @endforelse
    </ul>
</div>
