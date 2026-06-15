<div class="table-responsive p-0"
        style="max-height: 550px; transition: opacity 0.3s ease;"
        wire:key="pending-applications">
    <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
        <thead>
            <tr>
                <x-table-header
                    field="phase"
                    :sortField="$sortField"
                    :sortDirection="$sortDirection"
                >
                    Phase Details
                </x-table-header>

                <x-table-header :sortable="false">
                    Vetting Progress
                </x-table-header>

                <x-table-header
                    field="applied"
                    :sortField="$sortField"
                    :sortDirection="$sortDirection"
                >
                    Amount Applied
                </x-table-header>

                <x-table-header
                    field="approved"
                    :sortField="$sortField"
                    :sortDirection="$sortDirection"
                >
                    Amount Proposed
                </x-table-header>

                <x-table-header
                    field="review_date"
                    :sortField="$sortField"
                    :sortDirection="$sortDirection"
                    class="text-right"
                >
                    Review Date
                </x-table-header>

                <x-table-header :sortable="false">
                    Action
                </x-table-header>
            </tr>
        </thead>
        <tbody>
            @forelse($phases as $phase)
                <tr wire:key="phase-row-{{ $phase->id }}-{{ $activeTab }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center">
                            <div>
                                <span class="d-block text-gray-900">{{ ucwords($phase->phase) }}</span>
                                @if ($phase->total_apps > 0)
                                    <small class="text-gray-500 font-weight-bold">Applications: {{ $phase->total_apps }}</small><br>
                                    @if ($phase->pending_apps > 0)
                                        <small class="text-gray-500 text-xs">{{ $phase->pending_apps }} Pending Vetting</small>
                                    @else
                                        <small class="text-gray-500 text-xs">Awaiting submission</small>
                                    @endif
                                @else
                                    <small class="text-gray-500 text-xs">No assigned applications</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <small class="text-xs">Completion</small>
                            <span class="badge {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) == 100 ? 'badge-primary' : 'badge-info' }} shadow-sm">
                                {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) }}%
                            </span>
                        </div>
                        <div class="progress progress-xxs shadow-sm">
                            <div class="progress-bar bg-{{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) == 100 ? 'primary' : 'info' }}"
                                role="progressbar"
                                style="width: {{ $this->vettingPercentage($phase->total_apps, $phase->pending_apps) }}%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-900">KES {{ number_format($phase->total_requested, 2) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-900">KES {{ number_format($phase->total_approved, 2) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900"><i class="far fa-calendar-alt mr-1"></i> {{ $phase->review_date?->format('M d, Y') ?? 'Not Scheduled' }}</div>
                        <small class="text-gray-500">
                            {{ $phase->review_date?->diffForHumans() ?? '' }}
                        </small>
                    </td>
                    <td class="px-4 py-3">
                        <div x-data="{ open: false }" class="btn-group">

                            <a href="{{ route('const.app.app_phase_details', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                                class="btn btn-white btn-sm border shadow-sm"
                                title="View Batch Details"
                                >
                                    <i class="fas fa-folder-open text-primary mr-1"></i> Manage
                            </a>
                            <button type="button"
                                    x-ref="trigger" {{-- Reference for the anchor --}}
                                    @click="open = !open"
                                    class="btn btn-white btn-sm border shadow-sm dropdown-toggle dropdown-icon">
                            </button>

                            <template x-teleport="body">
                                <div x-show="open"
                                    x-anchor.bottom-end="$refs.trigger"
                                    @click.away="open = false"
                                    x-cloak
                                    x-transition:enter="animate__animated animate__fadeIn animate__faster"
                                    class="dropdown-menu show shadow-lg border-0 animate__animated animate__fadeIn animate__faster"
                                    style="z-index: 9999; min-width: 200px; position: fixed;">

                                    @if($phase->canAddApplications())
                                        <button type="button"
                                                class="dropdown-item py-2"
                                                @click="open = false; $dispatch('open-add-apps-modal', {
                                                    phaseId: {{ $phase->id }},
                                                    constituency_code: '{{ $constituency->constituency_code }}'
                                                })">
                                            <i class="fas fa-plus text-primary mr-2"></i> Attach Applications
                                        </button>
                                    @endif

                                    <div class="dropdown-divider"></div>

                                    <button type="button" class="dropdown-item py-2 text-danger"
                                            wire:click="deletePhase({{ $phase->id }})"
                                            @click="open = false">
                                        <i class="fas fa-trash-alt mr-2"></i> Remove Phase
                                    </button>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
            @empty
                <tr wire:key="empty-state-{{ $activeTab }}">
                    <td colspan="6" class="text-center py-5">
                        @if(filled($search))
                            <h3 class="text-gray-500">No pending phases detected for "{{ $search }}".</h3>
                            <x-pill-button
                                target="resetFilters"
                                label="Clear Filters"
                                icon="fas fa-eraser"
                                size="sm"
                                class="px-4 mr-1 shadow-sm btn-refresh-premium"
                            />
                        @else
                            <h5 class="text-muted">No review phases detected.</h5>
                            <p class="text-muted px-5">Groups cannot be approved until they are assigned to a specific review phase.</p>

                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
