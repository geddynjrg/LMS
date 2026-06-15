<div class="card border-0 animate-on-load" x-data="{ docSearch: '' }">
    <div class="card-header bg-white border-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h6 class="mb-0 font-weight-bold text-dark text-uppercase" style="letter-spacing: 1px;">
                <i class="fas fa-file-invoice mr-2 text-primary"></i> Documents & Attachments
            </h6>
            <small class="text-muted">{{ count($this->processedFiles) }} files uploaded for this application</small>
        </div>

        <div class="mt-2 mt-md-0" style="max-width: 250px;">
            <div class="input-group input-group-sm">
                <input type="text" x-model="docSearch" class="form-control border-right-0" placeholder="Filter files...">
                <div class="input-group-append">
                    <span class="input-group-text bg-white border-left-0"><i class="fas fa-search text-muted"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            @forelse($this->processedFiles as $file)
                <div class="col-12 col-md-6 col-lg-4 mb-3"
                     x-show="docSearch === '' || '{{ strtolower($file['field_name']) }}'.includes(docSearch.toLowerCase())"
                     x-transition>
                    <div class="document-card border rounded p-3 h-100 transition-all card-hover bg-white">
                        <div class="d-flex align-items-start mb-3">
                            <div class="avatar-soft-{{ str_contains($file['type'], 'pdf') ? 'danger' : (str_contains($file['type'], 'image') ? 'success' : 'primary') }} rounded mr-3 d-flex align-items-center justify-content-center"
                                 style="width: 45px; height: 45px; flex-shrink: 0;">
                                <i class="fas {{ $file['type'] }} fa-lg"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h6 class="text-dark font-weight-bold mb-0 text-truncate" title="{{ $file['field_name'] }}">
                                    {{ $file['field_name'] }}
                                </h6>
                                <p class="text-muted small mb-0 text-truncate">{{ $file['size'] }}</p>
                            </div>
                        </div>

                        <div class="file-info mb-3">
                            <small class="d-block text-muted text-truncate">
                                <i class="fas fa-paperclip mr-1"></i> {{ $file['original_filename'] }}
                            </small>
                        </div>

                        <div class="d-flex justify-content-between">
                            @if($file['view_url'])
                                <a href="{{ $file['view_url'] }}" target="_blank" class="btn btn-xs btn-outline-primary pill px-3">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            @endif

                            @if($file['download_url'])
                                <a href="{{ $file['download_url'] }}" class="btn btn-xs btn-soft-success pill px-3">
                                    <i class="fas fa-download mr-1"></i> Save
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="avatar-soft-secondary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <i class="fas fa-folder-open fa-2x"></i>
                    </div>
                    <h6 class="font-weight-bold">No Documents Found</h6>
                    <p class="text-muted small">This application doesn't have any attached files at this stage.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

