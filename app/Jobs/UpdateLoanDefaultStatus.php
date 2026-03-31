<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GroupLoan;
use App\Services\Loan\LoanAnalysisService;
use Illuminate\Support\Facades\Log;

class UpdateLoanDefaultStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    protected $groupId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Execute the job.
     */
    public function handle(LoanAnalysisService $analysisService): void
    {

        $analysisService->analyzeGroupLoans($this->groupId);

        Log::info("Updated group loans for group {$this->groupId}.");
    }
}
