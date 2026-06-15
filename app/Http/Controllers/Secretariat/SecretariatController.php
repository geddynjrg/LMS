<?php

namespace App\Http\Controllers\Secretariat;

use App\Http\Controllers\Controller;
use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Services\Loan\TrainingCertificateService;
use App\Services\Exports\ExportService;
use App\Exports\CodeListExport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SecretariatController extends Controller
{
    public function index()
    {
        return view('secretariat.index');
    }

    public function review(LoanPhase $phase)
    {
        return view('secretariat.review', compact('phase'));
    }

    public function downloadCodeList(LoanPhase $phase)
    {
        $phase->loadMissing('constituency:constituency_code,constituency_name');

        $query = app(ExportService::class)->exportCodeList($phase->id);
        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;
        $fileName = 'Code_List_' . str_replace(' ', '_', $constituencyName) . '_' . $phase->phase . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CodeListExport($query), $fileName);
    }

    public function downloadTrainingCertificate(PhaseApplication $phaseApplication)
    {
        $phaseApplication->loadMissing('loanPhase', 'tempApplication:id,application_id');

        $certificateService = app(TrainingCertificateService::class);
        abort_unless($certificateService->isOfficerCertificateAvailable($phaseApplication), 404);

        $enforceWait = !$certificateService->isOfficerCertificateAvailable($phaseApplication);
        $path = $certificateService->generateAndStore($phaseApplication, $enforceWait);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            'training-certificate-' . ($phaseApplication->tempApplication?->application_id ?? $phaseApplication->id) . '.pdf'
        );
    }
}
