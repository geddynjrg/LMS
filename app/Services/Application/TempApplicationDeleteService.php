<?php

namespace App\Services\Application;

use App\Models\TempApplication;
use App\Models\User;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\TempApplicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TempApplicationDeleteService
{
    public function __construct(
        protected TempApplicationDeleteBackupService $backupService,
        protected TempApplicationService $applicationService,
        protected AppCacheService $cacheService,
    ) {}

    public function canDelete(TempApplication $application, User $user): bool
    {
        if (!$user->can('delete loan application')) {
            return false;
        }

        if ($application->phaseApplication()->exists()) {
            return false;
        }

        if ($application->constituency_code !== null) {
            return $user->hasRole('super-admin')
                || $user->constituencies->contains('constituency_code', $application->constituency_code);
        }

        return $user->hasRole('super-admin');
    }

    public function deleteWithAudit(TempApplication $application, User $user): void
    {
        if (!$this->canDelete($application, $user)) {
            throw ValidationException::withMessages([
                'application' => 'You do not have permission to delete this application, or it is still assigned to a phase.',
            ]);
        }

        if ($application->phaseApplication()->exists()) {
            throw ValidationException::withMessages([
                'application' => 'Remove this application from its phase before deleting it.',
            ]);
        }

        $backup = $this->backupService->backup($application);

        $snapshot = [
            'application_id' => $application->application_id,
            'group_name' => $application->group_name,
            'constituency_code' => $application->constituency_code,
            'status' => $application->status,
            'loan_amount' => $application->loan_amount,
            'owner_identifier' => $application->owner_identifier,
            'submitted_at' => $application->created_at?->format('Y-m-d H:i'),
        ];

        $constituencyCode = $application->constituency_code;
        $applicationUuid = $application->application_id;
        $applicationId = $application->id;

        DB::transaction(function () use ($application, $user, $snapshot, $backup, $constituencyCode, $applicationUuid, $applicationId) {
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
                    'Application "%s" (%s) was deleted. A backup was saved.',
                    $snapshot['group_name'] ?: $applicationUuid,
                    $applicationUuid,
                ));

            $application->delete();

            if ($constituencyCode) {
                $this->cacheService->flashCache('applications', 'constituency_apps', $constituencyCode);
                $this->cacheService->flashCache('applications', 'constituency_stats', $constituencyCode);
            }

            $this->applicationService->invalidateApplicationCache($applicationUuid);
        });
    }
}
