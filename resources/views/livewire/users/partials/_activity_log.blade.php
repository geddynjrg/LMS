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
                        $isPhaseAudit = $log->log_name === 'phase_audit';
                        $isGroupAudit = $log->log_name === 'group_audit';
                        $isApplicationAudit = $log->log_name === 'application_audit';
                        $isRestoreAudit = $log->log_name === 'restore_audit';
                        $isCustomAudit = $isPhaseAudit || $isGroupAudit || $isApplicationAudit || $isRestoreAudit;
                        $hasAttributeDiff = $log->properties->has('attributes');
                        $hasValueDiff = $log->properties->has('old_values') || $log->properties->has('new_values');

                        $action = $log->event ?? $log->description;
                        $isUpdate = in_array($action, ['updated', 'update'], true);
                        $isDanger = in_array($action, ['deleted', 'deactivated', 'delete'], true);
                        $isRestore = in_array($action, ['restored', 'restore'], true);
                        $iconClass = $isRestore ? 'bg-success' : ($isUpdate ? 'bg-primary' : ($isDanger ? 'bg-danger' : 'bg-success'));

                        $oldValues = (array) ($log->properties->get('old_values') ?? []);
                        $newValues = (array) ($log->properties->get('new_values') ?? []);
                        $diffKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                        $hiddenDiffKeys = ['id', 'group_loan_id', 'loan_phase_id', 'group_id', 'backup_file'];
                        $visibleDiffKeys = array_values(array_filter(
                            $diffKeys,
                            fn ($key) => ! in_array($key, $hiddenDiffKeys, true),
                        ));

                        if ($isPhaseAudit) {
                            $subjectLabel = 'Phase';
                            $subjectName = $log->subject?->phase
                                ?? ($newValues['phase'] ?? $oldValues['phase'] ?? null)
                                ?? ('#' . ($log->properties->get('loan_phase_id') ?? $log->subject_id));
                        } elseif ($isGroupAudit) {
                            $subjectLabel = 'Group';
                            $subjectName = $newValues['group_name']
                                ?? $oldValues['group_name']
                                ?? $log->subject?->group?->name
                                ?? ('#' . ($log->properties->get('group_id') ?? $log->subject_id));
                        } elseif ($isApplicationAudit) {
                            $subjectLabel = 'Application';
                            $subjectName = $newValues['application_id']
                                ?? $oldValues['application_id']
                                ?? $oldValues['group_name']
                                ?? ('#' . ($log->properties->get('temp_application_id') ?? $log->subject_id));
                        } elseif ($isRestoreAudit) {
                            $subjectLabel = ucfirst((string) ($log->properties->get('backup_type') ?? 'Backup'));
                            $subjectName = 'Restored data';
                        } else {
                            $subjectLabel = class_basename($log->subject_type);
                            $subjectName = $log->subject?->name ?? $log->subject?->phase ?? $log->subject?->id ?? 'Record #' . $log->subject_id;
                        }

                        $constituencyCode = $log->properties->get('constituency_code');
                        $constituencyLabel = ($showConstituency ?? false) && $constituencyCode
                            ? ($constituencyNameMap[$constituencyCode] ?? $constituencyCode)
                            : null;

                        $stripBackupPathFromDescription = static function (?string $description): string {
                            if ($description === null || $description === '') {
                                return '';
                            }

                            $stripped = preg_replace('/\s+Backup:\s+\S+/', '', $description);

                            return $stripped !== '' ? $stripped : $description;
                        };

                        $auditDescription = $stripBackupPathFromDescription($log->description);

                        $formatAuditValue = static function ($value): string {
                            if ($value === null || $value === '') {
                                return '—';
                            }

                            if (is_bool($value)) {
                                return $value ? 'Yes' : 'No';
                            }

                            if (is_array($value)) {
                                return json_encode($value);
                            }

                            return (string) $value;
                        };
                    @endphp

                    <div class="mb-4">
                        <i class="fas {{ $isRestore ? 'fa-undo' : ($isUpdate ? 'fa-pen' : ($isDanger ? 'fa-exclamation-triangle' : 'fa-plus')) }} {{ $iconClass }} shadow-sm"></i>

                        <div class="timeline-item shadow-none border rounded-lg">
                            <span class="time text-muted mt-1 mr-2">
                                <i class="far fa-clock"></i> {{ $log->created_at->format('h:i A') }}
                            </span>

                            <h3 class="timeline-header border-0 pb-0">
                                @if($isCustomAudit)
                                    <span class="badge badge-{{ $isPhaseAudit ? 'info' : ($isApplicationAudit ? 'warning' : ($isRestoreAudit ? 'success' : 'secondary')) }} mr-1">
                                        {{ $isPhaseAudit ? 'Phase Audit' : ($isApplicationAudit ? 'Application Audit' : ($isRestoreAudit ? 'Restore Audit' : 'Group Audit')) }}
                                    </span>
                                @endif
                                <span class="text-capitalize font-weight-bold text-dark">
                                    {{ $isCustomAudit ? $auditDescription : ucfirst($auditDescription) }}
                                </span>
                                <span class="text-muted mx-1">by</span>
                                <span class="badge badge-light border">{{ $log->causer?->name ?? 'System' }}</span>
                            </h3>

                            <div class="timeline-body pt-2 pb-2">
                                @if($constituencyLabel)
                                    <p class="mb-2 small text-muted">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        Constituency: <span class="text-dark font-weight-600">{{ ucwords(strtolower($constituencyLabel)) }}</span>
                                    </p>
                                @endif

                                <p class="mb-2 text-secondary">
                                    Performed action on {{ $subjectLabel }}:
                                    <span class="text-dark font-weight-600">{{ $subjectName }}</span>
                                </p>

                                @if($isPhaseAudit && $isDanger && ($cascadeCounts = $log->properties->get('cascade_counts')))
                                    @if(($cascadeCounts['group_loans'] ?? 0) > 0)
                                        <p class="mb-2 small text-muted">
                                            Removed
                                            <strong>{{ $cascadeCounts['group_loans'] }}</strong> group loan(s),
                                            <strong>{{ $cascadeCounts['group_members'] ?? 0 }}</strong> member(s),
                                            <strong>{{ $cascadeCounts['groups'] ?? 0 }}</strong> group record(s),
                                            and <strong>{{ $cascadeCounts['loan_receipts'] ?? 0 }}</strong> receipt(s).
                                        </p>
                                    @endif
                                @endif

                                @if($log->properties->get('ip_address'))
                                    <p class="mb-2 small text-muted">
                                        <i class="fas fa-network-wired mr-1"></i>
                                        IP: {{ $log->properties->get('ip_address') }}
                                    </p>
                                @endif

                                @php
                                    $backupFile = $log->properties->get('backup_file')
                                        ?? ($log->properties->get('cascade_counts')['backup_file'] ?? null);
                                    $restoreCountySlug = request()->route('county');
                                    $restoreConstituency = null;

                                    if ($backupFile && $restoreCountySlug) {
                                        $constituencyCode = $log->properties->get('constituency_code');
                                        $restoreConstituency = $constituencyCode
                                            ? \App\Models\Constituency::where('constituency_code', $constituencyCode)->value('slug')
                                            : session('constituency')?->slug;
                                    }
                                @endphp

                                @if($isDanger && $backupFile && ($isPhaseAudit || $isGroupAudit || $isApplicationAudit) && $restoreCountySlug && $restoreConstituency)
                                    <p class="mb-2">
                                        <a href="{{ route('const.reports.restore-deleted-data', [
                                            'county' => $restoreCountySlug,
                                            'constituency' => $restoreConstituency,
                                        ]) }}"
                                           class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-undo mr-1"></i> Restore from backup
                                        </a>
                                        <span class="small text-muted d-block mt-1">Backup available</span>
                                    </p>
                                @endif

                                @if($hasAttributeDiff || ($hasValueDiff && $visibleDiffKeys !== []))
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
                                                        @if($hasAttributeDiff)
                                                            @foreach($log->properties['attributes'] as $key => $value)
                                                                @if(isset($log->properties['old'][$key]))
                                                                    <tr>
                                                                        <td class="font-weight-bold text-info" style="width: 30%;">{{ Str::headline($key) }}</td>
                                                                        <td class="text-danger"><s>{{ $log->properties['old'][$key] ?? 'null' }}</s></td>
                                                                        <td class="text-success font-weight-bold">{{ $value ?? 'null' }}</td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            @foreach($visibleDiffKeys as $key)
                                                                @php
                                                                    $oldValue = $oldValues[$key] ?? null;
                                                                    $newValue = $newValues[$key] ?? null;
                                                                @endphp
                                                                <tr>
                                                                    <td class="font-weight-bold text-info" style="width: 30%;">{{ Str::headline($key) }}</td>
                                                                    <td class="text-danger">
                                                                        @if($isDanger && ! array_key_exists($key, $newValues))
                                                                            <s>{{ $formatAuditValue($oldValue) }}</s>
                                                                        @else
                                                                            {{ $formatAuditValue($oldValue) }}
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-success font-weight-bold">
                                                                        @if($isDanger && ! array_key_exists($key, $newValues))
                                                                            <span class="text-muted">Removed</span>
                                                                        @else
                                                                            {{ $formatAuditValue($newValue) }}
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endif
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
                    <p class="text-muted">
                        {{ ($showConstituency ?? false) ? 'No audit entries found for the selected filters.' : 'No activity logs found for this user.' }}
                    </p>
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
