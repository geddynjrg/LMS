<?php

namespace App\Services\Exports;

use App\Exports\CodeListExport;
use App\Models\LoanPhase;
use App\Services\Constituency\ConstituencyAllocationService;
use App\Services\Loan\LoanPhaseService;
use Maatwebsite\Excel\Facades\Excel;

class ProcessedCodeListDocumentService
{
    public function __construct(
        protected ExportService $exportService,
        protected LoanPhaseService $phaseService,
        protected ConstituencyAllocationService $allocationService,
    ) {
    }

    public function generateAndAttach(LoanPhase $phase): LoanPhase
    {
        $phase->loadMissing('constituency');

        $this->allocationService->syncAllSubmittedPhasesForConstituency(
            $phase->constituency_code,
            $phase,
        );

        $query = $this->exportService->exportCodeList($phase->id);
        $constituencyLabel = preg_replace('/[^A-Za-z0-9_-]+/', '_', $phase->constituency->constituency_name ?? 'constituency');
        $fileName = "Processed_Codelist_{$constituencyLabel}_" . now()->format('Ymd_His') . '.xlsx';

        $binary = Excel::raw(new CodeListExport($query), \Maatwebsite\Excel\Excel::XLSX);

        $this->phaseService->storeProcessedCodeListDocument($phase, $binary, $fileName);

        return $phase->fresh(['phaseApplicationDocuments']);
    }
}
