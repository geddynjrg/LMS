
 {{-- 1. Loop through Status/Phase History --}}
                                        @forelse($application->audit_logs ?? [] as $log)
                                            {{-- Note: Replace 'audit_logs' with your actual relationship name, e.g., 'activities' or 'logs' --}}
                                            <div class="time-label">
                                                <span class="bg-gray small">{{ $log->created_at->format('d M, Y') }}</span>
                                            </div>

                                            <div>
                                                {{-- Determine Icon based on action --}}
                                                @php
                                                    $icon = 'fas fa-info-circle bg-primary';
                                                    if(str_contains($log->action, 'Approved')) $icon = 'fas fa-check bg-success';
                                                    if(str_contains($log->action, 'Rejected')) $icon = 'fas fa-times bg-danger';
                                                    if(str_contains($log->action, 'Phase')) $icon = 'fas fa-step-forward bg-info';
                                                @endphp

                                                <i class="{{ $icon }}"></i>
                                                <div class="timeline-item shadow-sm border">
                                                    <span class="time"><i class="fas fa-clock"></i> {{ $log->created_at->format('H:i A') }}</span>

                                                    <h3 class="timeline-header no-border">
                                                        <span class="text-primary font-weight-bold">{{ $log->user->name ?? 'System' }}</span>
                                                        {{ $log->action }}
                                                    </h3>

                                                    @if($log->remarks)
                                                        <div class="timeline-body bg-light m-2 rounded small text-muted">
                                                            <i class="fas fa-comment-dots mr-1"></i> "{{ $log->remarks }}"
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            {{-- 2. Fallback: Show Basic Application Milestones if no logs exist --}}
                                            <div class="time-label">
                                                <span class="bg-success">{{ $application->created_at->format('d M, Y') }}</span>
                                            </div>
                                            <div>
                                                <i class="fas fa-file-import bg-blue"></i>
                                                <div class="timeline-item shadow-sm border">
                                                    <span class="time"><i class="fas fa-clock"></i> {{ $application->created_at->format('H:i A') }}</span>
                                                    <h3 class="timeline-header no-border">Application Submitted via Portal</h3>
                                                </div>
                                            </div>

                                            @if($application->phaseApplication)
                                                <div>
                                                    <i class="fas fa-route bg-info"></i>
                                                    <div class="timeline-item shadow-sm border">
                                                        <span class="time"><i class="fas fa-clock"></i> {{ $application->phaseApplication->created_at->format('H:i A') }}</span>
                                                        <h3 class="timeline-header no-border">
                                                            Assigned to Phase: <span class="badge badge-info">{{ $application->phaseApplication->reviewPhase->name }}</span>
                                                        </h3>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforelse
                                        @forelse($application->activities()->latest()->get() as $activity)
                                                {{-- Date Label --}}
                                                <div class="time-label">
                                                    <span class="bg-gray-light border px-2 small">
                                                        {{ $activity->created_at->format('d M, Y') }}
                                                    </span>
                                                </div>

                                                <div>
                                                    {{-- Logic for Icons/Colors --}}
                                                    @php
                                                        $icon = 'fas fa-edit bg-primary';
                                                        if($activity->description === 'created') $icon = 'fas fa-plus bg-success';
                                                        if($activity->description === 'deleted') $icon = 'fas fa-trash bg-danger';
                                                        if(isset($activity->properties['attributes']['status'])) $icon = 'fas fa-sync bg-info';
                                                    @endphp

                                                    <i class="{{ $icon }}"></i>

                                                    <div class="timeline-item shadow-sm">
                                                        <span class="time"><i class="fas fa-clock"></i> {{ $activity->created_at->format('H:i') }}</span>

                                                        <h3 class="timeline-header no-border">
                                                            <span class="text-primary font-weight-bold">
                                                                {{ $activity->causer->name ?? 'System' }}
                                                            </span>
                                                            {{ ucfirst($activity->description) }} application
                                                        </h3>

                                                        @if($activity->properties->has('attributes'))
                                                            <div class="timeline-body p-2">
                                                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                                                    <tr class="bg-light">
                                                                        <th>Field</th>
                                                                        <th>Previous</th>
                                                                        <th>New Value</th>
                                                                    </tr>
                                                                    @foreach($activity->properties['attributes'] as $key => $value)
                                                                        <tr>
                                                                            <td class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                                            <td class="text-muted">{{ $activity->properties['old'][$key] ?? 'N/A' }}</td>
                                                                            <td class="text-success">{{ $value }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="text-center p-5">
                                                    <p class="text-muted">No audit logs found for this application.</p>
                                                </div>
                                            @endforelse

                                        {{-- Final Icon --}}
                                        <div>
                                            <i class="fas fa-dot-circle bg-gray"></i>
                                        </div>
