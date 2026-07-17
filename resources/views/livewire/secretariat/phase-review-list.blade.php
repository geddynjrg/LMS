<div>
    @if(session('secretariat_returned'))
        @php $returned = session('secretariat_returned'); @endphp
        <div class="alert alert-warning alert-dismissible shadow-sm">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>Phase returned to HQ.</strong>
            {{ $returned['phase_label'] ?? 'Phase' }} ({{ $returned['constituency'] ?? '' }}) has been sent back to the HQ officer for review.
        </div>
    @endif

    @if(session('secretariat_completed'))
        @php $completed = session('secretariat_completed'); @endphp
        <div class="alert alert-success alert-dismissible shadow-sm">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h5 class="alert-heading mb-1">
                <i class="fas fa-check-circle mr-1"></i> Phase authorisations complete
            </h5>
            <p class="mb-2">
                <strong>{{ $completed['constituency'] ?? '' }}</strong>
                · {{ $completed['phase_label'] ?? '' }}
                @if(!empty($completed['phase_ref']))
                    <span class="text-muted">({{ $completed['phase_ref'] }})</span>
                @endif
            </p>
            <div class="d-flex flex-wrap gap-2">
                @if(!empty($completed['next_phase_url']))
                    <a href="{{ $completed['next_phase_url'] }}" class="btn btn-sm btn-success mr-2 mb-1">
                        <i class="fas fa-arrow-right mr-1"></i> Review next: {{ $completed['next_phase_label'] }}
                    </a>
                @endif
                <span class="text-muted small align-self-center">Pick another phase from the pending list below.</span>
            </div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3 col-sm-6">
            <button type="button" wire:click="setFilter('pending')" class="info-box shadow-sm w-100 text-left border-0 p-0 {{ $filter === 'pending' ? 'border border-warning' : '' }}" style="background: transparent; cursor: pointer;">
                <span class="info-box-icon bg-warning"><i class="fas fa-hourglass-half"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending authorizations</span>
                    <span class="info-box-number">{{ $stats['pending_phases'] }}</span>
                    <small class="text-muted">Phases awaiting your action</small>
                </div>
            </button>
        </div>
        <div class="col-md-3 col-sm-6">
            <button type="button" wire:click="setFilter('pending_training')" class="info-box shadow-sm w-100 text-left border-0 p-0 {{ $filter === 'pending_training' ? 'border border-info' : '' }}" style="background: transparent; cursor: pointer;">
                <span class="info-box-icon bg-info"><i class="fas fa-chalkboard-teacher"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Training gate pending</span>
                    <span class="info-box-number">{{ $stats['pending_training'] }}</span>
                    <small class="text-muted">Before constituency can schedule training</small>
                </div>
            </button>
        </div>
        <div class="col-md-3 col-sm-6">
            <button type="button" wire:click="setFilter('training_authorized')" class="info-box shadow-sm w-100 text-left border-0 p-0 {{ $filter === 'training_authorized' ? 'border border-success' : '' }}" style="background: transparent; cursor: pointer;">
                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Training authorized</span>
                    <span class="info-box-number">{{ $stats['completed_training'] }}</span>
                    <small class="text-muted">Phases cleared for training</small>
                </div>
            </button>
        </div>
        <div class="col-md-3 col-sm-6">
            <button type="button" wire:click="setFilter('disbursement_authorized')" class="info-box shadow-sm w-100 text-left border-0 p-0 {{ $filter === 'disbursement_authorized' ? 'border border-primary' : '' }}" style="background: transparent; cursor: pointer;">
                <span class="info-box-icon bg-primary"><i class="fas fa-money-check-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Disbursement authorized</span>
                    <span class="info-box-number">{{ $stats['completed_disbursement'] }}</span>
                    <small class="text-muted">{{ $stats['pending_disbursement'] }} still pending</small>
                </div>
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 border-left-primary" style="border-left: 4px solid #007bff;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-inbox mr-2 text-primary"></i>{{ $actionInbox['inbox_title'] }}
                    </h3>
                    <span class="badge badge-primary">{{ $actionInbox['pending_phase_count'] }} pending</span>
                </div>
                <div class="card-body">
                    @forelse($actionInbox['pending_phases'] as $phase)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $phase['label'] }}</strong>
                                    <div class="text-muted small">{{ $phase['constituency'] }}</div>
                                    @if(!empty($phase['meta']))
                                        <div class="text-primary small mt-1">{{ $phase['meta'] }}</div>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $phase['submitted_at'] }}</small>
                            </div>
                            <div class="mt-2 d-flex flex-wrap">
                                <a href="{{ $phase['url'] }}" class="btn btn-sm btn-primary mr-2 mb-1">
                                    {{ $phase['action_label'] }}
                                </a>
                                @if(!empty($phase['secondary_url']))
                                    <a href="{{ $phase['secondary_url'] }}" class="btn btn-sm btn-outline-secondary mb-1">
                                        {{ $phase['secondary_label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ $actionInbox['inbox_empty'] }}</p>
                    @endforelse
                    <a href="{{ $actionInbox['tasks_queue_url'] }}" class="btn btn-link btn-sm px-0">
                        {{ $actionInbox['tasks_queue_label'] }} →
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Applications by constituency</h3>
                    </div>
                    <a href="{{ route('dashboard', ['national' => 1]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-chart-line mr-1"></i> Full national dashboard
                    </a>
                </div>
                <div class="card-body">
                    @include('livewire.partials.application-volume-ranking')
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-left-primary" style="border-left: 4px solid #007bff;">
                <div class="card-body py-3">
                    <h3 class="h6 font-weight-bold text-uppercase text-muted mb-2">National insights</h3>
                    <ul class="list-unstyled mb-0 small text-muted d-flex flex-wrap">
                        <li class="mr-4 mb-1"><i class="fas fa-check text-success mr-2"></i>Track secretariat backlog from pending authorizations above.</li>
                        <li class="mr-4 mb-1"><i class="fas fa-check text-success mr-2"></i>Compare constituency submission volume before authorizing training.</li>
                        <li class="mr-4 mb-1"><i class="fas fa-check text-success mr-2"></i>Use the full national dashboard for portfolio, recovery, and sector charts.</li>
                        <li class="mb-1"><i class="fas fa-check text-success mr-2"></i>Drill into a phase from the table below to download code lists and sign authorizations.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="btn-group btn-group-sm flex-wrap">
                <button type="button" wire:click="setFilter('pending')" class="btn {{ $filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Pending tasks
                </button>
                <button type="button" wire:click="setFilter('pending_training')" class="btn {{ $filter === 'pending_training' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Training gate pending
                </button>
                <button type="button" wire:click="setFilter('training_authorized')" class="btn {{ $filter === 'training_authorized' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Training authorized
                </button>
                <button type="button" wire:click="setFilter('disbursement_authorized')" class="btn {{ $filter === 'disbursement_authorized' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Disbursement authorized
                </button>
                <button type="button" wire:click="setFilter('all')" class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                    All phases
                </button>
                <button type="button" wire:click="setFilter('completed')" class="btn {{ $filter === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Fully authorized
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ $filterLabel }}</h3>
            <p class="text-muted small mb-0">Showing applications and phases in this category. Download the code list before signing off each phase.</p>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Phase</th>
                        <th>Constituency</th>
                        <th>HQ officer</th>
                        <th>Next action</th>
                        <th>Progress</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($phases as $phase)
                        @php
                            $secretariat = app(\App\Services\Loan\SecretariatApprovalService::class);
                            $progress = $secretariat->approvalProgress($phase);
                            $nextType = $secretariat->nextPendingApprovalType($phase);
                            $reviewLabel = !$nextType
                                ? 'View'
                                : ($progress['done'] === 0 ? 'Start review' : 'Continue');
                        @endphp
                        <tr class="{{ $nextType ? 'table-warning' : '' }}">
                            <td>{{ $phase->phase }}<br><small class="text-muted">{{ $phase->phase_ref }}</small></td>
                            <td>{{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}</td>
                            <td>{{ $phase->hqOfficer?->name ?? '—' }}</td>
                            <td>
                                @if($nextType)
                                    <span class="badge bg-warning text-dark">
                                        {{ $secretariat->approvalTypeLabel($nextType) }}
                                    </span>
                                @else
                                    <span class="badge bg-success">Complete</span>
                                @endif
                            </td>
                            <td>{{ $progress['done'] }} / {{ $progress['total'] }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('secretariat.phases.code-list', $phase) }}" class="btn btn-sm btn-outline-secondary">
                                    Code list
                                </a>
                                <a href="{{ route('secretariat.phases.review', $phase) }}" class="btn btn-sm {{ $nextType ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $reviewLabel }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No phases match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $phases->links() }}</div>
    </div>
</div>
