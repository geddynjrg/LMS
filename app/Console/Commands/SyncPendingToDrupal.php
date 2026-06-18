<?php

namespace App\Console\Commands;

use App\Jobs\SyncApplicationStatusToDrupal;
use App\Models\TempApplication;
use App\Services\DrupalApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPendingToDrupal extends Command
{
    protected $signature = 'drupal:sync-pending
                            {--reconcile : Re-check completed syncs where Drupal may be behind LMS}
                            {--limit=50 : Maximum applications to dispatch per run}';

    protected $description = 'Sync pending/failed LMS status changes to Drupal and optionally reconcile stale completed syncs';

    public function handle(DrupalApiService $drupal): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $reconciled = 0;

        if ($this->option('reconcile')) {
            $reconciled = $this->reconcileStaleCompletedSyncs($drupal, $limit);
            if ($reconciled > 0) {
                $this->info("Re-queued {$reconciled} stale completed sync(s).");
            }
        }

        $remaining = max(0, $limit - $reconciled);

        $pending = TempApplication::query()
            ->select('id', 'application_id', 'lms_to_loan_app_sync_status')
            ->whereIn('lms_to_loan_app_sync_status', ['pending', 'failed'])
            ->where('status', '!=', 'submitted')
            ->where(function ($query) {
                $query->whereNull('lms_to_loan_app_sync_error')
                    ->orWhere('lms_to_loan_app_sync_error', 'not like', '%not found in Drupal%')
                    ->orWhere('lms_to_loan_app_sync_error', 'not like', '%Application with ID%not found%');
            })
            ->orderByRaw("FIELD(lms_to_loan_app_sync_status, 'pending', 'failed')")
            ->orderBy('lms_to_loan_app_synced_at')
            ->limit($remaining)
            ->get();

        if ($pending->isEmpty() && $reconciled === 0) {
            $this->info('No pending, failed, or stale syncs found.');
            return self::SUCCESS;
        }

        if ($pending->isNotEmpty()) {
            $this->info("Dispatching {$pending->count()} pending/failed sync job(s)...");

            foreach ($pending as $app) {
                try {
                    SyncApplicationStatusToDrupal::pushNow($app->id);
                    $this->line("  synced {$app->application_id}");
                } catch (\Throwable $e) {
                    $this->error("  failed {$app->application_id}: {$e->getMessage()}");
                }
            }
        }

        $this->info('Drupal sync completed.');

        return self::SUCCESS;
    }

    protected function reconcileStaleCompletedSyncs(DrupalApiService $drupal, int $limit): int
    {
        $candidates = TempApplication::query()
            ->select('id', 'application_id', 'status')
            ->where('lms_to_loan_app_sync_status', 'completed')
            ->where('status', '!=', 'submitted')
            ->where('updated_at', '>=', now()->subDays(30))
            ->orderByDesc('lms_to_loan_app_synced_at')
            ->limit($limit)
            ->get();

        $requeued = 0;

        foreach ($candidates as $app) {
            if ($requeued >= $limit) {
                break;
            }

            try {
                $drupalStatus = $drupal->getApplication($app->application_id)['status'] ?? null;
                if (!$drupalStatus) {
                    continue;
                }

                $fullApp = TempApplication::with(['phaseApplication.loanPhase'])->find($app->id);
                if (!$fullApp) {
                    continue;
                }

                $target = SyncApplicationStatusToDrupal::resolveTargetDrupalStatus($fullApp);

                if (!SyncApplicationStatusToDrupal::isDrupalBehind($drupalStatus, $target)) {
                    continue;
                }

                $fullApp->update([
                    'lms_to_loan_app_sync_status' => 'pending',
                    'lms_to_loan_app_sync_error' => null,
                ]);

                SyncApplicationStatusToDrupal::pushNow($fullApp->id);
                $requeued++;

                Log::warning('Re-queued stale Drupal sync', [
                    'application_id' => $fullApp->application_id,
                    'drupal_status' => $drupalStatus,
                    'target_status' => $target,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed reconciling Drupal sync', [
                    'application_id' => $app->application_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $requeued;
    }
}
