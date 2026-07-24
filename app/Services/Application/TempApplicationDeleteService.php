<?php

namespace App\Services\Application;

use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\LoanPhase;
use App\Models\LoanReceipts;
use App\Models\PhaseComment;
use App\Models\TempApplication;
use App\Models\User;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Support\LmsGracePeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TempApplicationDeleteService
{
    public function __construct(
        protected TempApplicationDeleteBackupService $backupService,
        protected TempApplicationService $applicationService,
        protected AppCacheService $cacheService,
        protected LoanPhaseService $phaseService,
    ) {}

    public function canDelete(TempApplication $application, User $user): bool
    {
        if (!$this->userCanManageConstituencyApplication($application, $user)) {
            return false;
        }

        if (!$this->userHasDeletePermission($user)) {
            return false;
        }

        if ($application->phaseApplication()->exists()) {
            return LmsGracePeriod::isActive();
        }

        return true;
    }

    protected function userHasDeletePermission(User $user): bool
    {
        return $user->can('delete loan application')
            || $user->can('review application')
            || $user->can('submit application');
    }

    protected function userCanManageConstituencyApplication(TempApplication $application, User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (!$user->hasAnyRole(['constituency_officer', 'intern'])) {
            return false;
        }

        if ($application->constituency_code === null) {
            return false;
        }

        return $user->constituencies->contains('constituency_code', $application->constituency_code);
    }

    public function deleteWithAudit(TempApplication $application, User $user): void
    {
        if (!$this->canDelete($application, $user)) {
            throw ValidationException::withMessages([
                'application' => 'You do not have permission to delete this application, or the grace period for deleting assigned applications has ended.',
            ]);
        }

        $application->loadMissing(['phaseApplication.loanPhase', 'phaseApplication.groupLoan']);

        $backup = $this->backupService->backup($application);

        $phaseApplication = $application->phaseApplication;
        $phaseSnapshot = $phaseApplication ? [
            'phase_application_id' => $phaseApplication->id,
            'loan_phase_id' => $phaseApplication->loan_phase_id,
            'phase_name' => $phaseApplication->loanPhase?->phase,
            'phase_status' => $phaseApplication->loanPhase?->status,
            'application_status' => $phaseApplication->status,
            'group_loan_id' => $phaseApplication->group_loan_id,
        ] : null;

        $snapshot = [
            'application_id' => $application->application_id,
            'group_name' => $application->group_name,
            'constituency_code' => $application->constituency_code,
            'status' => $application->status,
            'loan_amount' => $application->loan_amount,
            'owner_identifier' => $application->owner_identifier,
            'submitted_at' => $application->created_at?->format('Y-m-d H:i'),
            'phase_assignment' => $phaseSnapshot,
            'grace_period_active' => LmsGracePeriod::isActive(),
        ];

        $constituencyCode = $application->constituency_code;
        $applicationUuid = $application->application_id;
        $applicationId = $application->id;

        DB::transaction(function () use ($application, $user, $snapshot, $backup, $constituencyCode, $applicationUuid, $applicationId) {
            $phase = $this->detachAndCascadePhaseLinks($application);

            activity('application_audit')
                ->performedOn($application)
                ->causedBy($user)
                ->event('deleted')
                ->withProperties([
                    'action' => 'deleted',
                    'model' => TempApplication::class,
                    'temp_application_id' => $applicationId,
                    'application_id' => $applicationUuid,
                    'constituency_code' => $constituencyCode,
                    'old_values' => $snapshot,
                    'new_values' => [],
                    'old' => $snapshot,
                    'backup_file' => $backup['path'],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log(sprintf(
                    'Application "%s" (%s) was deleted.%s A backup was saved.',
                    $snapshot['group_name'] ?: $applicationUuid,
                    $applicationUuid,
                    $phase ? ' Phase links were removed.' : '',
                ));

            $application->delete();

            if ($phase) {
                $phase->calculateStats();
                $this->phaseService->invalidatePhasesCache($phase->constituency_code);
                $this->phaseService->invalidatePhaseCache($phase->id);
            }

            if ($constituencyCode) {
                $this->cacheService->flashCache('applications', 'constituency_apps', $constituencyCode);
                $this->cacheService->flashCache('applications', 'constituency_stats', $constituencyCode);
                $this->cacheService->flushGroupLoansCache($constituencyCode);
            }

            $this->applicationService->invalidateApplicationCache($applicationUuid);
        });
    }

    protected function detachAndCascadePhaseLinks(TempApplication $application): ?LoanPhase
    {
        $phaseApplication = $application->phaseApplication;

        if (!$phaseApplication) {
            return null;
        }

        $phase = $phaseApplication->loanPhase;
        $groupLoanId = $phaseApplication->group_loan_id;

        PhaseComment::query()
            ->where('phase_application_id', $phaseApplication->id)
            ->delete();

        $phaseApplication->delete();

        if ($groupLoanId) {
            LoanReceipts::query()->where('group_loans_id', $groupLoanId)->delete();
            GroupMember::query()->where('group_loans_id', $groupLoanId)->delete();

            $groupLoan = GroupLoan::query()->find($groupLoanId);

            if ($groupLoan) {
                $groupId = $groupLoan->group_id;
                $groupLoan->delete();

                if ($groupId && !GroupLoan::query()->where('group_id', $groupId)->exists()) {
                    Group::query()->whereKey($groupId)->delete();
                }
            }
        }

        return $phase;
    }
}
