<div>
    @if($isOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);" wire:click.self="$set('isOpen', false)">
        <div class="modal-dialog modal-lg modal-dialog-scrollable shadow-sm">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white d-flex flex-column align-items-start">
                    <div class="d-flex justify-content-between w-100">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-plus-circle mr-2"></i>Add Applications to {{ ucwords($phaseName) }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="$set('isOpen', false)">
                            <span>&times;</span>
                        </button>
                    </div>
                    <small class="text-white-50 mt-1">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        Filtering by Constituency: <strong>{{ $constituency_code }}</strong>
                    </small>
                </div>

                <div class="px-4 pt-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-left-0" placeholder="Search by ID or Group Name...">
                    </div>
                </div>

                <div class="modal-body">
                    @if($pendingApplications->count() > 0)
                        <div class="table-responsive" style="max-height: 60vh;">
                            <table class="table table-hover table-align-middle">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th width="40"><input type="checkbox" wire:model.live="selectAll"></th>
                                        <th>Application ID</th>
                                        <th>Group Name</th>
                                        <th>Amount</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingApplications as $app)
                                        <tr wire:key="app-{{ $app->id }}"
                                            wire:click="toggleApplication('{{ $app->id }}')"
                                            style="cursor: pointer;"
                                            class="{{ in_array($app->id, $selectedApplications) ? 'table-primary' : '' }}">

                                            <td wire:click.stop>
                                                <input type="checkbox"
                                                    wire:model.live="selectedApplications"
                                                    value="{{ $app->id }}">
                                            </td>

                                            <td><span class="badge badge-light text-primary border">{{ $app->application_id }}</span></td>
                                            <td class="font-weight-bold">{{ $app->group_name }}</td>
                                            <td><span class="text-success font-weight-bold">KES {{ number_format($app->loan_amount) }}</span></td>
                                            <td><small class="text-muted">{{ $app->created_at->diffForHumans() }}</small></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-5 text-center">
                                                <div class="d-flex flex-column align-items-center">
                                                    <img src="{{ asset('dist/img/empty-folder.svg') }}" style="width: 120px;" class="mb-3 opacity-50">
                                                    <h5 class="text-muted">No Applications Found</h5>
                                                    @if($search)
                                                        <p class="text-muted small">No results matching "<strong>{{ $search }}</strong>" in constituency <strong>{{ $constituency_code }}</strong>.</p>
                                                        <button wire:click="$set('search', '')" class="btn btn-sm btn-link">Clear Search</button>
                                                    @else
                                                        <p class="text-muted small">All applications for this area have already been assigned to phases.</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing {{ $pendingApplications->firstItem() }} to {{ $pendingApplications->lastItem() }} of {{ $pendingApplications->total() }}
                            </div>
                            {{ $pendingApplications->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-light mb-3"></i>
                            <h5 class="text-muted">No pending applications found.</h5>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" wire:click="$set('isOpen', false)">Close</button>
                    <button type="button" class="btn btn-primary px-4"
                            wire:click="addApplicationsToPhase"
                            wire:loading.attr="disabled"
                            {{ empty($selectedApplications) ? 'disabled' : '' }}>
                        <span wire:loading.remove>Add Selected ({{ count($selectedApplications) }})</span>
                        <span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i> Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@push('styles')
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        tr.table-primary:hover {
            background-color: rgba(0, 123, 255, 0.2) !important;
        }
    </style>
@endpush
