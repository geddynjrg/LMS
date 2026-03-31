<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\TempApplication;
use App\Models\LoanPhase;

class HqApplications extends Controller
{
    public function index(Request $request) {
        return view('hq.app.application_list');
    }

    public function review(Request $request, LoanPhase $phase)
    {
        return View('hq.app.phase_review', [
            'phase' => $phase->load([
                'constituency',
                'constituencyOfficer' => fn($q) => $q->select('id', 'name', 'email', 'phone_number'),
                ]),
        ]);
    }

    public function tasks(Request $request)
    {
        return view('hq.app.phase_tasks');
    }

    public function appView(Request $request,  $applicationId)
    {
        return view('hq.app.view', [
            'applicationId' => $applicationId,
        ]);
    }

    public function app_files_download(Request $request, $applicationId, $action = 'download')
    {
        try {
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $this->verifyApplicationExists($applicationId);

            $decodedPath = trim(base64_decode($encodedPath));
            $decodedPath = ltrim($decodedPath, '/');

            $finalPath = $this->getFilePath($decodedPath);

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

    public function app_member_files_download(Request $request, $applicationId, $action = 'download')
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

            $foundPath = $this->getFilePath($cleanPath);

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

    private function verifyApplicationExists($applicationId)
    {
        if (!TempApplication::where('application_id', $applicationId)->exists()) {
            abort(404, 'Application not found');
        }
    }

    private function getFilePath($decodedPath)
    {
        if (Storage::disk('local')->exists($decodedPath)) {
            return Storage::disk('local')->path($decodedPath);
        } elseif (Storage::disk('public')->exists($decodedPath)) {
            return Storage::disk('public')->path($decodedPath);
        }
        return null;
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
}
