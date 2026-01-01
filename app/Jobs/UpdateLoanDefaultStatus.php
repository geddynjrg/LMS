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

        $result = $analysisService->analyzeSingleLoan($loan);

        $newStatus = $loan->status;

        if ($result['arrears'] <= 0 && $loan->amount_repaid >= $loan->amount_issued) {
            $newStatus = 'completed';
        }

        if ($loan->status === 'Pending' && $result['arrears'] > 0) $newStatus = 'active';


        $loan->update([
            'is_defaulted' => $result['is_defaulted'],
            'months_defaulted' => $result['months_defaulted'],
            'total_arrears' => $result['arrears'],
            'default_status_updated_at' => now(),
            'status' => $newStatus,
        ]);

        Log::info("Updated loan {$this->loanId}: Status = {$newStatus}, Defaulted = {$result['is_defaulted']}");
    }
}
