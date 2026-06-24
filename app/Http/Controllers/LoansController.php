<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\TempApplication;
use App\Models\LoanPhase;
use App\Models\Constituency;
use App\Models\County;
use App\Models\Group;
use App\Services\ApplicationFileResolver;
use App\Services\Exports\LetterOfAuthorityPdfService;
use App\Services\Exports\SignedCodeListPdfService;
use Exception;

class LoansController extends Controller
{
    public function groupLoans(Request $request, County $county, Constituency $constituency)
    {

        return View('loan.grouploans', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function receipts(Request $request, County $county, Constituency $constituency)
    {

        return View('loan.loan_receipts', [
            'constituency' => $constituency,
            'county' => $county
        ]);
    }

    public function members(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.members', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function groupLoan(Request $request, County $county, Constituency $constituency, Group $group)
    {
        return View('loan.grouploan', [
            'constituency' => $constituency,
            'county' => $county,
            'group' => $group,
        ]);
    }


    public function application(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.application', [
            'county' => $county,
            'constituency' => $constituency,
        ]);
    }


    public function loanphase(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.phase', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function applicants(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.applicants', [
            'county' => $county,
            'constituency' => $constituency,
        ]);
    }

    public function applicant(Request $request, County $county, Constituency $constituency, Group $group)
    {
        return View('loan.applicant', [
            'county' => $county,
            'constituency' => $constituency,
            'group' => $group,
        ]);
    }

    public function app_dashboard(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.app.dashboard', [
            'county' => $county,
            'constituency' => $constituency,
        ]);
    }

    public function app_loans(Request $request, County $county, Constituency $constituency)
    {
        // return View('loan.app.loans', [
        //     'county' => $county,
        //     'constituency' => $constituency,
        // ]);
    }

    public function app_loan(Request $request, County $county, Constituency $constituency, $applicationId)
    {
        return View('loan.app.view', [
            'county' => $county,
            'constituency' => $constituency,
            'applicationId' => $applicationId,
        ]);
    }

    public function app_phases( Request $request, County $county, Constituency $constituency)
    {
        return View('loan.app.phases', [
            'county' => $county,
            'constituency' => $constituency
        ]);
    }

    public function app_phase_details(Request $request, County $county, Constituency $constituency, LoanPhase $phase)
    {
        return View('loan.app.phase-details', [
            'county' => $county,
            'constituency' => $constituency,
            'phase' => $phase
        ]);
    }

    public function downloadSignedCodeList(Request $request, County $county, Constituency $constituency, LoanPhase $phase)
    {
        abort_unless($phase->constituency_code === $constituency->constituency_code, 404);

        return app(SignedCodeListPdfService::class)->downloadResponse($phase);
    }

    public function downloadProcessedCodeList(Request $request, County $county, Constituency $constituency, LoanPhase $phase)
    {
        abort_unless($phase->constituency_code === $constituency->constituency_code, 404);

        return app(\App\Http\Controllers\ProcessedCodeListController::class)->download($phase);
    }

    public function downloadLetterOfAuthority(Request $request, County $county, Constituency $constituency, LoanPhase $phase)
    {
        abort_unless($phase->constituency_code === $constituency->constituency_code, 404);

        return app(LetterOfAuthorityPdfService::class)->downloadResponse($phase);
    }

    public function app_loan_files_download(Request $request, County $county, Constituency $constituency, $applicationId, $action = 'download')
    {
        try {
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $this->verifyApplicationExists($applicationId);

            $decodedPath = trim(base64_decode($encodedPath));
            $decodedPath = ltrim($decodedPath, '/');

            $finalPath = app(ApplicationFileResolver::class)->resolve($applicationId, $decodedPath);

            if (!$finalPath) {
                Log::error('File not found in storage', [
                    'application_id' => $applicationId,
                    'decoded_path' => $decodedPath
                ]);
                abort(404, 'File not found');
            }

            $filename = basename($finalPath);

            return $this->viewOrDownloadFile($finalPath, $filename, $action);

        } catch (Exception $e) {
            Log::error('Loan File Download Error: ' . $e->getMessage());
            abort(404, 'File Error');
        }
    }

    private function verifyApplicationExists($applicationId)
    {
        if (!TempApplication::where('application_id', $applicationId)->exists()) {
            abort(404, 'Application not found');
        }
    }

    private function viewOrDownloadFile($filePath, $filename, $action = 'download')
    {
        if ($action === 'view') {
            return response()->file($filePath, [
                'Content-Disposition' => 'inline; filename="'.$filename.'"'
            ]);
        }

        return response()->download($filePath, $filename);
    }

    public function app_member_files_download(Request $request, County $county, Constituency $constituency, $applicationId, $action = 'download')
    {
        try {
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $this->verifyApplicationExists($applicationId);

            $decodedPath = ltrim(base64_decode($encodedPath), '/');

            $filename = basename($decodedPath);
            $cleanPath = preg_replace('#^(storage/|public/)#', '', $decodedPath);

            $foundPath = app(ApplicationFileResolver::class)->resolve($applicationId, $cleanPath);

            if (!$foundPath) {
                abort(404, "File not found: The document exists in the database but the file is missing from storage.");
            }

            return $this->viewOrDownloadFile($foundPath, $filename, $action);

        } catch (\Exception $e) {
            \Log::error('Member File Download Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'File not found');
        }
    }
}
