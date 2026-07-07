<?php

namespace App\Services\Loan;

use App\Models\CommitteeMember;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\GroupMember;
use App\Models\HqReview;
use App\Models\LoanPhase;
use App\Models\LoanReceipts;
use App\Models\PhaseApplicationDocument;
use App\Models\PhaseComment;
use App\Models\PhaseDocument;
use App\Models\SecretariatApproval;
use App\Models\User;
use App\Services\Cache\AppCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhaseDetailEditService
{
    public function __construct(
        protected LoanPhaseService $phaseService,
        protected PhaseDeleteBackupService $backupService,
        protected AppCacheService $cacheService,
    ) {}

    /**
     * @return list<string>
     */
    public function editableFieldsFor(LoanPhase $phase): array
    {
        if ($phase->isHistoricalUpload()) {
            return [
                'phase',
                'review_date',
                'review_venue',
                'amount_disbursed',
                'amount_repaid',
                'disbursed_at',
                'training_start_date',
                'training_end_date',
                'training_venue',
                'training_notes',
            ];
        }

        if ($phase->status === 'draft') {
            return ['phase', 'review_date', 'review_venue'];
        }

        if ($phase->canEditCompletedDetails()) {
            return [
                'review_date',
                'review_venue',
                'disbursed_at',
                'training_start_date',
                'training_end_date',
                'training_venue',
                'training_notes',
            ];
        }

        return [];
    }

    public function updateDetails(LoanPhase $phase, User $user, array $data): LoanPhase
    {
        if (!$phase->canEditDetailsBy($user)) {
            throw ValidationException::withMessages([
                'phase' => 'You do not have permission to edit this phase.',
            ]);
        }

        $allowed = $this->editableFieldsFor($phase);
        if ($allowed === []) {
            throw ValidationException::withMessages([
                'phase' => 'This phase cannot be edited in its current state.',
            ]);
        }

        $payload = $this->validatePayload($phase, $data, $allowed);
        $oldValues = $phase->only(array_keys($payload));

        DB::transaction(function () use ($phase, $payload, $user, $oldValues) {
            if (isset($payload['phase'])) {
                $payload['phase_name'] = $payload['phase'];
            }

            $phase->update($payload);

            $this->logAudit(
                $phase,
                $user,
                'updated',
                $oldValues,
                $phase->only(array_keys($payload)),
            );

            $this->phaseService->invalidatePhasesCache($phase->constituency_code);
            $this->phaseService->invalidatePhaseCache($phase->id);
        });

        return $phase->fresh();
    }

    /**
     * @return array{group_loans: int, group_members: int, groups: int, loan_receipts: int, backup_file: string}
     */
    public function deletePreviewCounts(LoanPhase $phase): array
    {
        $groupLoanIds = GroupLoan::query()
            ->where('loan_phase_id', $phase->id)
            ->pluck('id');

        $groupIds = GroupLoan::query()
            ->where('loan_phase_id', $phase->id)
            ->whereNotNull('group_id')
            ->pluck('group_id')
            ->unique();

        $orphanedGroupCount = $groupIds->filter(function ($groupId) use ($phase) {
            return !GroupLoan::query()
                ->where('group_id', $groupId)
                ->where('loan_phase_id', '!=', $phase->id)
                ->exists();
        })->count();

        return [
            'group_loans' => $groupLoanIds->count(),
            'group_members' => $groupLoanIds->isEmpty()
                ? 0
                : GroupMember::query()->whereIn('group_loans_id', $groupLoanIds)->count(),
            'groups' => $orphanedGroupCount,
            'loan_receipts' => $groupLoanIds->isEmpty()
                ? 0
                : LoanReceipts::query()->whereIn('group_loans_id', $groupLoanIds)->count(),
        ];
    }

    public function deleteWithAudit(LoanPhase $phase, User $user): void
    {
        if (!$phase->canDeleteBy($user)) {
            throw ValidationException::withMessages([
                'phase' => 'You do not have permission to delete this phase.',
            ]);
        }

        if ($phase->phaseApplications()->exists()) {
            throw ValidationException::withMessages([
                'phase' => 'Remove all applications from this phase before deleting it.',
            ]);
        }

        $cascadeCounts = $this->deletePreviewCounts($phase);

        $backup = $this->backupService->backup($phase);
        $cascadeCounts['backup_file'] = $backup['path'];

        $snapshot = [
            'phase' => $phase->phase,
            'status' => $phase->status,
            'constituency_code' => $phase->constituency_code,
            'review_date' => $phase->review_date?->format('Y-m-d'),
            'review_venue' => $phase->review_venue,
            'amount_disbursed' => $phase->amount_disbursed,
            'amount_repaid' => $phase->amount_repaid,
            'disbursed_at' => $phase->disbursed_at?->format('Y-m-d'),
            'submitted_to_hq_at' => $phase->submitted_to_hq_at?->format('Y-m-d H:i'),
        ];

        DB::transaction(function () use ($phase, $user, $snapshot, $cascadeCounts, $backup) {
            $phaseId = $phase->id;
            $constituencyCode = $phase->constituency_code;

            $groupLoanIds = GroupLoan::query()
                ->where('loan_phase_id', $phaseId)
                ->pluck('id')
                ->all();

            $groupIds = GroupLoan::query()
                ->where('loan_phase_id', $phaseId)
                ->whereNotNull('group_id')
                ->pluck('group_id')
                ->unique()
                ->all();

            if ($groupLoanIds !== []) {
                LoanReceipts::query()->whereIn('group_loans_id', $groupLoanIds)->delete();
                GroupMember::query()->whereIn('group_loans_id', $groupLoanIds)->delete();
                GroupLoan::query()->whereIn('id', $groupLoanIds)->delete();
            }

            foreach ($groupIds as $groupId) {
                if (!GroupLoan::query()->where('group_id', $groupId)->exists()) {
                    Group::query()->whereKey($groupId)->delete();
                }
            }

            PhaseApplicationDocument::query()->where('loan_phase_id', $phaseId)->delete();
            CommitteeMember::query()->where('loan_phase_id', $phaseId)->delete();
            PhaseComment::query()->where('loan_phase_id', $phaseId)->delete();
            PhaseDocument::query()->where('loan_phase_id', $phaseId)->delete();
            SecretariatApproval::query()->where('loan_phase_id', $phaseId)->delete();
            HqReview::query()->where('loan_phase_id', $phaseId)->delete();

            $this->logAudit(
                $phase,
                $user,
                'deleted',
                $snapshot,
                [],
                $cascadeCounts,
                $backup['path'],
            );

            $phase->delete();

            $this->phaseService->invalidatePhasesCache($constituencyCode);
            $this->phaseService->invalidatePhaseCache($phaseId);
            $this->cacheService->flushGroupLoansCache($constituencyCode);
        });
    }

    /**
     * @param  list<string>  $allowed
     * @return array<string, mixed>
     */
    protected function validatePayload(LoanPhase $phase, array $data, array $allowed): array
    {
        $rules = [];
        $attributes = [];

        if (in_array('phase', $allowed, true)) {
            $rules['phaseName'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('loan_phases', 'phase')
                    ->where('constituency_code', $phase->constituency_code)
                    ->ignore($phase->id),
            ];
            $attributes['phaseName'] = 'phase name';
        }

        if (in_array('review_date', $allowed, true)) {
            $rules['reviewDate'] = ['nullable', 'date'];
        }

        if (in_array('review_venue', $allowed, true)) {
            $rules['reviewVenue'] = ['nullable', 'string', 'max:255'];
        }

        if (in_array('amount_disbursed', $allowed, true)) {
            $rules['amountDisbursed'] = ['nullable', 'numeric', 'min:0'];
        }

        if (in_array('amount_repaid', $allowed, true)) {
            $rules['amountRepaid'] = ['nullable', 'numeric', 'min:0'];
        }

        if (in_array('disbursed_at', $allowed, true)) {
            $rules['disbursedAt'] = ['nullable', 'date'];
        }

        if (in_array('training_start_date', $allowed, true)) {
            $rules['trainingStartDate'] = ['nullable', 'date'];
        }

        if (in_array('training_end_date', $allowed, true)) {
            $rules['trainingEndDate'] = ['nullable', 'date', 'after_or_equal:trainingStartDate'];
        }

        if (in_array('training_venue', $allowed, true)) {
            $rules['trainingVenue'] = ['nullable', 'string', 'max:255'];
        }

        if (in_array('training_notes', $allowed, true)) {
            $rules['trainingNotes'] = ['nullable', 'string', 'max:1000'];
        }

        $validator = validator($data, $rules, [], $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $payload = [];

        $map = [
            'phaseName' => 'phase',
            'reviewDate' => 'review_date',
            'reviewVenue' => 'review_venue',
            'amountDisbursed' => 'amount_disbursed',
            'amountRepaid' => 'amount_repaid',
            'disbursedAt' => 'disbursed_at',
            'trainingStartDate' => 'training_start_date',
            'trainingEndDate' => 'training_end_date',
            'trainingVenue' => 'training_venue',
            'trainingNotes' => 'training_notes',
        ];

        foreach ($map as $input => $column) {
            if (!in_array($column, $allowed, true) || !array_key_exists($input, $validated)) {
                continue;
            }

            $value = $validated[$input];
            if ($value === '') {
                $value = null;
            }

            $payload[$column] = $value;
        }

        if (isset($payload['phase'])) {
            $payload['phase'] = trim($payload['phase']);
        }

        return $payload;
    }

    protected function logAudit(
        LoanPhase $phase,
        User $user,
        string $action,
        array $oldValues,
        array $newValues,
        array $cascadeCounts = [],
        ?string $backupFile = null,
    ): void {
        $backupFile ??= $cascadeCounts['backup_file'] ?? null;

        $properties = [
            'action' => $action,
            'model' => LoanPhase::class,
            'loan_phase_id' => $phase->id,
            'constituency_code' => $phase->constituency_code,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'cascade_counts' => $cascadeCounts,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($backupFile) {
            $properties['backup_file'] = $backupFile;
        }

        if ($action === 'updated' && $newValues !== []) {
            $properties['attributes'] = $newValues;
            $properties['old'] = $oldValues;
        }

        if ($action === 'deleted' && $oldValues !== []) {
            $properties['old'] = $oldValues;
        }

        $message = match ($action) {
            'deleted' => sprintf(
                'Phase "%s" was deleted.%s',
                $phase->phase,
                $backupFile ? ' A backup was saved.' : '',
            ),
            default => sprintf('Phase "%s" details were updated.', $phase->phase),
        };

        activity('phase_audit')
            ->performedOn($phase)
            ->causedBy($user)
            ->event($action)
            ->withProperties($properties)
            ->log($message);
    }
}
