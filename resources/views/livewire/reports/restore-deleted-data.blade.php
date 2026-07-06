<div>
    @if($statusMessage)
        <div class="alert alert-{{ $statusType === 'success' ? 'success' : 'danger' }} alert-dismissible fade show shadow-sm" role="alert">
            {{ $statusMessage }}
            <button type="button" class="close" wire:click="$set('statusMessage', '')" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(!$canRestore)
        <div class="alert alert-warning shadow-sm">
            You do not have permission to restore deleted data for this constituency.
        </div>
    @else
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-1 font-weight-bold">
                    <i class="fas fa-undo text-primary mr-2"></i>
                    Restore Deleted Data
                </h5>
                <p class="text-muted small mb-0">
                    Recover phases or groups deleted with an automatic SQL backup. Restore is blocked when IDs, slugs, GFS codes, or application references already exist.
                </p>
            </div>
            <div class="card-body p-0">
                @if($backups->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-archive fa-3x mb-3 text-light"></i>
                        <p class="mb-0">No restorable backups were found for this constituency.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Deleted</th>
                                    <th>By</th>
                                    <th>Backup file</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                    <tr class="{{ $selectedBackupPath === $backup['backup_path'] ? 'table-primary' : '' }}">
                                        <td>
                                            <span class="badge badge-{{ $backup['type'] === 'phase' ? 'info' : 'secondary' }}">
                                                {{ ucfirst($backup['type']) }}
                                            </span>
                                        </td>
                                        <td class="font-weight-600">{{ $backup['label'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($backup['deleted_at'])->format('d M Y, H:i') }}</td>
                                        <td>{{ $backup['deleted_by'] ?? '—' }}</td>
                                        <td><code class="small">{{ $backup['backup_file'] }}</code></td>
                                        <td class="text-right">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    wire:click="selectBackup(@js($backup['backup_path']))">
                                                Preview
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if($selectedBackupPath)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 font-weight-bold">Restore preview</h6>
                    <code class="small">{{ basename($selectedBackupPath) }}</code>
                </div>
                <div class="card-body">
                    @if($restoreSummary !== [])
                        <p class="text-muted small mb-2">Rows to restore:</p>
                        <ul class="list-inline mb-3">
                            @foreach($restoreSummary as $table => $count)
                                <li class="list-inline-item mr-3">
                                    <span class="badge badge-light border">{{ $table }}: {{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($conflictPreview !== [])
                        <div class="alert alert-danger mb-3">
                            <strong>Restore blocked.</strong> The following conflicts were detected:
                            <ul class="mb-0 mt-2">
                                @foreach($conflictPreview as $conflict)
                                    <li>{{ $conflict }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-success mb-3">
                            No conflicts detected. This backup can be restored.
                        </div>
                        <button type="button"
                                class="btn btn-primary"
                                wire:click="openConfirmModal">
                            <i class="fas fa-undo mr-1"></i> Restore from backup
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif

    @if($showConfirmModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.45);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm restore</h5>
                        <button type="button" class="close" wire:click="closeConfirmModal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">You are about to restore deleted data from:</p>
                        <p><code>{{ basename($selectedBackupPath ?? '') }}</code></p>
                        <p class="text-muted small mb-0">
                            This will re-insert the backed-up database rows. The action is logged in the activity audit trail.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeConfirmModal">Cancel</button>
                        <button type="button"
                                class="btn btn-danger"
                                wire:click="confirmRestore"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="confirmRestore">Yes, restore data</span>
                            <span wire:loading wire:target="confirmRestore">Restoring…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
