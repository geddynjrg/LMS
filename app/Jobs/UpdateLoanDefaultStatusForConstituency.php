<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Loan\LoanAnalysisService;
use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Models\Group;
use App\Models\Wards;

class UpdateLoanDefaultStatusForConstituency implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout for large constituencies
    public $tries = 3; // Retry 3 times on failure

    protected $constituencyCode;
    protected $updateLoanLevel;
    protected $analysisService;

    /**
     * Create a new job instance.
     */
    public function __construct(string $constituencyCode, bool $updateLoanLevel = false)
    {
        $this->constituencyCode = $constituencyCode;
        $this->updateLoanLevel = $updateLoanLevel;
    }

    /**
     * Execute the job.
     */
    public function handle(LoanAnalysisService $analysisService): void
    {
        $this->analysisService = $analysisService;

        Log::info("Starting loan default status update for constituency: {$this->constituencyCode}", [
            'update_loan_level' => $this->updateLoanLevel
        ]);

        try {
            DB::beginTransaction();

            if ($this->updateLoanLevel) {
                $this->updateLoanLevels();
            }

            $groupIds = Group::select(
                    'groups.id',
                    'groups.ward_id',
                )
                ->join('wards', 'wards.id', '=', 'groups.ward_id')
                ->where('wards.constituency_code', $this->constituencyCode)
                ->pluck('groups.id');

            // dd($groupIds);
            foreach ($groupIds as $groupId) {
                $this->analysisService->analyzeGroupLoans($groupId);
            }

            DB::commit();

            Log::info("Completed loan default status update for constituency: {$this->constituencyCode}", [
                'groups_processed' => $groupIds->count(),
                'loan_levels_updated' => $this->updateLoanLevel
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to update loan default status for constituency: {$this->constituencyCode}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function updateLoanLevels(): void
    {
        $sql = "
            UPDATE group_loans gl
            JOIN (
                SELECT
                    id,
                    group_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY group_id
                        ORDER BY
                            -- Order by loan_status first (financed first, then others)
                            CASE WHEN loan_status = 'financed' THEN 1 ELSE 2 END,
                            -- Then by date (NULLs last)
                            CASE WHEN disbursement_date IS NULL THEN 1 ELSE 0 END,
                            disbursement_date ASC,
                            id ASC
                    ) AS rn
                FROM group_loans
                WHERE group_id IN (
                    SELECT DISTINCT gl2.group_id
                    FROM group_loans gl2
                    JOIN loan_phases lp ON gl2.loan_phase_id = lp.id
                    WHERE lp.constituency_code = ?
                )
            ) AS ranked ON gl.id = ranked.id
            SET gl.loan_level = ranked.rn
            WHERE gl.loan_status = 'financed'";

        $affected = DB::update($sql, [$this->constituencyCode]);

        Log::info("Updated loan levels for {$affected} loans in constituency: {$this->constituencyCode}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateLoanDefaultStatusForConstituency job failed for constituency: {$this->constituencyCode}", [
            'error' => $exception->getMessage(),
            'update_loan_level' => $this->updateLoanLevel
        ]);
    }
}
