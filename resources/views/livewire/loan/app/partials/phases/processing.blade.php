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
                        <a href="{{ route('const.app.app_phase_details', [$constituency->county->slug, $constituency->slug, $phase->slug]) }}"
                            class="btn btn-refresh-premium btn-sm border shadow-sm"
                            title="View Batch Details"
                            >
                                <i class="fas fa-eye mr-1"></i> View
                        </a>
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
