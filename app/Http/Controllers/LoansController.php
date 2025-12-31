<?php

namespace App\Http\Controllers;

use App\Models\Constituency;
use App\Models\County;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Log;

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

    public function app_phases( Request $request, County $county, Constituency $constituency) {

    }

    // Method to handle file download
    public function app_loan_files_download(Request $request, County $county, Constituency $constituency, $applicationId)
    {
        try {
            // Get file path from query parameter (base64 encoded)
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $decodedPath = base64_decode($encodedPath);

            Log::info('File download requested', [
                'application_id' => $applicationId,
                'encoded_path' => $encodedPath,
                'decoded_path' => $decodedPath
            ]);

            if (!$decodedPath || !Storage::disk('public')->exists($decodedPath)) {
                Log::error('File not found in storage', ['path' => $decodedPath]);
                abort(404, 'File not found');
            }

            $application = TempApplication::where('application_id', $applicationId)->first();

            if (!$application) {
                abort(404, 'Application not found');
            }

            // Set appropriate headers for download
            $filename = basename($decodedPath);
            $headers = [
                'Content-Type' => Storage::disk('public')->mimeType($decodedPath),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return Storage::disk('public')->download($decodedPath, $filename, $headers);

        } catch (\Exception $e) {
            Log::error('File download error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'File not found');
        }
    }

    // View file
    // public function app_loan_files_view(Request $request, County $county, Constituency $constituency, $applicationId)
    // {
    //     try {
    //         // Get the encoded file path from query parameter
    //         $encodedPath = $request->query('path');

    //         if (!$encodedPath) {
    //             abort(400, 'File path is required');
    //         }

    //         $filePath = base64_decode($encodedPath);

    //         Log::info('File view requested', [
    //             'application_id' => $applicationId,
    //             'encoded_path' => $encodedPath,
    //             'decoded_path' => $filePath,
    //             'public_disk_exists' => Storage::disk('public')->exists($filePath),
    //         ]);

    //         // Validate the application exists
    //         $application = TempApplication::where('application_id', $applicationId)->first();
    //         if (!$application) {
    //             abort(404, 'Application not found');
    //         }

    //         // Check if file exists on the public disk
    //         if (!Storage::disk('public')->exists($filePath)) {
    //             Log::error('File not found on public disk for viewing', [
    //                 'path' => $filePath,
    //                 'absolute_path' => storage_path('app/public/' . $filePath)
    //             ]);
    //             abort(404, 'File not found');
    //         }

    //         // Get file content and mime type
    //         $fileContent = Storage::disk('public')->get($filePath);
    //         $mimeType = Storage::disk('public')->mimeType($filePath);
    //         $filename = basename($filePath);

    //         // Get original filename from database if available
    //         $originalFilename = $this->getOriginalFilename($application, $filename);
    //         $displayFilename = $originalFilename ?: $filename;

    //         // ✅ Set Content-Disposition to "inline" for viewing (not "attachment")
    //         return response($fileContent, 200)
    //             ->header('Content-Type', $mimeType)
    //             ->header('Content-Disposition', 'inline; filename="' . $displayFilename . '"')
    //             ->header('Cache-Control', 'private, max-age=3600');

    //     } catch (\Exception $e) {
    //         Log::error('File view error', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'application_id' => $applicationId
    //         ]);
    //         abort(404, 'File view failed: ' . $e->getMessage());
    //     }
    // }
    public function app_loan_files_view(Request $request, County $county, Constituency $constituency, $applicationId)
    {
        try {
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $filePath = base64_decode($encodedPath);

            Log::info('PDF view requested', [
                'path' => $filePath,
                'user_agent' => $request->header('User-Agent'),
            ]);

            // Check if file exists
            if (!Storage::disk('public')->exists($filePath)) {
                Log::error('File not found', ['path' => $filePath]);
                abort(404, 'File not found');
            }

            // Get file content
            $content = Storage::disk('public')->get($filePath);
            $fileSize = strlen($content);

            // Get mime type
            $mimeType = Storage::disk('public')->mimeType($filePath);

            // For PDF, force correct mime type
            if (pathinfo($filePath, PATHINFO_EXTENSION) === 'pdf') {
                $mimeType = 'application/pdf';
            }

            // Get filename for display
            $filename = basename($filePath);

            // Get original filename from application if available
            $application = TempApplication::where('application_id', $applicationId)->first();
            if ($application) {
                $originalFilename = $this->getOriginalFilename($application, $filename);
                $filename = $originalFilename ?: $filename;
            }

            // ✅ Set proper headers for PDF viewing
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
                'Pragma' => 'public',
            ];

            // Additional headers for PDF
            if ($mimeType === 'application/pdf') {
                $headers['X-Content-Type-Options'] = 'nosniff';
                $headers['X-Frame-Options'] = 'SAMEORIGIN'; // Allow iframes from same origin
            }

            Log::info('Returning PDF with headers', $headers);

            return response($content, 200)->withHeaders($headers);

        } catch (\Exception $e) {
            Log::error('PDF view error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'File not found');
        }
    }

    // Helper method to get original filename from application data
    private function getOriginalFilename(TempApplication $application, $filePath)
    {
        $filename = basename($filePath);

        // Search through all file fields
        $fileFields = [
            'group_registration_certificate',
            'list_of_members_certificate',
            'group_meeting_minutes',
            'group_credit_history',
        ];

        foreach ($fileFields as $field) {
            $data = $application->$field;

            if (!$data) continue;

            // Single file
            if (isset($data['filename']) && $data['filename'] === $filename) {
                return $data['original_filename'] ?? $filename;
            }

            // Array of files
            if (is_array($data)) {
                foreach ($data as $file) {
                    if (isset($file['filename']) && $file['filename'] === $filename) {
                        return $file['original_filename'] ?? $filename;
                    }
                }
            }
        }

        return $filename;
    }

    // Method for encoded paths
    public function app_loan_files_download_encoded(Request $request, County $county, Constituency $constituency, $applicationId)
    {
        try {
            $encodedPath = $request->query('path');

            if (!$encodedPath) {
                abort(400, 'File path is required');
            }

            $filePath = base64_decode($encodedPath);

            Log::info('Downloading encoded file', [
                'app_id' => $applicationId,
                'path' => $filePath
            ]);

            return $this->downloadFile($applicationId, $filePath);

        } catch (\Exception $e) {
            Log::error('Download error (encoded)', ['error' => $e->getMessage()]);
            abort(404, 'File not found');
        }
    }

    // Method for direct file access
    public function app_loan_files_download_direct(Request $request, County $county, Constituency $constituency, $applicationId, $filename)
    {
        try {
            // Get the full path from the application
            $application = TempApplication::where('application_id', $applicationId)->first();

            if (!$application) {
                abort(404, 'Application not found');
            }

            // Search for the file in all file fields
            $filePath = $this->findFilePath($application, $filename);

            if (!$filePath) {
                abort(404, 'File not found in application');
            }

            Log::info('Downloading direct file', [
                'app_id' => $applicationId,
                'filename' => $filename,
                'path' => $filePath
            ]);

            return $this->downloadFile($applicationId, $filePath);

        } catch (\Exception $e) {
            Log::error('Download error (direct)', ['error' => $e->getMessage()]);
            abort(404, 'File not found');
        }
    }

    // Helper method to find file path by filename
    private function findFilePath(TempApplication $application, $filename)
    {
        $fileFields = [
            'group_registration_certificate',
            'list_of_members_certificate',
            'group_meeting_minutes',
            'group_credit_history',
        ];

        foreach ($fileFields as $field) {
            $data = $application->$field;

            if (!$data) continue;

            // Handle single file
            if (isset($data['path']) && isset($data['filename']) && $data['filename'] === $filename) {
                return $data['path'];
            }

            // Handle array of files
            if (is_array($data)) {
                foreach ($data as $file) {
                    if (isset($file['path']) && isset($file['filename']) && $file['filename'] === $filename) {
                        return $file['path'];
                    }
                }
            }
        }

        return null;
    }

    // Common download logic
    private function downloadFile($applicationId, $filePath)
    {
        if (!Storage::disk('public')->exists($filePath)) {
            Log::error('File not in storage', ['path' => $filePath]);
            abort(404, 'File not found in storage');
        }

        $filename = basename($filePath);

        return Storage::disk('public')->download($filePath, $filename, [
            'Content-Type' => Storage::disk('public')->mimeType($filePath),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
