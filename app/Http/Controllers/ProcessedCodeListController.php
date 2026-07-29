<?php

namespace App\Http\Controllers;

use App\Exports\CodeListExport;
use App\Models\LoanPhase;
use App\Services\Constituency\ConstituencyAllocationService;
use App\Services\Exports\ExportService;
use App\Services\Exports\SignedCodeListPdfService;
use App\Support\CodeListRecordMapper;
use App\Support\PhaseCodeListAccess;
use Maatwebsite\Excel\Facades\Excel;

class ProcessedCodeListController extends Controller
{
    protected function authorizeHqPhaseReview(LoanPhase $phase): void
    {
        abort_unless(
            PhaseCodeListAccess::phaseCanBeViewedBy(auth()->user(), $phase),
            403,
            'You do not have permission to access this phase code list.'
        );
    }

    public function preview(LoanPhase $phase)
    {
        $this->authorizeHqPhaseReview($phase);

        $phase->loadMissing('constituency:constituency_code,constituency_name');

        app(ConstituencyAllocationService::class)->syncAllSubmittedPhasesForConstituency($phase->constituency_code, $phase);

        $rows = app(ExportService::class)->exportCodeList($phase->id)
            ->get()
            ->map(fn ($record) => CodeListRecordMapper::previewRow($record));

        return view('exports.code-list-preview', [
            'phase' => $phase,
            'rows' => $rows,
            'generatedAt' => now(),
        ]);
    }

    public function download(LoanPhase $phase)
    {
        $this->authorizeHqPhaseReview($phase);

        $phase->loadMissing('constituency:constituency_code,constituency_name');

        app(ConstituencyAllocationService::class)->syncAllSubmittedPhasesForConstituency($phase->constituency_code, $phase);

        $query = app(ExportService::class)->exportCodeList($phase->id);
        $constituencyName = str_replace(' ', '_', $phase->constituency?->constituency_name ?? $phase->constituency_code);
        $fileName = "Processed_Codelist_{$constituencyName}_{$phase->phase}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CodeListExport($query), $fileName);
    }

    public function downloadSigned(LoanPhase $phase)
    {
        $this->authorizeHqPhaseReview($phase);

        app(ConstituencyAllocationService::class)->syncAllSubmittedPhasesForConstituency($phase->constituency_code, $phase);

        return app(SignedCodeListPdfService::class)->downloadResponse($phase);
    }
}
