<?php

namespace App\Services\Loan;

use App\Jobs\SyncApplicationStatusToDrupal;
use App\Models\PhaseApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingAttendanceService
{
    public const AUTO_REJECTION_REASON = 'did_not_attend_training';

    public function autoRejectMissedTraining(bool $deadlinePassedOnly = true, bool $dryRun = false): int
    {
        $query = PhaseApplication::query()
            ->where('status', 'in_training')
            ->where('is_trained', false)
            ->whereNull('auto_rejection_reviewed_at')
            ->whereHas('loanPhase', function ($q) use ($deadlinePassedOnly) {
                $q->where('status', 'in_training')
                    ->whereNotNull('training_end_date');

                if ($deadlinePassedOnly) {
                    $q->whereDate('training_end_date', '<', now()->toDateString());
                }
            });

        $applications = $query->with(['tempApplication:id,application_id', 'loanPhase:id,phase'])->get();
        $count = 0;

        foreach ($applications as $application) {
            if ($dryRun) {
                $count++;
                continue;
            }

            DB::transaction(function () use ($application) {
                $application->update([
                    'status' => 'rejected',
                    'rejection_reason' => self::AUTO_REJECTION_REASON,
                    'rejection_message' => 'Group did not attend mandatory training within the scheduled dates. '
                        . 'Constituency officer may review and reinstate if attendance can be verified.',
                    'auto_rejected_at' => now(),
                    'level' => 'system',
                    'action' => 'auto_rejected_missed_training',
                ]);

                $application->tempApplication?->update([
                    'status' => 'rejected',
                    'lms_to_loan_app_sync_status' => 'pending',
                ]);

                if ($application->tempApplication) {
                    SyncApplicationStatusToDrupal::pushAfterCommit($application->tempApplication->id);
                }

                activity()
                    ->performedOn($application)
                    ->withProperties([
                        'application_id' => $application->tempApplication?->application_id,
                        'reason' => self::AUTO_REJECTION_REASON,
                    ])
                    ->log('Application auto-rejected for missed training deadline');
            });

            $count++;
        }

        if ($count > 0) {
            Log::info("Auto-rejected {$count} application(s) for missed training.");
        }

        return $count;
    }

    public function reinstateAutoRejected(int $phaseApplicationId): PhaseApplication
    {
        $application = PhaseApplication::with('loanPhase', 'tempApplication')->findOrFail($phaseApplicationId);

        if (!$application->auto_rejected_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'application' => 'This application was not auto-rejected for missed training.',
            ]);
        }

        if ($application->auto_rejection_reviewed_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'application' => 'This auto-rejection has already been reviewed.',
            ]);
        }

        return DB::transaction(function () use ($application) {
            $application->update([
                'status' => 'in_training',
                'is_trained' => false,
                'rejection_reason' => null,
                'rejection_message' => null,
                'auto_rejection_reviewed_at' => now(),
                'auto_rejection_reviewed_by' => auth()->id(),
                'auto_rejection_review_action' => 'reinstated',
                'auto_rejected_at' => null,
            ]);

            $application->tempApplication?->update([
                'status' => 'in_training',
                'lms_to_loan_app_sync_status' => 'pending',
            ]);

            if ($application->tempApplication) {
                SyncApplicationStatusToDrupal::pushAfterCommit($application->tempApplication->id);
            }

            activity()
                ->performedOn($application)
                ->causedBy(auth()->user())
                ->log('Auto-rejection overturned — group reinstated to in_training');

            return $application->fresh();
        });
    }

    public function confirmAutoRejected(int $phaseApplicationId): PhaseApplication
    {
        $application = PhaseApplication::findOrFail($phaseApplicationId);

        if (!$application->auto_rejected_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'application' => 'This application was not auto-rejected for missed training.',
            ]);
        }

        if ($application->auto_rejection_reviewed_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'application' => 'This auto-rejection has already been reviewed.',
            ]);
        }

        $application->update([
            'auto_rejection_reviewed_at' => now(),
            'auto_rejection_reviewed_by' => auth()->id(),
            'auto_rejection_review_action' => 'confirmed',
        ]);

        activity()
            ->performedOn($application)
            ->causedBy(auth()->user())
            ->log('Constituency officer confirmed auto-rejection for missed training');

        return $application->fresh();
    }

    public function isPendingAutoRejectionReview(PhaseApplication $application): bool
    {
        return $application->status === 'rejected'
            && $application->auto_rejected_at
            && !$application->auto_rejection_reviewed_at;
    }
}
