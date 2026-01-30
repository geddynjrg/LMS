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

        $query = GroupLoan::select('id','group_id', 'loan_phase_id')->where('loan_status', 'financed');

        if ($constituencyCode) {
            $query->whereHas('loanPhase', fn($q) => $q->where('constituency_code', $constituencyCode));
            $this->info("Queuing updates for constituency {$constituencyCode}...");
        } else {
            $this->info('Queuing updates for all active & financed loans...');
        }

        $groupIds = $query->pluck('group_id')->unique();

        $count = $groupIds->count();

        $this->info("Found {$count} unique groups to update.");

        $groupIds->chunk(500)->each(function ($chunk) {
            foreach ($chunk as $groupId) {
                UpdateLoanDefaultStatus::dispatch($groupId);
                $this->info("Queued job for group ID: {$groupId}");
            }
        });

        $this->info('All updates queued. Check Horizon dashboard for progress.');
    }
}
