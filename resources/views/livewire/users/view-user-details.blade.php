<div>
    @if($showModal && $user)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg shadow-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-circle mr-2"></i> User Profile & Activities</h5>
                    <button type="button" class="close text-white" wire:click="closeModal"><span>&times;</span></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <span class="avatar-lg bg-soft-primary text-primary rounded-circle p-4 h1">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <h5 class="font-weight-bold mb-1">{{ $user->name }}</h5>
                                    <span class="badge badge-pill badge-primary-light text-primary mb-3">
                                        {{ strtoupper($user->getRoleNames()->first()) }}
                                    </span>
                                    <hr>
                                    <div class="text-left small">
                                        <p class="mb-1"><strong>Email:</strong> {{ $user->email }}</p>
                                        <p class="mb-1"><strong>Phone:</strong> +{{ $user->phone_number }}</p>
                                        <p class="mb-1"><strong>ID Number:</strong> {{ $user->id_number }}</p>
                                        <p class="mb-0"><strong>Joined:</strong> {{ $user->created_at->format('d M Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <h6 class="font-weight-bold mb-3"><i class="fas fa-history mr-1 text-muted"></i> Recent Activity</h6>
                            <div class="timeline-small">
                                @forelse($user->assessments as $activity)
                                    <div class="item border-left pl-3 mb-3" style="border-width: 2px !important;">
                                        <small class="text-muted d-block">{{ $activity->created_at->diffForHumans() }}</small>
                                        <p class="mb-0 text-dark">
                                            Created assessment for <strong>{{ $activity->group_name }}</strong>
                                            <span class="badge badge-light border">{{ $activity->constituency_name }}</span>
                                        </p>
                                    </div>
                                @empty
                                    <div class="text-center py-4 bg-white rounded border">
                                        <i class="fas fa-ghost fa-2x text-light mb-2"></i>
                                        <p class="text-muted small">No activities recorded yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary px-4" wire:click="closeModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
