<?php

namespace App\Services\Application;

use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\LoanReceipts;
use App\Models\User;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanPhaseService;
use App\Support\LoanPhaseFinancialTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationDeleteService
{
  public function __construct(
    protected ApplicationDeleteBackupService $backupService,
    protected LoanPhaseService $phaseService,
    protected AppCacheService $cacheService,
  ) {}

  public function deleteWithAudit(GroupLoan $groupLoan, User $user): void
  {
    $groupLoan->load(['group', 'loanPhase']);

    if (!$user->can('delete loan application')) {
      throw ValidationException::withMessages([
        'application' => 'You do not have permission to delete this application.',
      ]);
    }

    $phase = $groupLoan->loanPhase;
    $group = $groupLoan->group;

    if (!$phase) {
      throw ValidationException::withMessages([
        'application' => 'The loan phase for this application could not be found.',
      ]);
    }

    $backup = $this->backupService->backup($groupLoan);

    $snapshot = [
      'group_loan_id' => $groupLoan->id,
      'loan_phase_id' => $groupLoan->loan_phase_id,
      'group_id' => $group?->id,
      'group_name' => $group?->name,
      'level' => $groupLoan->level,
    ];

    DB::transaction(function () use ($groupLoan, $group, $phase, $user, $snapshot, $backup) {
      $constituencyCode = $phase->constituency_code;
      $groupId = $groupLoan->group_id;
      $deleteGroup = $groupLoan->level === 'New';

      activity('group_audit')
        ->performedOn($groupLoan)
        ->causedBy($user)
        ->event('deleted')
        ->withProperties([
          'action' => 'deleted',
          'model' => GroupLoan::class,
          'group_loan_id' => $groupLoan->id,
          'group_id' => $group?->id,
          'loan_phase_id' => $phase->id,
          'constituency_code' => $constituencyCode,
          'old_values' => $snapshot,
          'new_values' => [],
          'backup_file' => $backup['path'],
          'source' => 'legacy_application_delete',
          'ip_address' => request()->ip(),
          'user_agent' => request()->userAgent(),
        ])
        ->log(
          'Application "' . ($group?->name ?? 'Group') . '" was deleted. A backup was saved.',
        );

      LoanReceipts::query()->where('group_loans_id', $groupLoan->id)->delete();
      GroupMember::query()->where('group_loans_id', $groupLoan->id)->delete();
      $groupLoan->delete();

      if ($deleteGroup && $groupId && !GroupLoan::where('group_id', $groupId)->exists()) {
        Group::whereKey($groupId)->delete();
      }

      LoanPhaseFinancialTotals::refresh($phase);
      $this->phaseService->invalidatePhasesCache($constituencyCode);
      $this->phaseService->invalidatePhaseCache($phase->id);
      $this->cacheService->flushGroupLoansCache($constituencyCode);
    });
  }
}
