<?php

namespace App\Console\Commands;

use App\Services\PhaseAgingAnalyticsService;
use Illuminate\Console\Command;

class SnapshotPhaseAgingCommand extends Command
{
    protected $signature = 'analytics:snapshot-phase-aging';

    protected $description = 'Snapshot constituency phase-aging metrics for Core LMS';

    public function handle(PhaseAgingAnalyticsService $aging): int
    {
        $count = $aging->snapshot();
        $this->info("Stored {$count} phase-aging snapshot row(s).");

        return self::SUCCESS;
    }
}
