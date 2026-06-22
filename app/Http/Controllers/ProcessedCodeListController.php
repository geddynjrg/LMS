<?php

namespace App\Http\Controllers;

use App\Exports\CodeListExport;
use App\Models\LoanPhase;
use App\Services\Constituency\ConstituencyAllocationService;
use App\Services\Exports\ExportService;
use App\Services\Exports\SignedCodeListPdfService;
use Maatwebsite\Excel\Facades\Excel;

class ProcessedCodeListController extends Controller
{
    public function download(LoanPhase $phase)
    {
        $phase->loadMissing('constituency:constituency_code,constituency_name');

        app(ConstituencyAllocationService::class)->syncPhaseIssuedFromLoanPhase($phase);

        $query = app(ExportService::class)->exportCodeList($phase->id);
        $constituencyName = str_replace(' ', '_', $phase->constituency?->constituency_name ?? $phase->constituency_code);
        $fileName = "Processed_Codelist_{$constituencyName}_{$phase->phase}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CodeListExport($query), $fileName);
    }

    public function downloadSigned(LoanPhase $phase)
    {
        app(ConstituencyAllocationService::class)->syncPhaseIssuedFromLoanPhase($phase);

        return app(SignedCodeListPdfService::class)->downloadResponse($phase);
    }
}
