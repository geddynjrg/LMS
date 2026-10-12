<?php

namespace App\Services\Application;

use App\Models\Group;
use App\Models\TempApplication;
use App\Models\User;
use App\Models\Wards;
use App\Services\Application\TempApplicationEditBackupService;
use App\Services\Cache\AppCacheService;
use App\Services\Group\GroupDetailEditService;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Support\LmsGracePeriod;
use App\Support\LoanPhaseFinancialTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TempApplicationDetailEditService
{
    public function __construct(
        protected GroupDetailEditService $groupEditService,
        protected TempApplicationService $applicationService,
        protected LoanPhaseService $phaseService,
        protected AppCacheService $cacheService,
        protected TempApplicationEditBackupService $backupService,
    ) {}

    public function canEdit(TempApplication $application, User $user): bool
    {
        if (!$this->userCanManageConstituencyApplication($application, $user)) {
            return false;
        }

        if (!$this->userHasEditPermission($user)) {
            return false;
        }

        $phase = $application->phaseApplication?->reviewPhase
            ?? $application->phaseApplication?->loanPhase;

        if ($phase === null) {
            return true;
        }

        if (LmsGracePeriod::isActive()) {
            return true;
        }

        return $phase->canEditDetailsBy($user);
    }

    /**
     * @return list<string>
     */
    public function editableFieldsFor(TempApplication $application): array
    {
        $fields = [
            'group_name',
            'group_registration_number',
            'ward_id',
            'gfs_code',
            'amount_applied',
            'date_registered',
            'disbursement_date',
        ];

        $groupLoan = $application->phaseApplication?->groupLoan;

        if ($groupLoan) {
            $fields = array_values(array_unique(array_merge(
                $fields,
                $this->groupEditService->editableFieldsFor($groupLoan),
            )));
        }

        // Approved and issued amounts are set only during approval / disbursement.
        $fields = array_values(array_diff($fields, ['amount_approved', 'amount_issued']));

        return $fields;
    }

    public function updateDetails(TempApplication $application, User $user, array $data): TempApplication
    {
        if (!$this->canEdit($application, $user)) {
            throw ValidationException::withMessages([
                'groupName' => 'You do not have permission to edit this application.',
            ]);
        }

        $application->loadMissing([
            'ward',
            'phaseApplication.reviewPhase',
            'phaseApplication.groupLoan.group.ward',
            'phaseApplication.linkedGroup',
        ]);

        $allowed = $this->editableFieldsFor($application);
        if ($allowed === []) {
            throw ValidationException::withMessages([
                'groupName' => 'This application cannot be edited in its current state.',
            ]);
        }

        $payload = $this->validatePayload($application, $data, $allowed);
        $oldValues = $this->buildSnapshot($application);
        $backup = $this->backupService->backup($application);

        DB::transaction(function () use ($application, $user, $payload, $oldValues, $backup) {
            $this->applyApplicationUpdates($application, $payload);
            $this->applyPhaseApplicationUpdates($application, $payload);
            $this->applyLinkedGroupUpdates($application, $user, $payload);

            $application->refresh()->load([
                'ward',
                'phaseApplication.reviewPhase',
                'phaseApplication.groupLoan.group.ward',
                'phaseApplication.linkedGroup',
            ]);

            $this->logAudit($application, $user, $oldValues, $this->buildSnapshot($application), $backup['path']);
            $this->invalidateCaches($application);
        });

        return $application;
    }

    protected function userHasEditPermission(User $user): bool
    {
        return $user->can('update loan application')
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

    /**
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    protected function validatePayload(TempApplication $application, array $data, array $allowed): array
    {
        $rules = [];
        $attributes = [];
        $constituencyCode = $application->constituency_code;

        if (in_array('group_name', $allowed, true)) {
            $rules['groupName'] = ['required', 'string', 'max:255'];
            $attributes['groupName'] = 'group name';
        }

        if (in_array('group_registration_number', $allowed, true)) {
            $rules['groupRegistrationNumber'] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('temp_applications', 'group_registration_number')
                    ->where('constituency_code', $constituencyCode)
                    ->ignore($application->id),
            ];
            $attributes['groupRegistrationNumber'] = 'group registration number';
        }

        if (in_array('ward_id', $allowed, true)) {
            $rules['wardId'] = [
                'nullable',
                'integer',
                Rule::exists('wards', 'id')->where('constituency_code', $constituencyCode),
            ];
            $attributes['wardId'] = 'ward';
        }

        if (in_array('gfs_code', $allowed, true)) {
            $rules['gfsCode'] = ['nullable', 'string', 'max:255'];
            $attributes['gfsCode'] = 'GFS code';
        }

        foreach (['amount_applied', 'amount_approved', 'amount_issued', 'amount_repaid'] as $field) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }

            $input = match ($field) {
                'amount_applied' => 'amountApplied',
                'amount_approved' => 'amountApproved',
                'amount_issued' => 'amountIssued',
                'amount_repaid' => 'amountRepaid',
            };

            $rules[$input] = ['nullable', 'numeric', 'min:0'];
            $attributes[$input] = str_replace('_', ' ', $field);
        }

        if (in_array('date_registered', $allowed, true)) {
            $rules['dateRegistered'] = ['nullable', 'date'];
            $attributes['dateRegistered'] = 'date registered';
        }

        if (in_array('disbursement_date', $allowed, true)) {
            $rules['disbursementDate'] = ['nullable', 'date'];
            $attributes['disbursementDate'] = 'disbursement date';
        }

        $validator = validator($data, $rules, [], $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $payload = [];

        $map = [
            'groupName' => 'group_name',
            'groupRegistrationNumber' => 'group_registration_number',
            'wardId' => 'ward_id',
            'gfsCode' => 'gfs_code',
            'amountApplied' => 'amount_applied',
            'amountApproved' => 'amount_approved',
            'amountIssued' => 'amount_issued',
            'amountRepaid' => 'amount_repaid',
            'dateRegistered' => 'date_registered',
            'disbursementDate' => 'disbursement_date',
        ];

        foreach ($map as $input => $column) {
            if (!in_array($column, $allowed, true) || !array_key_exists($input, $validated)) {
                continue;
            }

            $value = $validated[$input];
            if ($value === '') {
                $value = null;
            }

            if (in_array($column, ['group_name', 'group_registration_number'], true) && is_string($value)) {
                $value = trim($value);
            }

            $payload[$column] = $value;
        }

        $this->assertRegistrationNumberAvailable($application, $payload);

        return $payload;
    }

    protected function assertRegistrationNumberAvailable(TempApplication $application, array $payload): void
    {
        if (!array_key_exists('group_registration_number', $payload) || !$payload['group_registration_number']) {
            return;
        }

        $groupId = $application->phaseApplication?->group_id
            ?? $application->phaseApplication?->groupLoan?->group_id
            ?? $application->group_id;

        $taken = Group::query()
            ->where('reg_number', $payload['group_registration_number'])
            ->when($groupId, fn ($query) => $query->where('id', '!=', $groupId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'groupRegistrationNumber' => 'This group registration number is already in use.',
            ]);
        }
    }

    protected function applyApplicationUpdates(TempApplication $application, array $payload): void
    {
        $updates = [];

        if (isset($payload['group_name'])) {
            $updates['group_name'] = $payload['group_name'];
        }

        if (array_key_exists('group_registration_number', $payload)) {
            $updates['group_registration_number'] = $payload['group_registration_number'];
        }

        if (array_key_exists('ward_id', $payload)) {
            $updates['ward_id'] = $payload['ward_id'];
            $updates['ward_name'] = $payload['ward_id']
                ? Wards::query()->whereKey($payload['ward_id'])->value('ward_name')
                : null;
        }

        if (array_key_exists('amount_applied', $payload)) {
            $updates['loan_amount'] = $payload['amount_applied'];
        }

        if (array_key_exists('date_registered', $payload)) {
            $updates['date_registered'] = $payload['date_registered'];
        }

        $updates = $this->onlyExistingColumns('temp_applications', $updates);

        if ($updates !== []) {
            $application->update($updates);
        }
    }

    protected function applyPhaseApplicationUpdates(TempApplication $application, array $payload): void
    {
        $phaseApplication = $application->phaseApplication;

        if (!$phaseApplication) {
            return;
        }

        $updates = [];

        foreach (['gfs_code', 'amount_applied', 'disbursement_date'] as $field) {
            if (array_key_exists($field, $payload)) {
                $updates[$field] = $payload[$field];
            }
        }

        $updates = $this->onlyExistingColumns('phase_applications', $updates);

        if ($updates !== []) {
            $phaseApplication->update($updates);
        }
    }

    protected function applyLinkedGroupUpdates(TempApplication $application, User $user, array $payload): void
    {
        $groupLoan = $application->phaseApplication?->groupLoan;

        $wardId = $payload['ward_id'] ?? $groupLoan?->group?->ward_id;

        if ($groupLoan && $wardId && $this->groupEditService->canEditGroupLoan($groupLoan, $user)) {
            $this->groupEditService->updateDetails($groupLoan, $user, [
                'groupName' => $payload['group_name'] ?? $groupLoan->group?->name,
                'wardId' => $wardId,
                'gfsCode' => array_key_exists('gfs_code', $payload) ? $payload['gfs_code'] : $groupLoan->gfs_code,
                'amountApplied' => array_key_exists('amount_applied', $payload) ? $payload['amount_applied'] : $groupLoan->amount_applied,
                'amountRepaid' => array_key_exists('amount_repaid', $payload) ? $payload['amount_repaid'] : $groupLoan->amount_repaid,
                'disbursementDate' => array_key_exists('disbursement_date', $payload)
                    ? $payload['disbursement_date']
                    : $groupLoan->disbursement_date?->format('Y-m-d'),
            ]);

            if (array_key_exists('group_registration_number', $payload) && $groupLoan->group) {
                $groupLoan->group->update([
                    'reg_number' => $payload['group_registration_number'],
                ]);
            }

            $this->applyGroupLoanAmountUpdates($groupLoan, $payload);
            return;
        }

        $this->applyGroupLoanAmountUpdates($groupLoan, $payload);

        $group = $application->phaseApplication?->linkedGroup
            ?? ($application->group_id ? Group::query()->find($application->group_id) : null);

        if (!$group) {
            return;
        }

        $updates = [];

        if (isset($payload['group_name'])) {
            $updates['name'] = $payload['group_name'];
        }

        if (array_key_exists('group_registration_number', $payload)) {
            $updates['reg_number'] = $payload['group_registration_number'];
        }

        if (array_key_exists('ward_id', $payload)) {
            $updates['ward_id'] = $payload['ward_id'];
        }

        if (array_key_exists('date_registered', $payload)) {
            $updates['date_registered'] = $payload['date_registered'];
        }

        $updates = $this->onlyExistingColumns('groups', $updates);

        if ($updates !== []) {
            $group->update($updates);
        }
    }


    protected function applyGroupLoanAmountUpdates($groupLoan, array $payload): void
    {
        if (!$groupLoan) {
            return;
        }

        $loanUpdates = [];
        foreach (['gfs_code', 'amount_applied', 'amount_repaid', 'disbursement_date'] as $field) {
            if (array_key_exists($field, $payload)) {
                $loanUpdates[$field] = $payload[$field];
            }
        }

        $loanUpdates = $this->onlyExistingColumns('group_loans', $loanUpdates);

        if ($loanUpdates === []) {
            return;
        }

        $groupLoan->update($loanUpdates);
        $groupLoan->loadMissing('loanPhase');

        if ($groupLoan->loanPhase) {
            LoanPhaseFinancialTotals::refresh($groupLoan->loanPhase);
        }
    }

    protected function buildSnapshot(TempApplication $application): array
    {
        $phaseApplication = $application->phaseApplication;
        $groupLoan = $phaseApplication?->groupLoan;
        $group = $phaseApplication?->linkedGroup ?? $groupLoan?->group;

        return [
            'application_id' => $application->application_id,
            'group_name' => $application->group_name,
            'group_registration_number' => $application->group_registration_number ?? $group?->reg_number,
            'ward_id' => $application->ward_id,
            'ward_name' => $application->ward_name ?? $application->ward?->ward_name,
            'gfs_code' => $phaseApplication?->gfs_code ?? $groupLoan?->gfs_code,
            'loan_amount' => $application->loan_amount,
            'amount_approved' => $application->amount_approved ?? $phaseApplication?->amount_approved,
            'amount_issued' => $phaseApplication?->amount_issued ?? $groupLoan?->amount_issued,
            'amount_repaid' => $groupLoan?->amount_repaid,
            'date_registered' => $application->date_registered?->format('Y-m-d'),
            'disbursement_date' => optional($phaseApplication?->disbursement_date ?? $groupLoan?->disbursement_date)->format('Y-m-d'),
            'phase_application_id' => $phaseApplication?->id,
            'group_loan_id' => $groupLoan?->id,
            'group_id' => $group?->id,
        ];
    }

    protected function logAudit(TempApplication $application, User $user, array $oldValues, array $newValues, ?string $backupFile = null): void
    {
        activity('application_audit')
            ->performedOn($application)
            ->causedBy($user)
            ->event('updated')
            ->withProperties([
                'action' => 'updated',
                'model' => TempApplication::class,
                'temp_application_id' => $application->id,
                'application_id' => $application->application_id,
                'constituency_code' => $application->constituency_code,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'backup_file' => $backupFile,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Application {$application->application_id} details were updated. A backup was saved.");
    }

    protected function invalidateCaches(TempApplication $application): void
    {
        $this->applicationService->invalidateApplicationCache(
            $application->application_id,
            $application->group_id
        );
        $this->applicationService->invalidateStatsCache((string) $application->constituency_code);
        $this->applicationService->forgetApplication($application->application_id);
        $this->cacheService->flashCache('applications', 'constituency_apps', $application->constituency_code);
        $this->cacheService->flashCache('applications', 'constituency_stats', $application->constituency_code);

        $phase = $application->phaseApplication?->reviewPhase;
        if ($phase) {
            $this->phaseService->invalidatePhasesCache($phase->constituency_code);
            $this->phaseService->invalidatePhaseCache($phase->id);
            $this->cacheService->flushGroupLoansCache($phase->constituency_code);
        }
    }
    /**
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>
     */
    protected function onlyExistingColumns(string $table, array $updates): array
    {
        $filtered = [];

        foreach ($updates as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}
