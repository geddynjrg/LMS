<?php

namespace App\Http\Controllers;

use App\Exports\CodeListExport;
use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Services\Constituency\ConstituencyAllocationService;
use App\Services\Exports\ExportService;
use App\Services\Exports\SignedCodeListPdfService;
use App\Support\HqAccess;
use Maatwebsite\Excel\Facades\Excel;

class ProcessedCodeListController extends Controller
{
    protected function authorizeHqPhaseReview(LoanPhase $phase): void
    {
        abort_unless(
            HqAccess::phaseCanBeViewedBy(auth()->user(), $phase),
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
            ->with([
                'tempApplication:id,group_name,business_type,nature_of_business,loan_amount',
                'linkedGroup:id,name',
            ])
            ->get()
            ->map(fn (PhaseApplication $app) => [
                'group_name' => $app->linkedGroup?->name ?? $app->tempApplication?->group_name,
                'gfs_code' => $app->gfs_code,
                'loan_level' => $app->loan_level,
                'amount_applied' => (float) ($app->amount_applied ?? $app->tempApplication?->loan_amount ?? 0),
                'amount_approved' => (float) ($app->amount_approved ?? 0),
                'status' => $app->status,
            ]);

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
