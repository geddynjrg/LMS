<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Services\DrupalApiService;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncApplicationStatusToDrupal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DRUPAL_TRANSITIONS = [
        'draft' => ['group_member_confirmation'],
        'group_member_confirmation' => ['submitted'],
        'submitted' => ['constituency_review', 'rejected', 'draft'],
        'constituency_review' => ['approved_by_constituency', 'rejected', 'draft'],
        'approved_by_constituency' => ['under_review'],
        'under_review' => ['approved', 'rejected'],
        'approved' => ['pending_secretariat_review', 'training'],
        'pending_secretariat_review' => ['pending_fund_transfer'],
        'pending_fund_transfer' => ['training'],
        'training' => ['pending_final_disbursement'],
        'pending_final_disbursement' => ['disbursed', 'disbursement_failed'],
        'disbursement_failed' => ['pending_final_disbursement', 'closed'],
        'rejected' => ['constituency_review', 'under_review'],
        'disbursed' => ['closed'],
        'closed' => [],
    ];

    /**
     * Canonical forward workflow order — ensures every SMS-triggering stage is visited.
     */
    public const DRUPAL_WORKFLOW_ORDER = [
        'draft',
        'group_member_confirmation',
        'submitted',
        'constituency_review',
        'approved_by_constituency',
        'under_review',
        'approved',
        'pending_secretariat_review',
        'pending_fund_transfer',
        'training',
        'pending_final_disbursement',
        'disbursed',
        'closed',
    ];

    public const DRUPAL_STATUS_RANKS = [
        'draft' => 0,
        'group_member_confirmation' => 1,
        'submitted' => 2,
        'constituency_review' => 3,
        'approved_by_constituency' => 4,
        'under_review' => 5,
        'approved' => 6,
        'rejected' => 6,
        'pending_secretariat_review' => 7,
        'pending_fund_transfer' => 8,
        'training' => 9,
        'pending_final_disbursement' => 10,
        'disbursement_failed' => 10,
        'disbursed' => 11,
        'closed' => 12,
    ];

    public $tries = 5;
    public $backoff = [10, 30, 60, 300, 600];

    protected $tempApplicationId;

    public function __construct($tempApplicationId)
    {
        $this->tempApplicationId = $tempApplicationId;
    }

    public static function syncNow(int $tempApplicationId): void
    {
        self::dispatchSync($tempApplicationId);
    }

    /**
     * Push LMS status to Drupal immediately so member SMS notifications fire
     * without depending on a queue worker (Horizon).
     */
    public static function pushNow(int $tempApplicationId): void
    {
        self::dispatchSync($tempApplicationId);
    }

    /**
     * Same as pushNow, but waits until the active DB transaction commits.
     */
    public static function pushAfterCommit(int $tempApplicationId): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => self::dispatchSync($tempApplicationId));

            return;
        }

        self::dispatchSync($tempApplicationId);
    }

    public function handle(DrupalApiService $drupal): void
    {
        Log::debug('Job started', ['temp_id' => $this->tempApplicationId]);

        $app = TempApplication::select('id', 'application_id', 'status', 'lms_to_loan_app_sync_status')
            ->with(['phaseApplication' => function ($query) {
                $query->select(
                    'id',
                    'phase_applications.temp_application_id',
                    'phase_applications.loan_phase_id',
                    'phase_applications.rejection_reason',
                    'phase_applications.rejection_message',
                    'phase_applications.status',
                    'phase_applications.amount_issued',
                    'phase_applications.amount_approved',
                    'phase_applications.disbursement_failure_reason',
                    'phase_applications.disbursement_failure_notes',
                    'phase_applications.training_start_date',
                    'phase_applications.training_end_date',
                    'phase_applications.training_venue',
                    'phase_applications.training_notes'
                )->with(['loanPhase:id,training_start_date,training_end_date,training_venue,training_notes']);
            }])
            ->findOrFail($this->tempApplicationId);

        if ($app->status === 'submitted') {
            return;
        }

        Redis::throttle('drupal-api-sync')
            ->allow(10)
            ->every(1)
            ->then(function () use ($drupal, $app) {
                $targetStatus = self::resolveTargetDrupalStatus($app);
                $drupalApplication = $drupal->getApplication($app->application_id);
                $currentDrupalStatus = $drupalApplication['status'] ?? null;
                $preferredDrupalBase = $drupalApplication['_drupal_base'] ?? null;

                if (!$currentDrupalStatus) {
                    $this->markSyncSkipped($app, 'Application not found in Drupal');

                    return;
                }

                if (!self::isDrupalBehind($currentDrupalStatus, $targetStatus)) {
                    $wasAlreadyCompleted = $app->lms_to_loan_app_sync_status === 'completed';

                    $app->update([
                        'lms_to_loan_app_sync_status' => 'completed',
                        'lms_to_loan_app_synced_at' => now(),
                        'lms_to_loan_app_sync_error' => null,
                    ]);

                    if (!$wasAlreadyCompleted) {
                        Log::info('Drupal already at or ahead of LMS target', [
                            'application_id' => $app->application_id,
                            'drupal_status' => $currentDrupalStatus,
                            'target_status' => $targetStatus,
                        ]);
                    }

                    return;
                }

                if ($app->lms_to_loan_app_sync_status === 'completed') {
                    Log::warning('Drupal sync marked completed but Drupal is behind LMS', [
                        'application_id' => $app->application_id,
                        'drupal_status' => $currentDrupalStatus,
                        'target_status' => $targetStatus,
                    ]);
                }

                $app->update([
                    'lms_to_loan_app_sync_status' => 'processing',
                    'lms_to_loan_app_synced_at' => now(),
                ]);

                try {
                    $steps = $this->buildDrupalTransitionPath($currentDrupalStatus, $targetStatus);

                    if (empty($steps)) {
                        $app->update([
                            'lms_to_loan_app_sync_status' => 'completed',
                            'lms_to_loan_app_synced_at' => now(),
                            'lms_to_loan_app_sync_error' => null,
                        ]);

                        return;
                    }

                    $lastResponse = null;

                    foreach ($steps as $stepStatus) {
                        $liveStatus = $drupal->getApplication($app->application_id)['status'] ?? null;

                        if ($liveStatus === $stepStatus) {
                            continue;
                        }

                        $payload = $this->buildDrupalPayload($app, $stepStatus);
                        $lastResponse = $drupal->updateApplicationStatus(
                            $app->application_id,
                            $payload,
                            $preferredDrupalBase
                        );

                        if ($lastResponse->status() === 404) {
                            $this->markSyncSkipped($app, 'Application not found in Drupal during status update');

                            return;
                        }

                        if (!$drupal->isSuccessful($lastResponse)) {
                            throw new \Exception('Drupal returned unsuccessful response: ' . $lastResponse->body());
                        }
                    }

                    $finalDrupalStatus = $drupal->getApplication($app->application_id)['status'] ?? null;
                    if ($finalDrupalStatus && $this->isDrupalBehind($finalDrupalStatus, $targetStatus)) {
                        throw new \RuntimeException(
                            "Drupal still behind after sync: {$finalDrupalStatus} expected {$targetStatus}"
                        );
                    }

                    $app->update([
                        'lms_to_loan_app_sync_status' => 'completed',
                        'lms_to_loan_app_synced_at' => now(),
                        'lms_to_loan_app_sync_error'  => null,
                        'external_id'          => $lastResponse?->json('data.id') ?? null,
                    ]);

                    Log::info('Successfully synced status to Drupal', [
                        'application_id' => $app->application_id,
                        'lms_status'     => $app->status,
                        'phase_status'   => $app->phaseApplication?->status,
                        'drupal_status'  => $targetStatus,
                        'steps'          => $steps,
                    ]);
                } catch (Throwable $e) {
                    $errorMessage = $e ? $e->getMessage() : 'Unknown error (no exception object)';
                    $errorFile    = $e ? $e->getFile() : 'Unknown file';
                    $errorLine    = $e ? $e->getLine() : 'Unknown line';

                    $app->update([
                        'lms_to_loan_app_sync_status' => 'failed',
                        'lms_to_loan_app_synced_at' => now(),
                        'lms_to_loan_app_sync_error'  => substr($errorMessage, 0, 1000),
                    ]);

                    Log::error('Failed to sync status to Drupal', [
                        'application_id' => $app->application_id,
                        'error'          => $errorMessage,
                        'file'           => $errorFile,
                        'line'           => $errorLine,
                        'trace'          => $e ? $e->getTraceAsString() : 'No trace available',
                    ]);

                    throw $e;
                }
            }, function () {
                return $this->release(10);
            });
    }

    public static function isDrupalBehind(?string $current, string $target): bool
    {
        if ($current === null || $current === $target) {
            return false;
        }

        // Constituency returned the application to the group for correction.
        if ($target === 'draft') {
            return in_array($current, [
                'submitted',
                'constituency_review',
                'group_member_confirmation',
            ], true);
        }

        if ($target === 'rejected') {
            return $current !== 'rejected';
        }

        if ($target === 'disbursement_failed') {
            return !in_array($current, ['disbursement_failed', 'disbursed', 'closed'], true);
        }

        $currentRank = self::DRUPAL_STATUS_RANKS[$current] ?? -1;
        $targetRank = self::DRUPAL_STATUS_RANKS[$target] ?? -1;

        return $targetRank > $currentRank;
    }

    public static function resolveTargetDrupalStatus(TempApplication $app): string
    {
        $phaseStatus = $app->phaseApplication?->status;
        $tempStatus = strtolower(trim((string) $app->status));

        // Returned to applicant — always draft on the loans portal, even if phase is deferred.
        if ($tempStatus === 'draft' || $phaseStatus === 'deferred') {
            return 'draft';
        }

        if ($tempStatus === 'rejected' || $phaseStatus === 'rejected') {
            return 'rejected';
        }

        if ($tempStatus === 'disbursed' || $phaseStatus === 'disbursed') {
            return 'disbursed';
        }

        if ($tempStatus === 'disbursement_failed' || $phaseStatus === 'disbursement_failed') {
            return 'disbursement_failed';
        }

        if ($phaseStatus) {
            $fromPhase = match ($phaseStatus) {
                'pending' => 'constituency_review',
                'pending_submission_to_hq' => 'approved_by_constituency',
                'pending_hq_approval' => 'under_review',
                'pending_secretariat_review' => 'pending_secretariat_review',
                'approved' => 'approved',
                'in_training' => 'training',
                'pending_disbursement' => 'pending_final_disbursement',
                'disbursed' => 'disbursed',
                'rejected' => 'rejected',
                'disbursement_failed' => 'disbursement_failed',
                default => null,
            };

            if ($fromPhase) {
                return $fromPhase;
            }
        }

        $mapped = self::mapToDrupalStatus($tempStatus);

        if ($mapped === 'draft' && $tempStatus !== 'draft') {
            Log::warning('Unmapped LMS status sent to Drupal; using under_review fallback', [
                'application_id' => $app->application_id,
                'temp_status' => $tempStatus,
                'phase_status' => $phaseStatus,
            ]);

            return 'under_review';
        }

        return $mapped;
    }

    protected function buildDrupalPayload(TempApplication $app, string $drupalStatus): array
    {
        $payload = ['status' => $drupalStatus];

        if ($drupalStatus === 'rejected' || $drupalStatus === 'draft') {
            $message = $app->phaseApplication?->rejection_message
                ?? $app->phaseApplication?->comments
                ?? null;

            if ($drupalStatus === 'rejected' || filled($message)) {
                $payload['rejection_reason'] = $this->normalizeRejectionReason(
                    $app->phaseApplication?->rejection_reason ?? 'incomplete_information'
                );
                $payload['rejection_message'] = $message
                    ?? ($drupalStatus === 'draft'
                        ? 'Returned to group for correction by the Constituency Office.'
                        : 'No message provided');
            }
        }

        if ($drupalStatus === 'disbursement_failed') {
            $payload['rejection_reason'] = $this->normalizeRejectionReason(
                $app->phaseApplication?->disbursement_failure_reason
            );
            $payload['rejection_message'] = $app->phaseApplication?->disbursement_failure_notes
                ?: ($app->phaseApplication?->disbursement_failure_reason
                    ? str_replace('_', ' ', $app->phaseApplication->disbursement_failure_reason)
                    : 'Disbursement could not be completed');
        }

        if ($drupalStatus === 'training') {
            $phaseApplication = $app->phaseApplication;
            $phase = $phaseApplication?->loanPhase;
            $trainingStart = $phaseApplication?->training_start_date ?? $phase?->training_start_date;
            $trainingEnd = $phaseApplication?->training_end_date ?? $phase?->training_end_date;
            $trainingVenue = $phaseApplication?->training_venue ?? $phase?->training_venue;
            $trainingNotes = $phaseApplication?->training_notes ?? $phase?->training_notes;

            if ($trainingStart) {
                $payload['training_start_date'] = $trainingStart->format('Y-m-d');
            }
            if ($trainingEnd) {
                $payload['training_end_date'] = $trainingEnd->format('Y-m-d');
            }
            if ($trainingVenue) {
                $payload['training_venue'] = $trainingVenue;
            }
            if ($trainingNotes) {
                $payload['training_notes'] = $trainingNotes;
            }
        }

        if ($drupalStatus === 'disbursed') {
            $phaseApplication = $app->phaseApplication;
            $disbursedAmount = $phaseApplication?->amount_issued ?? $phaseApplication?->amount_approved;
            if ($disbursedAmount) {
                $payload['loan_amount'] = (int) round((float) $disbursedAmount);
            }
        }

        return $payload;
    }

    protected function buildDrupalTransitionPath(string $from, string $to): array
    {
        if ($from === $to) {
            return [];
        }

        // Direct return-to-draft (constituency correction request).
        if ($to === 'draft') {
            return ['draft'];
        }

        if ($to === 'rejected') {
            if (in_array('rejected', self::DRUPAL_TRANSITIONS[$from] ?? [], true)) {
                return ['rejected'];
            }

            return $this->buildDrupalTransitionPathBfs($from, $to);
        }

        if ($to === 'disbursement_failed') {
            $forwardSteps = $this->buildCanonicalForwardPath($from, 'pending_final_disbursement');

            if ($from === 'disbursement_failed') {
                return [];
            }

            if ($from === 'pending_final_disbursement') {
                return ['disbursement_failed'];
            }

            return array_merge($forwardSteps, ['disbursement_failed']);
        }

        $canonical = $this->buildCanonicalForwardPath($from, $to);
        if (!empty($canonical)) {
            return $canonical;
        }

        return $this->buildDrupalTransitionPathBfs($from, $to);
    }

    protected function buildCanonicalForwardPath(string $from, string $to): array
    {
        $fromIdx = array_search($from, self::DRUPAL_WORKFLOW_ORDER, true);
        $toIdx = array_search($to, self::DRUPAL_WORKFLOW_ORDER, true);

        if ($fromIdx === false || $toIdx === false || $toIdx <= $fromIdx) {
            return [];
        }

        return array_slice(self::DRUPAL_WORKFLOW_ORDER, $fromIdx + 1, $toIdx - $fromIdx);
    }

    protected function buildDrupalTransitionPathBfs(string $from, string $to): array
    {
        $queue = [[$from]];
        $visited = [$from => true];

        while ($queue) {
            $path = array_shift($queue);
            $current = end($path);

            foreach (self::DRUPAL_TRANSITIONS[$current] ?? [] as $next) {
                if ($next === $to) {
                    return array_merge(array_slice($path, 1), [$to]);
                }

                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue[] = array_merge($path, [$next]);
                }
            }
        }

        throw new \RuntimeException("No Drupal transition path from {$from} to {$to}");
    }

    protected function normalizeRejectionReason(?string $reason): string
    {
        $allowed = [
            'incomplete_information',
            'missing_documents',
            'eligibility_criteria',
            'duplicate_application',
            'insufficient_collateral',
            'poor_credit_history',
            'member_declined',
            'other',
        ];

        if ($reason === null || $reason === '') {
            return 'other';
        }

        $reason = strtolower(trim($reason));
        $aliases = [
            'unspecified' => 'other',
            'other_reason' => 'other',
            'incomplete' => 'incomplete_information',
            'missing_document' => 'missing_documents',
            'eligibility' => 'eligibility_criteria',
            'duplicate' => 'duplicate_application',
            'defaulter' => 'poor_credit_history',
            'credit_history' => 'poor_credit_history',
        ];

        if (isset($aliases[$reason])) {
            $reason = $aliases[$reason];
        }

        return in_array($reason, $allowed, true) ? $reason : 'other';
    }

    protected static function mapToDrupalStatus(string $lmsStatus): string
    {
        return match (strtolower($lmsStatus)) {
            'submitted'                      => 'submitted',
            'under_constituency_review'      => 'constituency_review',
            'under_hq_review'                => 'approved_by_constituency',
            'under_review'                   => 'under_review',
            'pending_hq_approval'            => 'under_review',
            'approved'                       => 'approved',
            'rejected'                       => 'rejected',
            'disbursed'                      => 'disbursed',
            'in_training'                    => 'training',
            'pending_secretariat_review'     => 'pending_secretariat_review',
            'pending_fund_transfer'          => 'pending_fund_transfer',
            'pending_disbursement'           => 'pending_final_disbursement',
            'disbursement_failed'            => 'disbursement_failed',
            'closed'                         => 'closed',
            'waiting_sms'                    => 'group_member_confirmation',
            'draft'                          => 'draft',
            default                          => 'draft',
        };
    }

    protected function markSyncSkipped(TempApplication $app, string $reason): void
    {
        $app->update([
            'lms_to_loan_app_sync_status' => 'completed',
            'lms_to_loan_app_synced_at' => now(),
            'lms_to_loan_app_sync_error' => substr($reason, 0, 1000),
        ]);

        Log::info('Skipped Drupal sync for application not present in loans portal', [
            'application_id' => $app->application_id,
            'reason' => $reason,
        ]);
    }

    public function failed(Throwable $exception)
    {
        $errorMessage = $exception ? $exception->getMessage() : 'Unknown failure (no exception object)';

        TempApplication::where('id', $this->tempApplicationId)->update([
            'lms_to_loan_app_sync_status' => 'failed',
            'lms_to_loan_app_synced_at' => now(),
            'lms_to_loan_app_sync_error'  => substr($errorMessage, 0, 1000),
        ]);

        Log::critical('Permanent failure syncing to Drupal after retries', [
            'application_id' => $this->tempApplicationId,
            'error'          => $errorMessage,
            'file'           => $exception ? $exception->getFile() : 'Unknown',
            'line'           => $exception ? $exception->getLine() : 'Unknown',
        ]);
    }
}
