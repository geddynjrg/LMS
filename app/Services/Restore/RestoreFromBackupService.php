<?php

namespace App\Services\Restore;

use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\TempApplication;
use App\Models\User;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanPhaseService;
use App\Support\BackupSqlParser;
use App\Support\LoanPhaseFinancialTotals;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class RestoreFromBackupService
{
  /** @var list<string> */
  protected const RESTORE_TABLE_ORDER = [
    'loan_phases',
    'groups',
    'group_loans',
    'group_members',
    'loan_receipts',
    'phase_applications',
    'phase_comments',
    'phase_documents',
    'phase_application_documents',
    'committee_members',
    'secretariat_approvals',
    'hq_reviews',
    'temp_applications',
  ];

  public function __construct(
    protected BackupSqlParser $parser,
    protected LoanPhaseService $phaseService,
    protected AppCacheService $cacheService,
  ) {}

  public function userCanRestore(?User $user, string $constituencyCode): bool
  {
    if ($user === null) {
      return false;
    }

    if ($user->hasRole('super-admin')) {
      return true;
    }

    if (!$user->hasAnyRole(['constituency_officer', 'intern'])) {
      return false;
    }

    if (!$user->can('submit application') && !$user->can('create new phase')) {
      return false;
    }

    return $user->constituencies
      ->contains('constituency_code', $constituencyCode);
  }

  /**
   * @return Collection<int, array{
   *   id: string,
   *   type: string,
   *   backup_path: string,
   *   backup_file: string,
   *   deleted_at: string,
   *   deleted_by: ?string,
   *   label: string,
   *   entity_id: ?int,
   *   activity_id: int,
   * }>
   */
  public function listBackupsForConstituency(string $constituencyCode, int $limit = 50): Collection
  {
    return Activity::query()
      ->with('causer')
      ->where(function ($query) {
        $query->where(function ($inner) {
          $inner->whereIn('log_name', ['phase_audit', 'group_audit'])
            ->where('event', 'deleted');
        })->orWhere(function ($inner) {
          $inner->where('log_name', 'application_audit')
            ->whereIn('event', ['deleted', 'updated']);
        });
      })
      ->where('properties->constituency_code', $constituencyCode)
      ->whereNotNull('properties->backup_file')
      ->orderByDesc('created_at')
      ->limit($limit)
      ->get()
      ->map(function (Activity $activity) {
        $properties = $activity->properties->toArray();
        $backupPath = $properties['backup_file']
          ?? ($properties['cascade_counts']['backup_file'] ?? null);

        if (!$backupPath || !File::exists($backupPath)) {
          return null;
        }

        $type = $this->backupTypeFromActivity($activity, $backupPath);
        $oldValues = (array) ($properties['old_values'] ?? []);
        $newValues = (array) ($properties['new_values'] ?? []);

        $label = match ($type) {
          'phase' => $oldValues['phase'] ?? ('Phase #' . ($properties['loan_phase_id'] ?? $activity->subject_id)),
          'application', 'application_edit' => $oldValues['group_name']
            ?? $newValues['group_name']
            ?? $oldValues['application_id']
            ?? $properties['application_id']
            ?? ('Application #' . ($properties['temp_application_id'] ?? $activity->subject_id)),
          default => $oldValues['group_name'] ?? ('Group loan #' . ($properties['group_loan_id'] ?? $activity->subject_id)),
        };

        return [
          'id' => md5($backupPath),
          'type' => $type,
          'backup_path' => $backupPath,
          'backup_file' => basename($backupPath),
          'deleted_at' => $activity->created_at->toDateTimeString(),
          'deleted_by' => $activity->causer?->name,
          'label' => (string) $label,
          'entity_id' => match ($type) {
            'phase' => $oldValues['id'] ?? $properties['loan_phase_id'] ?? null,
            'application', 'application_edit' => $properties['temp_application_id'] ?? $oldValues['application_id'] ?? null,
            default => $oldValues['group_loan_id'] ?? $properties['group_loan_id'] ?? null,
          },
          'activity_id' => $activity->id,
        ];
      })
      ->filter()
      ->unique('backup_path')
      ->values();
  }

  /**
   * @return list<string>
   */
  public function detectConflicts(string $backupPath): array
  {
    if (!File::exists($backupPath)) {
      return ['Backup file could not be found on disk.'];
    }

    if ($this->isReplaceRestore($backupPath)) {
      return [];
    }

    $sql = File::get($backupPath);
    $inserts = $this->parser->parseInserts($sql);
    $conflicts = [];

    foreach ($inserts['loan_phases'] ?? [] as $row) {
      $data = $this->mapRow('loan_phases', $row);
      $phaseId = $data['id'] ?? null;
      $slug = $data['slug'] ?? null;
      $constituencyCode = $data['constituency_code'] ?? null;
      $phaseName = $data['phase'] ?? null;

      if ($phaseId && LoanPhase::whereKey($phaseId)->exists()) {
        $conflicts[] = "Loan phase ID {$phaseId} already exists.";
      }

      if ($slug && LoanPhase::where('slug', $slug)->exists()) {
        $conflicts[] = "Loan phase slug \"{$slug}\" is already in use.";
      }

      if ($constituencyCode && $phaseName) {
        $nameConflict = LoanPhase::query()
          ->where('constituency_code', $constituencyCode)
          ->where('phase', $phaseName)
          ->when($phaseId, fn ($q) => $q->where('id', '!=', $phaseId))
          ->exists();

        if ($nameConflict) {
          $conflicts[] = "Phase name \"{$phaseName}\" already exists in constituency {$constituencyCode}.";
        }
      }
    }

    foreach ($inserts['group_loans'] ?? [] as $row) {
      $data = $this->mapRow('group_loans', $row);
      $groupLoanId = $data['id'] ?? null;
      $gfsCode = $data['gfs_code'] ?? null;

      if ($groupLoanId && GroupLoan::whereKey($groupLoanId)->exists()) {
        $conflicts[] = "Group loan ID {$groupLoanId} already exists.";
      }

      if ($gfsCode) {
        $gfsConflict = GroupLoan::query()
          ->where('gfs_code', $gfsCode)
          ->when($groupLoanId, fn ($q) => $q->where('id', '!=', $groupLoanId))
          ->exists();

        if ($gfsConflict) {
          $conflicts[] = "GFS code \"{$gfsCode}\" is already assigned to another group loan.";
        }
      }
    }

    foreach ($inserts['groups'] ?? [] as $row) {
      $data = $this->mapRow('groups', $row);
      $groupId = $data['id'] ?? null;
      $slug = $data['slug'] ?? null;
      $regNumber = $data['reg_number'] ?? null;

      if ($groupId && Group::whereKey($groupId)->exists()) {
        $conflicts[] = "Group ID {$groupId} already exists.";
      }

      if ($slug && Group::where('slug', $slug)->exists()) {
        $conflicts[] = "Group slug \"{$slug}\" is already in use.";
      }

      if ($regNumber && Group::where('reg_number', $regNumber)->exists()) {
        $conflicts[] = "Group registration number \"{$regNumber}\" is already in use.";
      }
    }

    foreach ($inserts['phase_applications'] ?? [] as $row) {
      $data = $this->mapRow('phase_applications', $row);
      $applicationId = $data['id'] ?? null;
      $loanPhaseId = $data['loan_phase_id'] ?? null;
      $tempApplicationId = $data['temp_application_id'] ?? null;

      if ($applicationId && PhaseApplication::whereKey($applicationId)->exists()) {
        $conflicts[] = "Phase application ID {$applicationId} already exists.";
      }

      if ($loanPhaseId && $tempApplicationId) {
        $pairExists = PhaseApplication::query()
          ->where('loan_phase_id', $loanPhaseId)
          ->where('temp_application_id', $tempApplicationId)
          ->when($applicationId, fn ($q) => $q->where('id', '!=', $applicationId))
          ->exists();

        if ($pairExists) {
          $conflicts[] = "Phase application for temp application #{$tempApplicationId} already exists on phase #{$loanPhaseId}.";
        }
      }
    }

    foreach ($inserts['temp_applications'] ?? [] as $row) {
      $data = $this->mapRow('temp_applications', $row);
      $tempId = $data['id'] ?? null;
      $applicationRef = $data['application_id'] ?? null;

      if ($tempId && TempApplication::whereKey($tempId)->exists()) {
        $conflicts[] = "Application record ID {$tempId} already exists.";
      }

      if ($applicationRef && TempApplication::where('application_id', $applicationRef)->exists()) {
        $conflicts[] = "Application reference \"{$applicationRef}\" is already in use.";
      }
    }

    return array_values(array_unique($conflicts));
  }

  /**
   * @return array{conflicts: list<string>, summary: array<string, int>}
   */
  public function previewRestore(string $backupPath): array
  {
    if (!File::exists($backupPath)) {
      throw ValidationException::withMessages([
        'restore' => 'Backup file could not be found on disk.',
      ]);
    }

    $inserts = $this->parser->parseInserts(File::get($backupPath));
    $summary = [];

    foreach ($inserts as $table => $rows) {
      $summary[$table] = count($rows);
    }

    return [
      'conflicts' => $this->detectConflicts($backupPath),
      'summary' => $summary,
    ];
  }

  public function restore(string $backupPath, User $user, string $constituencyCode): void
  {
    if (!File::exists($backupPath)) {
      throw ValidationException::withMessages([
        'restore' => 'Backup file could not be found on disk.',
      ]);
    }

    if (!$this->userCanRestore($user, $constituencyCode)) {
      throw ValidationException::withMessages([
        'restore' => 'You do not have permission to restore deleted data for this constituency.',
      ]);
    }

    $this->assertBackupBelongsToConstituency($backupPath, $constituencyCode);

    $conflicts = $this->detectConflicts($backupPath);
    if ($conflicts !== []) {
      throw ValidationException::withMessages([
        'restore' => $conflicts,
      ]);
    }

    $sql = File::get($backupPath);
    $statements = $this->orderedInsertStatements($sql);
    if ($this->isReplaceRestore($backupPath)) {
      $statements = $this->asReplaceStatements($statements);
    }

    if ($statements === []) {
      throw ValidationException::withMessages([
        'restore' => 'This backup file does not contain any restorable row data.',
      ]);
    }

    $restoredPhaseIds = $this->extractPhaseIdsFromSql($sql);

    DB::transaction(function () use ($statements, $backupPath, $user, $constituencyCode, $restoredPhaseIds) {
      DB::statement('SET FOREIGN_KEY_CHECKS=0');

      try {
        foreach ($statements as $statement) {
          DB::unprepared($statement);
        }
      } finally {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
      }

      foreach ($restoredPhaseIds as $phaseId) {
        $phase = LoanPhase::find($phaseId);
        if ($phase) {
          LoanPhaseFinancialTotals::refresh($phase);
        }
      }

      $this->phaseService->invalidatePhasesCache($constituencyCode);
      $this->cacheService->flushGroupLoansCache($constituencyCode);
      $this->cacheService->flashCache('applications', 'constituency_apps', $constituencyCode);
      $this->cacheService->flashCache('applications', 'constituency_stats', $constituencyCode);

      foreach ($restoredPhaseIds as $phaseId) {
        $this->phaseService->invalidatePhaseCache($phaseId);
      }

      $this->logRestore($user, $backupPath, $constituencyCode, $statements);
    });
  }

  protected function assertBackupBelongsToConstituency(string $backupPath, string $constituencyCode): void
  {
    $activity = Activity::query()
      ->whereIn('log_name', ['phase_audit', 'group_audit', 'application_audit'])
      ->whereIn('event', ['deleted', 'updated'])
      ->where('properties->constituency_code', $constituencyCode)
      ->where(function ($query) use ($backupPath) {
        $query->where('properties->backup_file', $backupPath)
          ->orWhere('properties->cascade_counts->backup_file', $backupPath);
      })
      ->exists();

    if (!$activity && !auth()->user()?->hasRole('super-admin')) {
      throw ValidationException::withMessages([
        'restore' => 'This backup does not belong to the selected constituency.',
      ]);
    }
  }

  /**
   * @return list<string>
   */
  protected function orderedInsertStatements(string $sql): array
  {
    $statements = $this->parser->extractInsertStatements($sql);
    $ordered = [];

    foreach (self::RESTORE_TABLE_ORDER as $table) {
      foreach ($statements as $statement) {
        if (preg_match('/INSERT\s+INTO\s+`' . preg_quote($table, '/') . '`/i', $statement)) {
          $ordered[] = $statement;
        }
      }
    }

    foreach ($statements as $statement) {
      if (!in_array($statement, $ordered, true)) {
        $ordered[] = $statement;
      }
    }

    return $ordered;
  }

  /**
   * @return list<int>
   */
  protected function extractPhaseIdsFromSql(string $sql): array
  {
    $inserts = $this->parser->parseInserts($sql);
    $ids = [];

    foreach ($inserts['loan_phases'] ?? [] as $row) {
      $data = $this->mapRow('loan_phases', $row);
      if (!empty($data['id'])) {
        $ids[] = (int) $data['id'];
      }
    }

    foreach ($inserts['group_loans'] ?? [] as $row) {
      $data = $this->mapRow('group_loans', $row);
      if (!empty($data['loan_phase_id'])) {
        $ids[] = (int) $data['loan_phase_id'];
      }
    }

    return array_values(array_unique($ids));
  }

  /**
   * @param  list<string>  $statements
   */
  protected function logRestore(User $user, string $backupPath, string $constituencyCode, array $statements): void
  {
    $type = $this->backupTypeFromPath($backupPath);
    $label = match ($type) {
      'application_edit' => 'application edit',
      'application' => 'application',
      'phase' => 'phase',
      default => 'group',
    };

    activity('restore_audit')
      ->causedBy($user)
      ->event('restored')
      ->withProperties([
        'action' => 'restored',
        'backup_file' => $backupPath,
        'backup_type' => $type,
        'constituency_code' => $constituencyCode,
        'statement_count' => count($statements),
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
      ])
      ->log("Restored {$label} data from backup.");
  }

  protected function isReplaceRestore(string $backupPath): bool
  {
    return str_contains($backupPath, 'application-edit');
  }

  /**
   * @param  list<string>  $statements
   * @return list<string>
   */
  protected function asReplaceStatements(array $statements): array
  {
    return array_map(
      fn (string $statement) => preg_replace('/^INSERT\s+INTO/i', 'REPLACE INTO', $statement) ?? $statement,
      $statements,
    );
  }

  protected function backupTypeFromPath(string $backupPath): string
  {
    return match (true) {
      str_contains($backupPath, 'application-edit') => 'application_edit',
      str_contains($backupPath, 'application-delete') => 'application',
      str_contains($backupPath, 'phase-delete') => 'phase',
      default => 'group',
    };
  }

  protected function backupTypeFromActivity(Activity $activity, string $backupPath): string
  {
    if ($activity->log_name === 'application_audit') {
      return $activity->event === 'updated' ? 'application_edit' : 'application';
    }

    if ($activity->log_name === 'phase_audit') {
      return 'phase';
    }

    return $this->backupTypeFromPath($backupPath);
  }

  /**
   * @param  list<string|null>  $row
   * @return array<string, string|null>
   */
  protected function mapRow(string $table, array $row): array
  {
    $columns = Schema::getColumnListing($table);
    $mapped = [];

    foreach ($columns as $index => $column) {
      if (!array_key_exists($index, $row)) {
        break;
      }

      $mapped[$column] = $row[$index];
    }

    return $mapped;
  }
}
