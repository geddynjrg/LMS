<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Jobs\UpdateLoanDefaultStatusForConstituency;

class UpdateGroupLoanDefaultStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group-loans:update-default-status {--constituency=} {--skip-loan-level} {--chunk-size=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue default status updates for all financed loans by constituency';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $constituencyCode = $this->option('constituency');
        $skipLoanLevel = $this->option('skip-loan-level');
        $chunkSize = (int) $this->option('chunk-size');

        $updateLoanLevel = !$skipLoanLevel;

        $startTime = now();
        $this->info("Starting loan default status update at: " . $startTime->format('Y-m-d H:i:s'));
        $this->info("Loan level update: " . ($updateLoanLevel ? 'ENABLED' : 'SKIPPED'));

        if ($constituencyCode) {
            // Process single constituency
            $this->processSingleConstituency($constituencyCode, $updateLoanLevel);
        } else {
            // Process all constituencies with financed loans
            $this->processAllConstituencies($updateLoanLevel, $chunkSize);
        }

        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->newLine();
        $this->info('✅ All updates queued successfully!');
        $this->info("⏱️  Total time: " . gmdate('H:i:s', $duration));
        $this->info('📊 Check Horizon dashboard for progress.');
    }

    private function processSingleConstituency(string $constituencyCode, bool $updateLoanLevel): void
    {
        $this->info("📌 Processing single constituency: {$constituencyCode}");

        // Check if constituency has any financed loans
        $loanCount = $this->getFinancedLoanCountForConstituency($constituencyCode);

        if ($loanCount === 0) {
            $this->warn("⚠️  No financed loans found for constituency {$constituencyCode}");
            return;
        }

        $this->info("   Found {$loanCount} financed loans");

        // Dispatch the job
        UpdateLoanDefaultStatusForConstituency::dispatch($constituencyCode, $updateLoanLevel)
            ->onQueue('financial-updates');

        $this->info("   ✅ Job dispatched for constituency {$constituencyCode}");
    }

    private function processAllConstituencies(bool $updateLoanLevel, int $chunkSize): void
    {
        $this->info("📊 Fetching all constituencies with financed loans...");

        $constituencies = LoanPhase::whereHas('groupLoans', function($query) {
                $query->where('loan_status', 'financed');
            })
            ->select('constituency_code')
            ->distinct()
            ->pluck('constituency_code')
            ->values();

        $totalConstituencies = $constituencies->count();

        if ($totalConstituencies === 0) {
            $this->warn('⚠️  No constituencies found with financed loans.');
            return;
        }

        $this->info("📊 Found {$totalConstituencies} constituencies with financed loans");

        // loan counts per constituency
        $constituencyStats = [];
        foreach ($constituencies as $code) {
            $count = $this->getFinancedLoanCountForConstituency($code);
            $constituencyStats[$code] = $count;
        }

        // Sort by loan count
        arsort($constituencyStats);

        $processed = 0;
        $totalJobs = 0;

        // chunks
        collect($constituencyStats)
            ->chunk($chunkSize)
            ->each(function($chunk, $chunkIndex) use ($updateLoanLevel, &$processed, &$totalJobs, $totalConstituencies) {
                $chunkNumber = $chunkIndex + 1;
                $chunkConstituencies = $chunk->keys()->toArray();
                $chunkLoanCount = array_sum($chunk->toArray());

                $this->newLine();
                $this->info("📦 Chunk {$chunkNumber}: Processing " . count($chunkConstituencies) . " constituencies (Total loans: {$chunkLoanCount})");

                $progressBar = $this->output->createProgressBar(count($chunkConstituencies));
                $progressBar->start();

                foreach ($chunkConstituencies as $constituencyCode) {
                    UpdateLoanDefaultStatusForConstituency::dispatch($constituencyCode, $updateLoanLevel)
                        ->onQueue('financial-updates');

                    $totalJobs++;
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
                $this->info("   ✅ Chunk {$chunkNumber} complete - {$totalJobs} jobs dispatched so far");
            });

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   - Total constituencies processed: {$totalConstituencies}");
        $this->info("   - Total jobs dispatched: {$totalJobs}");
        $this->info("   - Average loans per constituency: " . round(array_sum($constituencyStats) / $totalConstituencies, 2));
    }

    /**
     * Get count of financed loans for a constituency
     */
    private function getFinancedLoanCountForConstituency(string $constituencyCode): int
    {
        return GroupLoan::where('loan_status', 'financed')
            ->whereHas('loanPhase', function($query) use ($constituencyCode) {
                $query->where('constituency_code', $constituencyCode);
            })
            ->count();
    }
}
