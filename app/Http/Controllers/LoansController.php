<?php

namespace App\Http\Controllers;

use App\Models\Constituency;
use App\Models\County;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\TempApplication;
use App\Models\LoanPhase;
use Illuminate\Support\Str;
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
        return View('loan.app.loans', [
            'county' => $county,
            'constituency' => $constituency,
        ]);
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

    // Method to handle file download
    public function app_loan_files_download(Request $request, County $county, Constituency $constituency, $applicationId, $action = 'download')
    {
        try {
            // Get file path from query parameter (base64 encoded)
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $decodedPath = base64_decode($encodedPath);

            $application = TempApplication::where('application_id', $applicationId)->exists();

            if (!$application) {
                abort(404, 'Application not found');
            }

            // Set appropriate headers for download
            $filename = basename($decodedPath);


            $path = storage_path('app/public/' . $decodedPath);

            if (!file_exists($path)) {
                abort(404, "File not found on server disk.");
            }

            if ($action === 'view') {
                return response()->file($path, [
                    'Content-Disposition' => 'inline; filename="'.$filename.'"'
                ]);
            }

            return response()->download($path, $filename);

        } catch (\Exception $e) {
            abort(404, 'File not found');
        }
    }
}
