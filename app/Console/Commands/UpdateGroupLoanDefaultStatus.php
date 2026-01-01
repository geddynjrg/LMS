<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GroupLoan;
use App\Jobs\UpdateLoanDefaultStatus;

class UpdateGroupLoanDefaultStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group-loans:update-default-status {--constituency= : Specific constituency code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue default status updates for active/defaulted loans';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $constituencyCode = $this->option('constituency');

        $query = GroupLoan::where('status', 'active')
                  ->where('loan_status', 'financed');

        if ($constituencyCode) {
            $query->whereHas('loanPhase', fn($q) => $q->where('constituency_code', $constituencyCode));
            $this->info("Queuing updates for constituency {$constituencyCode}...");
        } else {
            $this->info('Queuing updates for all active & financed loans...');
        }

        $count = $query->count();
        $this->info("Found {$count} loans to update.");

        $query->chunk(500, function ($loans) {
            foreach ($loans as $loan) {
                UpdateLoanDefaultStatus::dispatch($loan->id);
            }
        });

        $this->info('All updates queued. Check Horizon dashboard for progress.');
    }
}
