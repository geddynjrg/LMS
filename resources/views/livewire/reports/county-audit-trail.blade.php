<div>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="small text-muted font-weight-bold text-uppercase">Constituency</label>
                    <select wire:model.live="filterConstituency" class="form-control form-control-sm">
                        <option value="">All constituencies</option>
                        @foreach($constituencies as $constituency)
                            <option value="{{ $constituency->constituency_code }}">
                                {{ ucwords(strtolower($constituency->constituency_name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="small text-muted font-weight-bold text-uppercase">Action type</label>
                    <select wire:model.live="filterLogName" class="form-control form-control-sm">
                        <option value="">All actions</option>
                        <option value="phase_audit">Phase edits / deletes</option>
                        <option value="group_audit">Group edits / deletes</option>
                        <option value="application_audit">Application deletes</option>
                        <option value="restore_audit">Restores</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="small text-muted font-weight-bold text-uppercase">Officer</label>
                    <select wire:model.live="filterCauserId" class="form-control form-control-sm">
                        <option value="">All officers</option>
                        @foreach($officers as $officer)
                            <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="small text-muted font-weight-bold text-uppercase">Search</label>
                    <input type="search"
                           wire:model.live.debounce.400ms="search"
                           class="form-control form-control-sm"
                           placeholder="Description, event, officer...">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="small text-muted font-weight-bold text-uppercase">From date</label>
                    <input type="date" wire:model.live="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="small text-muted font-weight-bold text-uppercase">To date</label>
                    <input type="date" wire:model.live="filterDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="button" wire:click="clearFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eraser mr-1"></i> Clear filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @include('livewire.users.partials._activity_log', [
                'showConstituency' => true,
                'constituencyNameMap' => $constituencyNameMap,
            ])
        </div>
    </div>
</div>
