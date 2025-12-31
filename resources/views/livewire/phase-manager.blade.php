{{-- resources/views/livewire/phase-manager.blade.php --}}
<div>
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h4>Phase Management - {{ $constituency->constituency_name }}</h4>
            <p class="text-muted">Create and manage review phases for committee meetings</p>
        </div>
        <div class="col-md-4 text-right">
            <button wire:click="$set('showCreateModal', true)" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Phase
            </button>
        </div>
    </div>

    <!-- Phase List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Review Phases</h5>
        </div>
        <div class="card-body">
            @if($phases->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Phase</th>
                                <th>Applications</th>
                                <th>Review Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($phases as $phase)
                            <tr>
                                <td>
                                    <strong>{{ $phase->phase_number }}</strong><br>
                                    <small class="text-muted">{{ $phase->phase_name }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $phase->phase_applications_count }}</span>
                                </td>
                                <td>{{ $phase->review_date->format('M d, Y') }}</td>
                                <td>
                                    <strong>KES {{ number_format($phase->total_amount_applied) }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $phase->getStatusBadgeClass() }}">
                                        {{ str_replace('_', ' ', $phase->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('phase.details', [$constituency->slug, $phase->id]) }}"
                                           class="btn btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        @if($phase->canAddApplications())
                                        <button wire:click="showAddAppsModal = true; selectedPhase = {{ $phase->id }}"
                                                class="btn btn-warning">
                                            <i class="fas fa-plus"></i> Add Apps
                                        </button>
                                        @endif
                                        @if($phase->status === 'approved_by_committee')
                                        <button wire:click="submitToHq({{ $phase->id }})"
                                                class="btn btn-success">
                                            <i class="fas fa-paper-plane"></i> Submit to HQ
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $phases->links() }}
            @else
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No phases created yet</h5>
                    <p class="text-muted">Create your first phase to start the review process</p>
                    <button wire:click="$set('showCreateModal', true)" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Phase
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Create Phase Modal -->
    @if($showCreateModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showCreateModal', false)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Review Phase</h5>
                    <button type="button" class="close" wire:click="$set('showCreateModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <form wire:submit.prevent="createPhase">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Phase Name *</label>
                            <input type="text" wire:model="phaseName" class="form-control"
                                   placeholder="e.g., Q1 2024 Review, Emergency Meeting" required>
                            @error('phaseName') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Review Date *</label>
                            <input type="date" wire:model="reviewDate" class="form-control" required>
                            @error('reviewDate') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Review Venue (Optional)</label>
                            <input type="text" wire:model="reviewVenue" class="form-control"
                                   placeholder="e.g., Constituency Office, Community Hall">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCreateModal', false)">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Phase
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Add Applications Modal -->
    @if($showAddAppsModal && $selectedPhase)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showAddAppsModal', false)">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Applications to Phase</h5>
                    <button type="button" class="close" wire:click="$set('showAddAppsModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($pendingApplications->count() > 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Select applications to add to the review phase. Applications already in other phases are not shown.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" wire:model="selectAll">
                                        </th>
                                        <th>Application ID</th>
                                        <th>Group Name</th>
                                        <th>Amount</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingApplications as $app)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   wire:model="selectedApplications"
                                                   value="{{ $app->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $app->application_id }}</strong>
                                        </td>
                                        <td>{{ $app->group_name }}</td>
                                        <td>
                                            <strong class="text-success">
                                                KES {{ number_format($app->loan_amount) }}
                                            </strong>
                                        </td>
                                        <td>{{ $app->created_at->format('M d, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">All applications are assigned!</h5>
                            <p class="text-muted">No pending applications available to add to this phase.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showAddAppsModal', false)">
                        Cancel
                    </button>
                    @if($pendingApplications->count() > 0)
                    <button type="button" class="btn btn-primary"
                            wire:click="addApplicationsToPhase({{ $selectedPhase }})"
                            wire:loading.attr="disabled"
                            {{ empty($selectedApplications) ? 'disabled' : '' }}>
                        <span wire:loading.remove>Add Selected ({{ count($selectedApplications) }})</span>
                        <span wire:loading>Adding...</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Upload Document Modal -->
    @if($showUploadDocModal && $selectedPhase)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showUploadDocModal', false)">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Phase Document</h5>
                    <button type="button" class="close" wire:click="$set('showUploadDocModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <form wire:submit.prevent="uploadDocument">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Document Type *</label>
                            <select wire:model="documentType" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="committee_minutes">Committee Minutes</option>
                                <option value="credit_reports">Credit Reports</option>
                                <option value="supporting_docs">Supporting Documents</option>
                                <option value="additional_notes">Additional Notes</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Document File *</label>
                            <input type="file" wire:model="documentFile" class="form-control" required>
                            @error('documentFile') <span class="text-danger">{{ $message }}</span> @enderror
                            @if($documentFile)
                                <small class="text-muted">
                                    Selected: {{ $documentFile->getClientOriginalName() }}
                                    ({{ number_format($documentFile->getSize() / 1024, 2) }} KB)
                                </small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Description (Optional)</label>
                            <textarea wire:model="documentDescription" class="form-control"
                                      rows="3" placeholder="Brief description of the document..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showUploadDocModal', false)">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="fas fa-upload"></i> Upload Document</span>
                            <span wire:loading>Uploading...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Flash Messages -->
    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
</div>

@push('styles')
<style>
    .modal-backdrop {
        opacity: 0.5 !important;
    }
    .table th {
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }
    .badge {
        font-size: 0.8em;
    }
</style>
@endpush
