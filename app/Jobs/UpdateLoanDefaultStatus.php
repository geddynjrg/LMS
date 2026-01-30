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
    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Execute the job.
     */
    public function handle(LoanAnalysisService $analysisService): void
    {
        $groupLoans = GroupLoan::where('group_id', $this->groupId)
            ->where('loan_status', 'financed')
            ->orderBy('disbursement_date', 'asc')
            ->get();

        if ($groupLoans->isEmpty()) {
            Log::warning("No loans found for group {$this->groupId}.");
            return;
        }

        $analysisService->analyzeGroupLoans($groupLoans);

        Log::info("Updated group loans for group {$this->groupId}.");
    }
}
