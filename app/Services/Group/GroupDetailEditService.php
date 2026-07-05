<?php

namespace App\Services\Group;

use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\Cache\AppCacheService;
use App\Services\Loan\LoanPhaseService;
use App\Support\LoanPhaseFinancialTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroupDetailEditService
{
    public function __construct(
        protected LoanPhaseService $phaseService,
        protected AppCacheService $cacheService,
    ) {}

    public function canEditGroupLoan(GroupLoan $groupLoan, User $user): bool
    {
        $groupLoan->loadMissing('loanPhase');

        return $groupLoan->loanPhase !== null
            && $groupLoan->loanPhase->canEditDetailsBy($user);
    }

    /**
     * @return list<string>
     */
    public function editableFieldsFor(GroupLoan $groupLoan): array
    {
        $phase = $groupLoan->loanPhase;

        if (!$phase) {
            return [];
        }

        if ($phase->isHistoricalUpload()) {
            return [
                'group_name',
                'ward_id',
                'gfs_code',
                'amount_applied',
                'amount_approved',
                'amount_issued',
                'amount_repaid',
                'disbursement_date',
            ];
        }

        if ($phase->status === 'draft') {
            return [
                'group_name',
                'ward_id',
                'amount_applied',
                'amount_approved',
                'gfs_code',
            ];
        }

        if ($phase->canEditCompletedDetails()) {
            return [
                'group_name',
                'ward_id',
                'amount_issued',
                'amount_repaid',
                'disbursement_date',
                'gfs_code',
            ];
        }

        return [];
    }

    public function updateDetails(GroupLoan $groupLoan, User $user, array $data): GroupLoan
    {
        if (!$this->canEditGroupLoan($groupLoan, $user)) {
            throw ValidationException::withMessages([
                'groupName' => 'You do not have permission to edit this group.',
            ]);
        }

        $allowed = $this->editableFieldsFor($groupLoan);
        if ($allowed === []) {
            throw ValidationException::withMessages([
                'groupName' => 'This group cannot be edited in its current phase state.',
            ]);
        }

        $groupLoan->load(['group.ward', 'loanPhase']);
        $group = $groupLoan->group;
        $phase = $groupLoan->loanPhase;

        if (!$group || !$phase) {
            throw ValidationException::withMessages([
                'groupName' => 'Group record could not be found.',
            ]);
        }

        $payload = $this->validatePayload($groupLoan, $phase, $data, $allowed);
        $oldValues = $this->buildSnapshot($groupLoan, $group);

        DB::transaction(function () use ($groupLoan, $group, $phase, $user, $payload, $oldValues) {
            $groupUpdates = [];
            $loanUpdates = [];

            if (isset($payload['group_name'])) {
                $groupUpdates['name'] = $payload['group_name'];
            }

            if (isset($payload['ward_id'])) {
                $groupUpdates['ward_id'] = $payload['ward_id'];
            }

            if (isset($payload['gfs_code'])) {
                $loanUpdates['gfs_code'] = $payload['gfs_code'];
            }

            foreach (['amount_applied', 'amount_approved', 'amount_issued', 'amount_repaid', 'disbursement_date'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $loanUpdates[$field] = $payload[$field];
                }
            }

            if ($groupUpdates !== []) {
                $group->update($groupUpdates);
            }

            if ($loanUpdates !== []) {
                $groupLoan->update($loanUpdates);
            }

            $groupLoan->refresh();
            $group->refresh();

            $this->logAudit(
                $groupLoan,
                $group,
                $phase,
                $user,
                'updated',
                $oldValues,
                $this->buildSnapshot($groupLoan, $group),
            );

            LoanPhaseFinancialTotals::refresh($phase);
            $this->phaseService->invalidatePhasesCache($phase->constituency_code);
            $this->phaseService->invalidatePhaseCache($phase->id);
            $this->cacheService->flushGroupLoansCache($phase->constituency_code);
        });

        return $groupLoan->fresh(['group.ward', 'loanPhase']);
    }

    public function deleteWithAudit(GroupLoan $groupLoan, User $user): void
    {
        if (!$this->canEditGroupLoan($groupLoan, $user)) {
            throw ValidationException::withMessages([
                'group' => 'You do not have permission to delete this group.',
            ]);
        }

        if ($groupLoan->phaseApplication()->exists()) {
            throw ValidationException::withMessages([
                'group' => 'This group is linked to an application workflow and cannot be deleted.',
            ]);
        }

        $groupLoan->load(['group', 'loanPhase']);
        $group = $groupLoan->group;
        $phase = $groupLoan->loanPhase;

        if (!$phase) {
            throw ValidationException::withMessages([
                'group' => 'The loan phase for this group could not be found.',
            ]);
        }

        $snapshot = $this->buildSnapshot($groupLoan, $group);

        DB::transaction(function () use ($groupLoan, $group, $phase, $user, $snapshot) {
            $this->logAudit($groupLoan, $group, $phase, $user, 'deleted', $snapshot, []);

            GroupMember::where('group_loans_id', $groupLoan->id)->delete();

            $groupId = $groupLoan->group_id;
            $constituencyCode = $phase->constituency_code;

            $groupLoan->delete();

            if ($groupId && !GroupLoan::where('group_id', $groupId)->exists()) {
                Group::whereKey($groupId)->delete();
            }

            LoanPhaseFinancialTotals::refresh($phase);
            $this->phaseService->invalidatePhasesCache($constituencyCode);
            $this->phaseService->invalidatePhaseCache($phase->id);
            $this->cacheService->flushGroupLoansCache($constituencyCode);
        });
    }

    /**
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    protected function validatePayload(GroupLoan $groupLoan, $phase, array $data, array $allowed): array
    {
        $rules = [];
        $attributes = [];

        if (in_array('group_name', $allowed, true)) {
            $rules['groupName'] = ['required', 'string', 'max:255'];
            $attributes['groupName'] = 'group name';
        }

        if (in_array('ward_id', $allowed, true)) {
            $rules['wardId'] = [
                'required',
                'integer',
                Rule::exists('wards', 'id')->where('constituency_code', $phase->constituency_code),
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
            'wardId' => 'ward_id',
            'gfsCode' => 'gfs_code',
            'amountApplied' => 'amount_applied',
            'amountApproved' => 'amount_approved',
            'amountIssued' => 'amount_issued',
            'amountRepaid' => 'amount_repaid',
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

            if ($column === 'group_name' && is_string($value)) {
                $value = trim($value);
            }

            $payload[$column] = $value;
        }

        return $payload;
    }

    protected function buildSnapshot(?GroupLoan $groupLoan, ?Group $group): array
    {
        return [
            'group_loan_id' => $groupLoan?->id,
            'loan_phase_id' => $groupLoan?->loan_phase_id,
            'group_id' => $group?->id,
            'group_name' => $group?->name,
            'ward_id' => $group?->ward_id,
            'ward_name' => $group?->ward?->ward_name,
            'gfs_code' => $groupLoan?->gfs_code,
            'amount_applied' => $groupLoan?->amount_applied,
            'amount_approved' => $groupLoan?->amount_approved,
            'amount_issued' => $groupLoan?->amount_issued,
            'amount_repaid' => $groupLoan?->amount_repaid,
            'disbursement_date' => $groupLoan?->disbursement_date?->format('Y-m-d'),
        ];
    }

    protected function logAudit(
        GroupLoan $groupLoan,
        ?Group $group,
        $phase,
        User $user,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $groupName = $group?->name ?? 'Group';

        activity('group_audit')
            ->performedOn($groupLoan)
            ->causedBy($user)
            ->event($action)
            ->withProperties([
                'action' => $action,
                'model' => GroupLoan::class,
                'group_loan_id' => $groupLoan->id,
                'group_id' => $group?->id,
                'loan_phase_id' => $phase->id,
                'constituency_code' => $phase->constituency_code,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log(match ($action) {
                'deleted' => "Group \"{$groupName}\" was removed from phase \"{$phase->phase}\".",
                default => "Group \"{$groupName}\" details were updated.",
            });
    }
}
