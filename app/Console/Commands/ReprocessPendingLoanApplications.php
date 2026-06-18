<?php

namespace App\Console\Commands;

use App\Jobs\ProcessLoanApplication;
use App\Models\TempApplication;
use App\Services\Loan\IncomingLoanApplicationStore;
use Illuminate\Console\Command;

class ReprocessPendingLoanApplications extends Command
{
    protected $signature = 'loan-applications:reprocess-pending
                            {--application-id= : Re-queue a single application ID}';

    protected $description = 'Re-dispatch loan application import jobs for payloads stored on disk';

    public function handle(IncomingLoanApplicationStore $store): int
    {
        $applicationId = $this->option('application-id');

        if ($applicationId) {
            if (!$store->exists($applicationId)) {
                $this->error("No stored payload for {$applicationId}");

                return self::FAILURE;
            }

            ProcessLoanApplication::dispatch($applicationId);
            $this->info("Queued import for {$applicationId}");

            return self::SUCCESS;
        }

        $queued = 0;

        foreach ($store->listApplicationIds() as $storedId) {
            $completed = TempApplication::query()
                ->where('application_id', $storedId)
                ->where('sync_status', 'completed')
                ->exists();

            if ($completed) {
                $store->delete($storedId);
                continue;
            }

            ProcessLoanApplication::dispatch($storedId);
            $queued++;
            $this->line("Queued: {$storedId}");
        }

        $this->info("Queued {$queued} pending loan application import(s).");

        return self::SUCCESS;
    }
}
