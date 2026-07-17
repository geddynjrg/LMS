<div class="secretariat-command-center">
    @if(session('secretariat_returned'))
        @php $returned = session('secretariat_returned'); @endphp
        <div class="alert alert-warning alert-dismissible shadow-sm sec-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>Phase returned to HQ.</strong>
            {{ $returned['phase_label'] ?? 'Phase' }} ({{ $returned['constituency'] ?? '' }}) has been sent back to the HQ officer for review.
        </div>
    @endif

    @if(session('secretariat_completed'))
        @php $completed = session('secretariat_completed'); @endphp
        <div class="alert alert-success alert-dismissible shadow-sm sec-alert">
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
                    <span class="sec-muted">({{ $completed['phase_ref'] }})</span>
                @endif
            </p>
            <div class="d-flex flex-wrap gap-2">
                @if(!empty($completed['next_phase_url']))
                    <a href="{{ $completed['next_phase_url'] }}" class="btn btn-sm btn-success mr-2 mb-1">
                        <i class="fas fa-arrow-right mr-1"></i> Review next: {{ $completed['next_phase_label'] }}
                    </a>
                @endif
                <span class="sec-muted small align-self-center">Pick another phase from the pending list below.</span>
            </div>
        </div>
    @endif

    {{-- Hero strip --}}
    <div class="sec-hero mb-4">
        <div class="sec-hero-glow"></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <span class="sec-hero-badge"><i class="fas fa-bolt mr-1"></i> Authorization queue</span>
                <h2 class="sec-hero-title mb-1">
                    <span class="hq-count-up" data-count-up="{{ $stats['pending_phases'] }}">0</span>
                    phases need your signature
                </h2>
                <p class="sec-hero-sub mb-0">Swipe through pending tasks, preview code lists, and authorize in one flow.</p>
            </div>
            <div class="sec-hero-actions mt-3 mt-md-0">
                <a href="{{ route('dashboard', ['national' => 1]) }}" class="btn sec-btn-glass">
                    <i class="fas fa-chart-pie mr-1"></i> Analytics
                </a>
            </div>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="row mb-4 sec-stat-grid">
        @foreach([
            ['key' => 'pending', 'tone' => 'amber', 'icon' => 'fa-hourglass-half', 'label' => 'Pending authorizations', 'value' => $stats['pending_phases'], 'hint' => 'Awaiting your action'],
            ['key' => 'pending_training', 'tone' => 'sky', 'icon' => 'fa-chalkboard-teacher', 'label' => 'Training gate', 'value' => $stats['pending_training'], 'hint' => 'Before training schedule'],
            ['key' => 'training_authorized', 'tone' => 'emerald', 'icon' => 'fa-check-circle', 'label' => 'Training authorized', 'value' => $stats['completed_training'], 'hint' => 'Cleared for training'],
            ['key' => 'disbursement_authorized', 'tone' => 'violet', 'icon' => 'fa-money-check-alt', 'label' => 'Disbursement authorized', 'value' => $stats['completed_disbursement'], 'hint' => $stats['pending_disbursement'].' still pending'],
        ] as $tile)
            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                <button type="button"
                    wire:click="setFilter('{{ $tile['key'] }}')"
                    class="sec-stat-tile sec-stat-tile--{{ $tile['tone'] }} w-100 text-left {{ $filter === $tile['key'] ? 'is-active' : '' }}">
                    <span class="sec-stat-tile__icon"><i class="fas {{ $tile['icon'] }}"></i></span>
                    <span class="sec-stat-tile__label">{{ $tile['label'] }}</span>
                    <span class="sec-stat-tile__value hq-count-up" data-count-up="{{ $tile['value'] }}">0</span>
                    <span class="sec-stat-tile__hint">{{ $tile['hint'] }}</span>
                </button>
            </div>
        @endforeach
    </div>

    {{-- Filter pills (TikTok-style chips) --}}
    <div class="sec-filter-rail mb-4">
        <div class="sec-filter-pills">
            @foreach([
                'pending' => ['Pending tasks', 'fa-inbox'],
                'pending_training' => ['Training gate', 'fa-chalkboard-teacher'],
                'training_authorized' => ['Training OK', 'fa-check'],
                'disbursement_authorized' => ['Disbursement OK', 'fa-money-check'],
                'all' => ['All phases', 'fa-layer-group'],
                'completed' => ['Fully authorized', 'fa-star'],
            ] as $key => [$label, $icon])
                <button type="button"
                    wire:click="setFilter('{{ $key }}')"
                    class="sec-filter-pill {{ $filter === $key ? 'is-active' : '' }}">
                    <i class="fas {{ $icon }} mr-1"></i>{{ $label }}
                </button>
            @endforeach
        </div>
        <div wire:loading wire:target="setFilter" class="sec-filter-loading">
            <span class="spinner-border spinner-border-sm text-success mr-1"></span> Updating feed…
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="sec-panel h-100">
                <div class="sec-panel__header">
                    <h3 class="sec-panel__title">
                        <i class="fas fa-inbox mr-2"></i>{{ $actionInbox['inbox_title'] }}
                    </h3>
                    <span class="sec-pill sec-pill--live">{{ $actionInbox['pending_phase_count'] }} pending</span>
                </div>
                <div class="sec-panel__body sec-feed-scroll">
                    @forelse($actionInbox['pending_phases'] as $phase)
                        <div class="sec-feed-card hq-inbox-feed-item hq-inbox-feed-item--visible card-hover" style="--hq-stagger-index: {{ $loop->index }}">
                            <div class="sec-feed-card__top">
                                <div>
                                    <div class="sec-feed-card__title">{{ $phase['label'] }}</div>
                                    <div class="sec-feed-card__meta"><i class="fas fa-map-marker-alt mr-1"></i>{{ $phase['constituency'] }}</div>
                                    @if(!empty($phase['meta']))
                                        <div class="sec-feed-card__tag">{{ $phase['meta'] }}</div>
                                    @endif
                                </div>
                                <small class="sec-feed-card__time">{{ $phase['submitted_at'] }}</small>
                            </div>
                            <div class="sec-feed-card__actions">
                                <a href="{{ $phase['url'] }}" class="btn btn-sm sec-btn-primary">
                                    {{ $phase['action_label'] }}
                                </a>
                                @if(!empty($phase['secondary_url']))
                                    <a href="{{ $phase['secondary_url'] }}" class="btn btn-sm sec-btn-ghost">
                                        {{ $phase['secondary_label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="sec-empty-state">
                            <i class="fas fa-check-double sec-empty-state__icon"></i>
                            <p class="mb-0">{{ $actionInbox['inbox_empty'] }}</p>
                        </div>
                    @endforelse
                    <a href="{{ $actionInbox['tasks_queue_url'] }}" class="sec-link-arrow">
                        {{ $actionInbox['tasks_queue_label'] }} →
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="sec-panel h-100">
                <div class="sec-panel__header">
                    <h3 class="sec-panel__title">Applications by constituency</h3>
                    <a href="{{ route('dashboard', ['national' => 1]) }}" class="btn btn-sm sec-btn-ghost">
                        <i class="fas fa-chart-line mr-1"></i> Full dashboard
                    </a>
                </div>
                <div class="sec-panel__body">
                    @include('livewire.partials.application-volume-ranking')
                </div>
            </div>
        </div>
    </div>

    {{-- Phase feed (graphical cards) --}}
    <div class="sec-panel mb-4">
        <div class="sec-panel__header">
            <div>
                <h3 class="sec-panel__title mb-1">{{ $filterLabel }}</h3>
                <p class="sec-panel__subtitle mb-0">Preview code lists in-browser, then sign authorizations.</p>
            </div>
            <span class="sec-pill">{{ $phases->total() }} phases</span>
        </div>
        <div class="sec-panel__body p-0 position-relative">
            <div wire:loading.delay wire:target="setFilter" class="sec-loading-overlay">
                <div class="sec-loading-shimmer"></div>
            </div>

            <div class="sec-phase-feed p-3">
                @forelse($phases as $phase)
                    @php
                        $secretariat = app(\App\Services\Loan\SecretariatApprovalService::class);
                        $progress = $secretariat->approvalProgress($phase);
                        $nextType = $secretariat->nextPendingApprovalType($phase);
                        $pct = $progress['total'] > 0 ? round(($progress['done'] / $progress['total']) * 100) : 100;
                        $reviewLabel = !$nextType
                            ? 'View'
                            : ($progress['done'] === 0 ? 'Start review' : 'Continue');
                    @endphp
                    <div class="sec-phase-card hq-inbox-feed-item hq-inbox-feed-item--visible {{ $nextType ? 'needs-action' : 'is-complete' }}" style="--hq-stagger-index: {{ $loop->index }}">
                        <div class="sec-phase-card__progress-ring" style="--sec-progress: {{ $pct }};">
                            <span>{{ $progress['done'] }}/{{ $progress['total'] }}</span>
                        </div>
                        <div class="sec-phase-card__body">
                            <div class="sec-phase-card__title">{{ $phase->phase }}</div>
                            <div class="sec-phase-card__ref">{{ $phase->phase_ref }}</div>
                            <div class="sec-phase-card__meta">
                                <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}</span>
                                <span><i class="fas fa-user-tie mr-1"></i>{{ $phase->hqOfficer?->name ?? '—' }}</span>
                            </div>
                            @if($nextType)
                                <span class="sec-status-badge sec-status-badge--pending">
                                    {{ $secretariat->approvalTypeLabel($nextType) }}
                                </span>
                            @else
                                <span class="sec-status-badge sec-status-badge--done">Complete</span>
                            @endif
                        </div>
                        <div class="sec-phase-card__actions">
                            <a href="{{ route('secretariat.phases.processed_code_list.preview', $phase) }}" target="_blank" class="btn btn-sm sec-btn-ghost" title="View code list">
                                <i class="fas fa-file-alt mr-1"></i> Code list
                            </a>
                            <a href="{{ route('secretariat.phases.code-list', $phase) }}" class="btn btn-sm sec-btn-ghost" title="Download Excel">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="{{ route('secretariat.phases.review', $phase) }}" class="btn btn-sm {{ $nextType ? 'sec-btn-primary' : 'sec-btn-ghost' }}">
                                {{ $reviewLabel }} <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="sec-empty-state py-5">
                        <i class="fas fa-folder-open sec-empty-state__icon"></i>
                        <p class="mb-0">No phases match this filter.</p>
                    </div>
                @endforelse
            </div>
        </div>
        @if($phases->hasPages())
            <div class="sec-panel__footer">{{ $phases->links() }}</div>
        @endif
    </div>
</div>
