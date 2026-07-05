<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhaseDetailEditService
{
    public function __construct(
        protected LoanPhaseService $phaseService,
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

    public function deleteWithAudit(LoanPhase $phase, User $user): void
    {
        if (!$phase->canEditDetailsBy($user)) {
            throw ValidationException::withMessages([
                'phase' => 'You do not have permission to delete this phase.',
            ]);
        }

        if ($phase->phaseApplications()->exists()) {
            throw ValidationException::withMessages([
                'phase' => 'Remove all applications from this phase before deleting it.',
            ]);
        }

        if ($phase->grouploans()->exists()) {
            throw ValidationException::withMessages([
                'phase' => 'This phase has linked group loans and cannot be deleted.',
            ]);
        }

        $snapshot = $phase->only([
            'id',
            'constituency_code',
            'phase',
            'status',
            'review_date',
            'review_venue',
            'amount_disbursed',
            'amount_repaid',
        ]);

        DB::transaction(function () use ($phase, $user, $snapshot) {
            $this->logAudit($phase, $user, 'deleted', $snapshot, []);

            $phase->delete();

            $this->phaseService->invalidatePhasesCache($phase->constituency_code);
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
    ): void {
        activity('phase_audit')
            ->performedOn($phase)
            ->causedBy($user)
            ->event($action)
            ->withProperties([
                'action' => $action,
                'model' => LoanPhase::class,
                'loan_phase_id' => $phase->id,
                'constituency_code' => $phase->constituency_code,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log(match ($action) {
                'deleted' => "Phase \"{$phase->phase}\" was deleted.",
                default => "Phase \"{$phase->phase}\" details were updated.",
            });
    }
}
