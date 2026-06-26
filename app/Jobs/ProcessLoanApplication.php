<?php

namespace App\Jobs;

use App\Models\TempApplication;
use App\Services\Loan\IncomingLoanApplicationStore;
use App\Services\Loan\LoanApplicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessLoanApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [15, 30, 60, 120, 300];

    public $timeout = 600;

    public $maxExceptions = 3;

    public function __construct(public string $applicationId)
    {
        $this->onQueue('loan-applications');
    }

    public function handle(
        LoanApplicationService $service,
        IncomingLoanApplicationStore $store,
    ): void {
        $lockKey = "loan_app_lock:{$this->applicationId}";
        $lock = Cache::lock($lockKey, 600);

        try {
            $lock->block(10);

            $payload = $store->get($this->applicationId);
            if (!$payload) {
                $existing = TempApplication::query()
                    ->where('application_id', $this->applicationId)
                    ->where('sync_status', 'completed')
                    ->exists();

                if ($existing) {
                    Log::info('Loan application already imported; stored payload removed', [
                        'application_id' => $this->applicationId,
                    ]);

                    return;
                }

                Log::error('No stored payload for loan application import', [
                    'application_id' => $this->applicationId,
                ]);

                return;
            }

            Log::info('Started processing loan application', [
                'application_id' => $this->applicationId,
                'attempt' => $this->attempts(),
            ]);

            $validator = \Illuminate\Support\Facades\Validator::make(
                $payload,
                $service->validationRules(),
            );

            if ($validator->fails()) {
                $errorSummary = collect($validator->errors()->all())->implode('; ');

                Log::warning('Validation failed in queue job', [
                    'application_id' => $this->applicationId,
                    'errors' => $validator->errors()->toArray(),
                ]);

                $store->recordImportError($this->applicationId, $errorSummary);

                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $service->process($validator->validated());
            $store->delete($this->applicationId);

            Log::info('Successfully processed loan application', [
                'application_id' => $this->applicationId,
                'temp_application_id' => TempApplication::where('application_id', $this->applicationId)->value('id'),
            ]);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning('Could not acquire lock — releasing job for retry', [
                'application_id' => $this->applicationId,
            ]);

            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to process loan application', [
                'application_id' => $this->applicationId,
                'error' => $e->getMessage(),
            ]);

            $store->recordImportError($this->applicationId, $e->getMessage());

            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('Loan application processing FAILED after all retries', [
            'application_id' => $this->applicationId,
            'error' => $exception->getMessage(),
        ]);

        TempApplication::query()
            ->where('application_id', $this->applicationId)
            ->update([
                'sync_status' => 'failed',
                'sync_error' => substr($exception->getMessage(), 0, 1000),
                'last_sync_at' => now(),
            ]);
    }
}
