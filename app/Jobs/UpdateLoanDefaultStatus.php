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

    protected $loanId;

    /**
     * Create a new job instance.
     */
    public function __construct($loanId)
    {
        $this->loanId = $loanId;
    }

    /**
     * Execute the job.
     */
    public function handle(LoanAnalysisService $analysisService): void
    {
        $loan = GroupLoan::find($this->loanId);

        if (!$loan) {
            Log::warning("Loan {$this->loanId} not found for default status update.");
            return;
        }

        $groupLoans = GroupLoan::where('group_id', $loan->group_id)
            ->orderBy('disbursement_date', 'asc')
            ->get();

        $analysisService->analyzeGroupLoans($groupLoans);

        Log::info("Updated group loans for group {$loan->group_id}.");
    }
}
