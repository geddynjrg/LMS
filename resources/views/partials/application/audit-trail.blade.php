<div class="card animate-on-load" x-data="{
    logSearch: '',
    filterEvent: 'all'
}">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h6 class="mb-0 font-weight-bold text-dark text-uppercase" style="letter-spacing: 1px;">
                    <i class="fas fa-fingerprint mr-2 text-primary"></i> Activity Log
                </h6>
                <small class="text-muted">Tracking all modifications to this application</small>
            </div>

            <div class="mt-3 mt-md-0 d-flex">
                <div class="input-group input-group-sm mr-2" style="width: 200px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" x-model="logSearch" class="form-control border-left-0" placeholder="Filter logs...">
                </div>

                <select x-model="filterEvent" class="form-control form-control-sm" style="width: 130px;">
                    <option value="all">All Events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card-body pt-0">
        <div class="timeline-scroll-container pr-2" style="max-height: 650px; overflow-y: auto;">

            @forelse($this->application->activities->groupBy(fn($item) => $item->created_at->format('Y-m-d')) as $date => $events)

                <div class="d-flex align-items-center my-4">
                    <div class="flex-grow-1 border-top"></div>
                    <span class="mx-3 badge badge-soft-secondary text-uppercase font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        {{ \Carbon\Carbon::parse($date)->isToday() ? 'Today' : (\Carbon\Carbon::parse($date)->isYesterday() ? 'Yesterday' : \Carbon\Carbon::parse($date)->format('M d, Y')) }}
                    </span>
                    <div class="flex-grow-1 border-top"></div>
                </div>

                <div class="timeline-steps ml-3">
                    @foreach($events as $activity)
                        @php
                            $activityDescription = preg_replace('/\s+Backup:\s+\S+/', '', $activity->description) ?: $activity->description;

                            $config = match($activity->event) {
                                'created' => ['color' => 'success', 'icon' => 'fa-plus-circle', 'bg' => '#e1fcef'],
                                'updated' => ['color' => 'info', 'icon' => 'fa-pen-nib', 'bg' => '#e0f2ff'],
                                'deleted' => ['color' => 'danger', 'icon' => 'fa-trash-alt', 'bg' => '#ffe5e5'],
                                default   => ['color' => 'primary', 'icon' => 'fa-history', 'bg' => '#f0f3f5'],
                            };
                        @endphp

                        <div class="timeline-item border-left pb-4 ml-2 position-relative"
                             x-show="(filterEvent === 'all' || '{{ $activity->event }}' === filterEvent) &&
                                     (logSearch === '' || '{{ strtolower($activityDescription) }}'.includes(logSearch.toLowerCase()))"
                             style="border-left: 2px solid #e9ecef !important;">

                            <div class="position-absolute d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                                 style="left: -13px; top: 0; width: 26px; height: 26px; z-index: 1; background: {{ $config['bg'] }}; border: 2px solid white;">
                                <i class="fas {{ $config['icon'] }} text-{{ $config['color'] }}" style="font-size: 11px;"></i>
                            </div>

                            <div class="ml-4">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-0 text-dark font-weight-bold" style="font-size: 0.9rem;">
                                            {{ ucfirst($activityDescription) }}
                                        </p>
                                        <div class="d-flex align-items-center mt-1">
                                            <div class="avatar-xs mr-2" style="width: 20px; height: 20px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="font-size: 10px; color: #999;"></i>
                                            </div>
                                            <small class="text-muted">
                                                <span class="text-dark font-weight-medium">{{ $activity->causer->name ?? 'System' }}</span>
                                                <span class="mx-1">•</span>
                                                <span>{{ $activity->created_at->format('h:i A') }}</span>
                                            </small>
                                        </div>
                                    </div>

                                    @if($activity->changes && isset($activity->changes['attributes']))
                                        <div x-data="{ open: false }">
                                            <button @click="open = !open" class="btn btn-xs btn-outline-light text-dark border shadow-sm px-2">
                                                <i class="fas fa-list-ul mr-1"></i> <span x-text="open ? 'Hide' : 'Changes'"></span>
                                            </button>

                                            <div x-show="open" x-collapse x-cloak class="position-relative mt-2" style="z-index: 10;">
                                                <div class="bg-white border rounded shadow-sm p-2 mt-2" style="min-width: 250px;">
                                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.7rem;">
                                                        <thead class="border-bottom">
                                                            <tr class="text-muted">
                                                                <th>Attribute</th>
                                                                <th>New Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($activity->changes['attributes'] as $key => $value)
                                                                @if(!is_array($value))
                                                                <tr>
                                                                    <td class="text-muted">{{ str_replace('_', ' ', $key) }}</td>
                                                                    <td class="font-weight-bold text-primary">{{ $value ?? 'empty' }}</td>
                                                                </tr>
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="text-center py-5">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNNDggOEgyNEMyMS43OTA5IDggMjAgOS43OTA4NiAyMCAxMlY1MkMyMCA1NC4yMDkxIDIxLjc5MDkgNTYgMjQgNTZINDhDNDkuMjA5MSA1NiA1MSA1NS4xMDQ2IDUxIDU0VjEwQzUxIDguODk1NDMgNTAuMTA0NiA4IDQ4IDhaIiBzdHJva2U9IiNFOEU4RjAiIHN0cm9rZS13aWR0aD0iMiIvPjwvc3ZnPg==" class="mb-3 opacity-50">
                    <h6 class="text-muted">No activity recorded yet</h6>
                </div>
            @endforelse
        </div>
    </div>
</div>

<style>
    /* Custom Scrollbar for a Premium Look */
    .timeline-scroll-container::-webkit-scrollbar {
        width: 4px;
    }
    .timeline-scroll-container::-webkit-scrollbar-track {
        background: transparent;
    }
    .timeline-scroll-container::-webkit-scrollbar-thumb {
        background: #e9ecef;
        border-radius: 10px;
    }
    .timeline-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #dee2e6;
    }
    .badge-soft-secondary {
        background-color: #f3f4f7;
        color: #6c757d;
    }
    .font-weight-medium { font-weight: 500; }
</style>
