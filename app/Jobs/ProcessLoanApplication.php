<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\TempApplication;
use App\Services\Loan\LoanApplicationService;
use Throwable;

class ProcessLoanApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [15, 30, 60, 120, 300];
    public $timeout = 600; // 10 minutes max
    public $maxExceptions = 3;

    protected $payload;
    protected $applicationId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->applicationId = $payload['application_id'] ?? 'unknown';
        $this->onQueue('loan-applications'); // dedicated queue
    }

    /**
     * Execute the job.
     */
    public function handle(LoanApplicationService $service): void
    {
        $lockKey = "loan_app_lock:{$this->applicationId}";
        $lock = Cache::lock($lockKey, 600);

        try {
            $lock->block(10);

            Log::info("Lock acquired for application: {$this->applicationId}");

            Log::info('Started processing loan application', [
                'application_id' => $this->applicationId,
                'attempt' => $this->attempts()
            ]);

            $validator = \Illuminate\Support\Facades\Validator::make(
                $this->payload,
                $service->validationRules()
            );

            if ($validator->fails()) {
                Log::warning('Validation failed in queue job', [
                    'application_id' => $this->applicationId,
                    'errors' => $validator->errors()->toArray()
                ]);

                $lock->release();
                return;
            }

            $service->process($validator->validated());

            Log::info('Successfully processed loan application', [
                'application_id' => $this->applicationId,
                'temp_application_id' => TempApplication::where('application_id', $this->applicationId)->value('id')
            ]);

        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning('Could not acquire lock — already processing or stuck', [
                'application_id' => $this->applicationId
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process loan application', [
                'application_id' => $this->applicationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    public function failed(Throwable $exception)
    {
        Log::critical('Loan application processing FAILED after all retries', [
            'application_id' => $this->applicationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
