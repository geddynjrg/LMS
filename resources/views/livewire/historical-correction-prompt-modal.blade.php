<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background: rgba(15, 23, 42, 0.45); z-index: 2000;">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        HQ correction prompt
                    </h5>
                    <button type="button" class="close" wire:click="dismissForNow" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Headquarters asked you to correct or validate the items below.
                        Group-loan prompts clear automatically after you save the corrected fields.
                        Allocation-profile prompts clear when you mark them reviewed.
                    </p>

                    @foreach($requests as $request)
                        <div class="border rounded p-3 mb-3" wire:key="corr-{{ $request['id'] }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="font-weight-bold">
                                        @if($request['type'] === 'allocation_profile')
                                            Validate constituency allocation profile
                                        @else
                                            {{ $request['group'] ?? 'Historical group loan' }}
                                            @if(!empty($request['phase']))
                                                <span class="text-muted font-weight-normal">({{ $request['phase'] }})</span>
                                            @endif
                                        @endif
                                    </div>
                                    @if(!empty($request['requested_by']))
                                        <div class="small text-muted">Requested by {{ $request['requested_by'] }}</div>
                                    @endif
                                </div>
                            </div>

                            <ul class="mb-2 mt-2 pl-3">
                                @foreach($request['fields'] as $label)
                                    <li class="small">{{ $label }}</li>
                                @endforeach
                            </ul>

                            @if(!empty($request['message']))
                                <p class="small mb-2"><strong>HQ note:</strong> {{ $request['message'] }}</p>
                            @endif

                            <div class="d-flex flex-wrap">
                                @if(!empty($request['url']))
                                    <a href="{{ $request['url'] }}" class="btn btn-sm btn-primary mr-2 mb-1">
                                        <i class="fas fa-external-link-alt mr-1"></i> Open
                                    </a>
                                @endif

                                @if($request['type'] === 'allocation_profile')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-success mb-1"
                                        wire:click="acknowledgeAllocation({{ $request['id'] }})"
                                    >
                                        Mark reviewed
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click="dismissForNow">
                        Continue and correct later
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
