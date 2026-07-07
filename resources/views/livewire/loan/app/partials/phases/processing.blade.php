<div class="table-responsive p-0"
        style="max-height: 550px; transition: opacity 0.3s ease;"
        wire:key="processing-applications">
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
                    Submited Date
                </x-table-header>

                <x-table-header
                    field="status"
                    :sortable="false"
                >
                    Status
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
                                <small class="text-gray-500 font-weight-bold">Applications: {{ $phase->total_apps }}</small><br>
                                <small class="text-gray-500 text-xs"> Approved: {{$phase->approved_apps}} | Rejected: {{$phase->rejected_apps}}</small>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-900">KES {{ number_format($phase->total_requested, 2) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-900">KES {{ number_format($phase->total_approved, 2) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center">
                            <div>
                                <span class="d-block text-gray-900 text-sm"><i class="far fa-calendar-alt mr-1"></i> {{ $phase->submitted_to_hq_at?->format('M d, Y') ?? 'N/A'}}</span>
                                <span class="text-gray-500 text-xs">{{ $phase->submitted_to_hq_at?->format('h:i A') ?? 'N/A'}}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php $displayStatus = $this->getPhaseDisplayStatus($phase); @endphp
                        <span class="{{ $displayStatus['class'] }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                            {{ $displayStatus['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div x-data="{ open: false }" class="btn-group">
                            <a href="{{ route('const.app.app_phase_details', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                                class="btn btn-white btn-sm border shadow-sm"
                                title="Manage Batch"
                                >
                                    <i class="fas fa-folder-open text-primary mr-1"></i> Manage
                            </a>
                            @if($this->canEditPhaseDetails($phase) || $this->canDeletePhase($phase))
                            <button type="button"
                                    x-ref="trigger"
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

                                    @if($this->canEditPhaseDetails($phase))
                                        <button type="button"
                                                class="dropdown-item py-2"
                                                wire:click="openEditPhaseModal({{ $phase->id }})"
                                                @click="open = false">
                                            <i class="fas fa-edit text-info mr-2"></i> Edit Details
                                        </button>
                                    @endif

                                    @if($this->canEditPhaseDetails($phase) && $this->canDeletePhase($phase))
                                        <div class="dropdown-divider"></div>
                                    @endif

                                    @if($this->canDeletePhase($phase))
                                    <button type="button" class="dropdown-item py-2 text-danger"
                                            wire:click="deletePhase({{ $phase->id }})"
                                            @click="open = false">
                                        <i class="fas fa-trash-alt mr-2"></i> Remove Phase
                                    </button>
                                    @endif
                                </div>
                            </template>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr wire:key="empty-state-{{ $activeTab }}">
                    <td colspan="6" class="text-center py-5">
                        @if(filled($search))
                            <h3 class="text-sm text-gray-600">No Processing phases detected for "{{ $search }}".</h3>
                            <x-pill-button
                                target="resetFilters"
                                label="Clear Filters"
                                icon="fas fa-eraser"
                                size="sm"
                                class="px-4 mr-1 shadow-sm btn-refresh-premium"
                            />
                        @else
                            <h6 class="text-gray-600">No Processing Phases at the moment.</h6>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
