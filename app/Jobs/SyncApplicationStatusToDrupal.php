<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Services\DrupalApiService;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncApplicationStatusToDrupal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60, 300, 600];

    protected $tempApplicationId;

    public function __construct($tempApplicationId)
    {
        $this->tempApplicationId = $tempApplicationId;
    }

    public function handle(DrupalApiService $drupal): void
    {
        Log::debug('Job started', ['temp_id' => $this->tempApplicationId]);

        $app = TempApplication::select('id', 'application_id', 'status', 'lms_to_loan_app_sync_status')
            ->with(['phaseApplication' => function($query) {
                $query->select('id', 'phase_applications.temp_application_id', 'phase_applications.rejection_reason', 'phase_applications.rejection_message', 'phase_applications.status');
            }])
            ->findOrFail($this->tempApplicationId);

        if ($app->lms_to_loan_app_sync_status === 'completed') {
            return;
        }

        if ($app->status === 'submitted') {
            return;
        }

        Redis::throttle('drupal-api-sync')
            ->allow(10)
            ->every(1)
            ->then(function () use ($drupal, $app) {


                $app->update([
                'lms_to_loan_app_sync_status' => 'processing',
                'lms_to_loan_app_synced_at' => now(),
            ]);

            try {
                $payload = [
                    'status' => $this->mapToDrupalStatus($app->status),
                ];

                if ($app->status === 'rejected') {
                    $payload['rejection_reason']   = $app->phaseApplication?->rejection_reason ?? 'unspecified';
                    $payload['rejection_message']  = $app->phaseApplication?->rejection_message ?? 'No message provided';
                }

                $response = $drupal->updateApplicationStatus($app->application_id, $payload);

                if ($drupal->isSuccessful($response)) {
                    $app->update([
                        'lms_to_loan_app_sync_status' => 'completed',
                        'lms_to_loan_app_synced_at' => now(),
                        'lms_to_loan_app_sync_error'  => null,
                        'external_id'          => $response->json('data.id') ?? null,
                    ]);

                    Log::info('Successfully synced status to Drupal', [
                        'application_id' => $app->application_id,
                        'new_status'     => $app->status,
                    ]);
                } else {
                    throw new \Exception('Drupal returned unsuccessful response: ' . $response->body());
                }
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

                throw $e;  // Re-throw for retry
            }

        }, function () {
            return $this->release(10);
        });
    }

    protected function mapToDrupalStatus(string $lmsStatus): string
    {
        return match (strtolower($lmsStatus)) {
            'submitted'                      => 'submitted',
            'under_constituency_review'      => 'constituency_review',
            'under_hq_review'                => 'approved_by_constituency',
            'pending_hq_approval'            => 'under_review',
            'approved'                       => 'approved',
            'rejected'                       => 'rejected',
            'disbursed',                     => 'disbursed',
            'in_training'                    => 'training',
            'closed'                         => 'closed',
            'waiting_sms'                    => 'group_member_confirmation',
            default                          => 'draft',
        };
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
