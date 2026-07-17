<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\SecretariatApproval;
use App\Models\User;
use App\Models\GroupLoan;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Jobs\SendWorkflowNotification;
use App\Services\Exports\SignedCodeListPdfService;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SecretariatApprovalService
{
    public function hasApproval(LoanPhase $phase, string $type): bool
    {
        return SecretariatApproval::where('loan_phase_id', $phase->id)
            ->where('approval_type', $type)
            ->whereNotNull('approved_at')
            ->exists();
    }

    public function missingTrainingGateApprovals(LoanPhase $phase): array
    {
        $missing = [];
        foreach ($this->trainingGateTypesForPhase($phase) as $type) {
            if (!$this->hasApproval($phase, $type)) {
                $missing[] = $type;
            }
        }

        return $missing;
    }

    public function requiresFundTransferAuthorization(LoanPhase $phase): bool
    {
        $phase->loadMissing('constituency');

        $available = $phase->constituency?->constituency_bank_balance;
        if ($available === null) {
            return false;
        }

        return $this->phaseRequestedAmount($phase) > (float) $available;
    }

    public function phaseRequestedAmount(LoanPhase $phase): float
    {
        $stored = (float) ($phase->total_amount_approved ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        if (in_array($phase->status, ['draft', 'in_review'], true)) {
            return (float) $phase->phaseApplications()->sum('amount_applied');
        }

        return (float) $phase->phaseApplications()
            ->whereNotIn('status', ['pending', 'rejected', 'deferred'])
            ->sum('amount_approved');
    }

    /**
     * @return list<string>
     */
    public function approvalSequence(LoanPhase $phase): array
    {
        $sequence = [SecretariatApproval::TYPE_POST_HQ_REVIEW];

        if ($this->requiresFundTransferAuthorization($phase)) {
            $sequence[] = SecretariatApproval::TYPE_FUND_TRANSFER;
        }

        $sequence[] = SecretariatApproval::TYPE_TRAINING_AUTHORIZATION;
        $sequence[] = SecretariatApproval::TYPE_FINAL_DISBURSEMENT;

        return $sequence;
    }

    /**
     * @return list<string>
     */
    public function trainingGateTypesForPhase(LoanPhase $phase): array
    {
        return array_values(array_filter(
            $this->approvalSequence($phase),
            fn (string $type) => $type !== SecretariatApproval::TYPE_FINAL_DISBURSEMENT
        ));
    }

    public function approvalTypesForPhase(LoanPhase $phase): array
    {
        $labels = SecretariatApproval::defaultApprovalLabels();
        $types = [];

        foreach ($this->approvalSequence($phase) as $type) {
            $types[$type] = $labels[$type] ?? $this->approvalTypeLabel($type);
        }

        return $types;
    }

    public function nextPendingApprovalType(LoanPhase $phase): ?string
    {
        foreach ($this->approvalSequence($phase) as $type) {
            if (!$this->hasApproval($phase, $type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return array{done: int, total: int, is_complete: bool}
     */
    public function approvalProgress(LoanPhase $phase): array
    {
        $sequence = $this->approvalSequence($phase);
        $done = 0;

        foreach ($sequence as $type) {
            if ($this->hasApproval($phase, $type)) {
                $done++;
            }
        }

        $total = count($sequence);

        return [
            'done' => $done,
            'total' => $total,
            'is_complete' => $total > 0 && $done === $total,
        ];
    }

    public function nextPendingPhase(?int $excludePhaseId = null): ?LoanPhase
    {
        return LoanPhase::query()
            ->whereIn('status', ['approved', 'in_training', 'pending_disbursement'])
            ->when($excludePhaseId, fn ($query) => $query->where('id', '!=', $excludePhaseId))
            ->orderByDesc('hq_approved_at')
            ->with('constituency:constituency_code,constituency_name')
            ->get()
            ->first(fn (LoanPhase $phase) => $this->nextPendingApprovalType($phase) !== null);
    }

    public function completionSummary(LoanPhase $phase): string
    {
        if ($this->hasApproval($phase, SecretariatApproval::TYPE_FINAL_DISBURSEMENT)) {
            return 'Constituency officers may proceed with cheque disbursement for trained groups.';
        }

        if ($this->hasApproval($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)) {
            return 'The constituency may schedule mandatory training for approved groups.';
        }

        return 'This phase remains in the secretariat workflow until all required steps are signed.';
    }

    public function approvalTypeLabel(?string $type): string
    {
        if ($type === null) {
            return 'Approval';
        }

        return match ($type) {
            SecretariatApproval::TYPE_POST_HQ_REVIEW => 'Confirm HQ approval',
            SecretariatApproval::TYPE_FUND_TRANSFER => 'Letter of authority to disburse funds',
            SecretariatApproval::TYPE_TRAINING_AUTHORIZATION => 'Authorize training',
            SecretariatApproval::TYPE_FINAL_DISBURSEMENT => 'Authorize disbursement',
            default => SecretariatApproval::defaultApprovalLabels()[$type] ?? str_replace('_', ' ', $type),
        };
    }

    /**
     * @return array{
     *     pending_phases:int,
     *     pending_training:int,
     *     pending_disbursement:int,
     *     completed_training:int,
     *     completed_disbursement:int
     * }
     */
    public function dashboardStats(): array
    {
        $phases = LoanPhase::query()
            ->whereIn('status', ['approved', 'in_training', 'pending_disbursement'])
            ->with('secretariatApprovals')
            ->get();

        $pendingTraining = 0;
        $pendingDisbursement = 0;
        $pendingPhases = 0;

        foreach ($phases as $phase) {
            $next = $this->nextPendingApprovalType($phase);
            if ($next === null) {
                continue;
            }

            $pendingPhases++;

            if (in_array($next, SecretariatApproval::trainingGateTypes(), true)) {
                $pendingTraining++;
            }

            if ($next === SecretariatApproval::TYPE_FINAL_DISBURSEMENT) {
                $pendingDisbursement++;
            }
        }

        return [
            'pending_phases' => $pendingPhases,
            'pending_training' => $pendingTraining,
            'pending_disbursement' => $pendingDisbursement,
            'completed_training' => SecretariatApproval::query()
                ->where('approval_type', SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)
                ->whereNotNull('approved_at')
                ->count(),
            'completed_disbursement' => SecretariatApproval::query()
                ->where('approval_type', SecretariatApproval::TYPE_FINAL_DISBURSEMENT)
                ->whereNotNull('approved_at')
                ->count(),
        ];
    }

    public function phaseMatchesCategory(LoanPhase $phase, string $category): bool
    {
        $next = $this->nextPendingApprovalType($phase);

        return match ($category) {
            'all' => true,
            'pending' => $next !== null,
            'pending_training' => $next !== null
                && in_array($next, SecretariatApproval::trainingGateTypes(), true),
            'training_authorized' => $this->hasApproval(
                $phase,
                SecretariatApproval::TYPE_TRAINING_AUTHORIZATION
            ),
            'disbursement_authorized' => $this->hasApproval(
                $phase,
                SecretariatApproval::TYPE_FINAL_DISBURSEMENT
            ),
            'pending_disbursement' => $next === SecretariatApproval::TYPE_FINAL_DISBURSEMENT,
            'completed' => $next === null
                && $this->hasApproval($phase, SecretariatApproval::TYPE_FINAL_DISBURSEMENT),
            default => true,
        };
    }

    public function categoryLabel(string $category): string
    {
        return match ($category) {
            'pending' => 'Pending authorizations',
            'pending_training' => 'Training gate pending',
            'training_authorized' => 'Training authorized',
            'disbursement_authorized' => 'Disbursement authorized',
            'pending_disbursement' => 'Disbursement authorization pending',
            'completed' => 'Fully authorized phases',
            default => 'All phases',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function buildActionInbox(int $limit = 8): array
    {
        $pending = LoanPhase::query()
            ->with(['constituency', 'secretariatApprovals'])
            ->whereIn('status', ['approved', 'in_training', 'pending_disbursement'])
            ->orderByDesc('hq_approved_at')
            ->get()
            ->filter(fn (LoanPhase $phase) => $this->nextPendingApprovalType($phase) !== null)
            ->values();

        return [
            'mode' => 'secretariat',
            'inbox_title' => 'Authorization inbox',
            'inbox_badge' => 'Incoming applications',
            'inbox_empty' => 'No phases awaiting secretariat authorization.',
            'tasks_title' => 'Phases awaiting authorization',
            'tasks_queue_url' => route('secretariat.phases.index', ['filter' => 'pending']),
            'tasks_queue_label' => 'Open secretariat queue',
            'pending_phase_count' => $pending->count(),
            'pending_phases' => $pending->take($limit)->map(function (LoanPhase $phase) {
                $next = $this->nextPendingApprovalType($phase);
                $progress = $this->approvalProgress($phase);

                return [
                    'label' => $phase->phase_ref ?? $phase->phase,
                    'constituency' => $phase->constituency?->constituency_name ?? 'Unknown',
                    'submitted_at' => $phase->hq_approved_at?->format('M d, Y') ?? 'N/A',
                    'meta' => $this->approvalTypeLabel($next) . ' · ' . $progress['done'] . '/' . $progress['total'],
                    'url' => route('secretariat.phases.review', $phase),
                    'action_label' => $progress['done'] === 0 ? 'Authorize' : 'Continue',
                    'secondary_url' => route('secretariat.phases.code-list', $phase),
                    'secondary_label' => 'Code list',
                ];
            })->all(),
        ];
    }

    public function signatureRequiredForType(string $type): bool
    {
        return $type === SecretariatApproval::TYPE_POST_HQ_REVIEW;
    }

    public function recordApproval(
        LoanPhase $phase,
        string $type,
        ?UploadedFile $signatureFile,
        ?string $notes = null,
    ): SecretariatApproval {
        $allowed = [
            SecretariatApproval::TYPE_POST_HQ_REVIEW,
            SecretariatApproval::TYPE_FUND_TRANSFER,
            SecretariatApproval::TYPE_TRAINING_AUTHORIZATION,
            SecretariatApproval::TYPE_FINAL_DISBURSEMENT,
        ];

        if (!in_array($type, $allowed, true)) {
            throw ValidationException::withMessages(['type' => 'Invalid approval type.']);
        }

        if ($type === SecretariatApproval::TYPE_FUND_TRANSFER
            && !$this->requiresFundTransferAuthorization($phase)) {
            throw ValidationException::withMessages([
                'approval' => 'Letter of authority to disburse funds is not required for this phase.',
            ]);
        }

        if ($type === SecretariatApproval::TYPE_FUND_TRANSFER
            && !$this->hasApproval($phase, SecretariatApproval::TYPE_POST_HQ_REVIEW)) {
            throw ValidationException::withMessages([
                'approval' => 'Post-HQ review must be signed before fund transfer authorization.',
            ]);
        }

        if ($type === SecretariatApproval::TYPE_TRAINING_AUTHORIZATION) {
            $requiredBeforeTraining = $this->requiresFundTransferAuthorization($phase)
                ? SecretariatApproval::TYPE_FUND_TRANSFER
                : SecretariatApproval::TYPE_POST_HQ_REVIEW;

            if (!$this->hasApproval($phase, $requiredBeforeTraining)) {
                throw ValidationException::withMessages([
                    'approval' => $requiredBeforeTraining === SecretariatApproval::TYPE_FUND_TRANSFER
                        ? 'Letter of authority to disburse funds must be signed before training authorization.'
                        : 'Post-HQ review must be signed before training authorization.',
                ]);
            }
        }

        if ($type === SecretariatApproval::TYPE_FINAL_DISBURSEMENT
            && !$this->hasApproval($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)) {
            throw ValidationException::withMessages([
                'approval' => 'Training authorization must be completed before final disbursement approval.',
            ]);
        }

        if ($this->signatureRequiredForType($type) && ! $signatureFile) {
            throw ValidationException::withMessages([
                'signatureFile' => 'A digital signature is required to sign the approved code list.',
            ]);
        }

        return DB::transaction(function () use ($phase, $type, $signatureFile, $notes) {
            $signaturePath = null;

            if ($signatureFile) {
                $signaturePath = $signatureFile->store(
                    "secretariat/signatures/phase-{$phase->id}",
                    'local'
                );
            }

            $approval = SecretariatApproval::updateOrCreate(
                [
                    'loan_phase_id' => $phase->id,
                    'approval_type' => $type,
                ],
                [
                    'approved_by' => auth()->id(),
                    'signature_path' => $signaturePath,
                    'notes' => $notes,
                    'approved_at' => now(),
                ]
            );

            $this->syncApplicationsAfterApproval($phase, $type);

            if ($type === SecretariatApproval::TYPE_POST_HQ_REVIEW) {
                app(SignedCodeListPdfService::class)->storeAsApprovedCodeListDocument(
                    $phase->fresh(),
                    auth()->id()
                );
            }

            if ($type === SecretariatApproval::TYPE_TRAINING_AUTHORIZATION) {
                DB::afterCommit(fn () => SendWorkflowNotification::dispatchSync(
                    $phase->id,
                    SendWorkflowNotification::TYPE_TRAINING_SCHEDULE_REQUIRED
                ));
            }

            return $approval;
        });
    }

    public function canReturnPhaseToHq(LoanPhase $phase): bool
    {
        return $phase->status === 'approved'
            && !$this->hasApproval($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION);
    }

    public function returnPhaseToHq(LoanPhase $phase, string $reason): void
    {
        if (!$this->canReturnPhaseToHq($phase)) {
            throw ValidationException::withMessages([
                'returnRemarks' => 'This phase cannot be returned to HQ. Training may already be authorized.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'returnRemarks' => 'Please provide a reason for returning this phase to HQ.',
            ]);
        }

        DB::transaction(function () use ($phase, $reason) {
            $phase->loadMissing('secretariatApprovals');

            foreach ($phase->secretariatApprovals as $approval) {
                if ($approval->signature_path && Storage::disk('local')->exists($approval->signature_path)) {
                    Storage::disk('local')->delete($approval->signature_path);
                }
            }

            SecretariatApproval::where('loan_phase_id', $phase->id)->delete();

            $phaseApplications = PhaseApplication::query()
                ->where('loan_phase_id', $phase->id)
                ->where('status', 'approved')
                ->with('tempApplication:id')
                ->get();

            if ($phaseApplications->isEmpty()) {
                throw ValidationException::withMessages([
                    'phase' => 'No approved applications found to return to HQ.',
                ]);
            }

            foreach ($phaseApplications as $app) {
                if ($app->group_loan_id) {
                    DB::table('group_members')->where('group_loans_id', $app->group_loan_id)->delete();
                    GroupLoan::where('id', $app->group_loan_id)->delete();
                }

                $app->update([
                    'status' => 'pending_hq_approval',
                    'level' => 'hq',
                    'action' => 'returned_from_secretariat',
                    'group_loan_id' => null,
                ]);

                if ($app->tempApplication) {
                    $app->tempApplication->update([
                        'status' => 'under_hq_review',
                        'lms_to_loan_app_sync_status' => 'pending',
                    ]);

                    SyncApplicationStatusToDrupal::pushAfterCommit($app->temp_application_id);
                }
            }

            $phase->update([
                'status' => 'submitted_to_hq',
                'hq_approved_at' => null,
                'hq_remarks' => $reason,
            ]);

            activity()
                ->performedOn($phase)
                ->causedBy(auth()->user())
                ->withProperties([
                    'phase' => $phase->phase,
                    'return_reason' => $reason,
                    'status' => 'submitted_to_hq',
                ])
                ->log('Phase returned to HQ by Head of Secretariat for review.');
        });

        try {
            app(WorkflowNotificationService::class)
                ->notifyHqOfficerPhaseReturnedFromSecretariat($phase->fresh(), $reason);
        } catch (\Throwable $e) {
            Log::error('Failed to notify HQ officer of secretariat return', [
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function syncApplicationsAfterApproval(LoanPhase $phase, string $type): void
    {
        $statusMap = [
            SecretariatApproval::TYPE_POST_HQ_REVIEW => 'pending_secretariat_review',
            SecretariatApproval::TYPE_FUND_TRANSFER => 'pending_fund_transfer',
        ];

        if (!isset($statusMap[$type])) {
            return;
        }

        $tempStatus = $statusMap[$type];
        $phaseApps = PhaseApplication::where('loan_phase_id', $phase->id)
            ->with('tempApplication:id')
            ->get();

        foreach ($phaseApps as $phaseApp) {
            if (!$phaseApp->tempApplication) {
                continue;
            }

            $phaseApp->tempApplication->update([
                'status' => $tempStatus,
                'lms_to_loan_app_sync_status' => 'pending',
            ]);

            SyncApplicationStatusToDrupal::pushAfterCommit($phaseApp->tempApplication->id);
        }
    }

    public function assignHqOfficer(): ?int
    {
        $officers = User::role('hq_officer')
            ->get()
            ->reject(fn (User $user) => $user->hasRole('super-admin'))
            ->pluck('id');

        if ($officers->isEmpty()) {
            $officers = User::role('hq_officer')->pluck('id');
        }

        if ($officers->isEmpty()) {
            return null;
        }

        $workloads = LoanPhase::query()
            ->whereIn('assigned_hq_officer_id', $officers)
            ->whereIn('status', ['submitted_to_hq', 'approved', 'in_training'])
            ->selectRaw('assigned_hq_officer_id, COUNT(*) as workload')
            ->groupBy('assigned_hq_officer_id')
            ->pluck('workload', 'assigned_hq_officer_id');

        return $officers
            ->sortBy(fn ($id) => $workloads[$id] ?? 0)
            ->first();
    }

    public function backfillUnassignedHqOfficers(): int
    {
        $updated = 0;
        $superAdminIds = User::role('super-admin')->pluck('id');

        if ($superAdminIds->isNotEmpty()) {
            $updated += LoanPhase::query()
                ->whereIn('assigned_hq_officer_id', $superAdminIds)
                ->whereIn('status', ['submitted_to_hq', 'approved', 'in_training'])
                ->update(['assigned_hq_officer_id' => null]);
        }

        LoanPhase::query()
            ->whereNull('assigned_hq_officer_id')
            ->whereIn('status', ['submitted_to_hq', 'approved', 'in_training'])
            ->orderBy('submitted_to_hq_at')
            ->each(function (LoanPhase $phase) use (&$updated) {
                $officerId = $this->assignHqOfficer();
                if (!$officerId) {
                    return false;
                }

                $phase->update(['assigned_hq_officer_id' => $officerId]);
                $updated++;
            });

        return $updated;
    }
}
