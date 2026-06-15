<div class="activity-log-wrapper" x-data="{ expandedLog: null }">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="font-weight-bold mb-0">System Activity Audit</h5>
        <div class="badge badge-pill badge-light border px-3 py-2">
            Total Logs: {{ $activities ? $activities->total() : 0 }}
        </div>
    </div>

    <div class="timeline-scroll-wrapper px-2" style="max-height: 650px; overflow-y: auto; scrollbar-width: thin;">
        <div class="timeline timeline-inverse">
            @php
                $groupedLogs = $activities
                    ? $activities->getCollection()->groupBy(fn($i) => $i->created_at->format('d M, Y'))
                    : collect();
            @endphp
            @forelse($groupedLogs as $date => $logs)
                <div class="time-label">
                    <span class="bg-info shadow-sm px-3">{{ $date }}</span>
                </div>

                @foreach($logs as $log)
                    @php
                        $isUpdate = $log->description === 'updated';
                        $isDanger = in_array($log->description, ['deleted', 'deactivated']);
                        $iconClass = $isUpdate ? 'bg-primary' : ($isDanger ? 'bg-danger' : 'bg-success');
                    @endphp

                    <div class="mb-4">
                        <i class="fas {{ $isUpdate ? 'fa-pen' : ($isDanger ? 'fa-exclamation-triangle' : 'fa-plus') }} {{ $iconClass }} shadow-sm"></i>

                        <div class="timeline-item shadow-none border rounded-lg">
                            <span class="time text-muted mt-1 mr-2">
                                <i class="far fa-clock"></i> {{ $log->created_at->format('h:i A') }}
                            </span>

                            <h3 class="timeline-header border-0 pb-0">
                                <span class="text-capitalize font-weight-bold text-dark">{{ $log->description }}</span>
                                <span class="text-muted mx-1">by</span>
                                <span class="badge badge-light border">{{ $log->causer?->name ?? 'System' }}</span>
                            </h3>

                            <div class="timeline-body pt-2 pb-2">
                                <p class="mb-2 text-secondary">
                                    Performed action on {{ class_basename($log->subject_type) }}:
                                    <span class="text-dark font-weight-600">
                                        {{ $log->subject?->name ?? $log->subject?->id ?? 'Record #'.$log->subject_id }}
                                    </span>
                                </p>

                                {{-- Details Toggle with Alpine --}}
                                @if($log->properties->has('attributes'))
                                    <div class="mt-2">
                                        <button @click="expandedLog === {{ $log->id }} ? expandedLog = null : expandedLog = {{ $log->id }}"
                                                class="btn btn-xs btn-link text-info p-0 font-weight-bold"
                                                style="text-decoration: none;">
                                            <i class="fas" :class="expandedLog === {{ $log->id }} ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                            <span x-text="expandedLog === {{ $log->id }} ? 'Hide Changes' : 'View Changes'"></span>
                                        </button>

                                        <div x-show="expandedLog === {{ $log->id }}"
                                             x-collapse
                                             class="bg-light rounded p-3 mt-2 border-left border-info"
                                             style="display: none;">

                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless mb-0 small">
                                                    <thead>
                                                        <tr class="text-muted border-bottom">
                                                            <th>Field</th>
                                                            <th>Old Value</th>
                                                            <th>New Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($log->properties['attributes'] as $key => $value)
                                                            @if(isset($log->properties['old'][$key]))
                                                                <tr>
                                                                    <td class="font-weight-bold text-info" style="width: 30%;">{{ Str::headline($key) }}</td>
                                                                    <td class="text-danger"><s>{{ $log->properties['old'][$key] ?? 'null' }}</s></td>
                                                                    <td class="text-success font-weight-bold">{{ $value ?? 'null' }}</td>
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
            @empty
                <div class="text-center py-5">
                    <i class="fas fa-ghost fa-3x text-light mb-3"></i>
                    <p class="text-muted">No activity logs found for this user.</p>
                </div>
            @endforelse

            <div>
                <i class="fas fa-stopwatch bg-gray shadow-sm"></i>
            </div>
        </div>
        </div>
        <div class="mt-4 ">
            <div class="pagination-sm">
                {{ $activities->links() }}
            </div>
        </div>

    </div>
</div>
