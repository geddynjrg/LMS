<?php

namespace App\Console\Commands;

use App\Services\Loan\LoanPhaseService;
use App\Support\LoanPhaseFinancialTotals;
use Illuminate\Console\Command;

class RefreshPhaseFinancialTotals extends Command
{
    protected $signature = 'phases:refresh-financial-totals {constituency_code? : Optional constituency code to scope the refresh}';

    protected $description = 'Recompute loan phase financial totals from phase applications or historical group loans';

    public function handle(LoanPhaseService $phaseService): int
    {
        $constituencyCode = $this->argument('constituency_code');

        $count = LoanPhaseFinancialTotals::refreshAll($constituencyCode);

        if ($constituencyCode) {
            $phaseService->invalidatePhasesCache($constituencyCode);
        }

        $scope = $constituencyCode ? "constituency {$constituencyCode}" : 'all constituencies';
        $this->info("Refreshed financial totals for {$count} phase(s) in {$scope}.");

        return self::SUCCESS;
    }
}
