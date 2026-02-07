<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\LoanApplication as Application;
use App\Models\GroupMember;
use App\Models\TempUser;
use App\Models\TempApplication;
use App\Models\TempFile;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Wards as Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Helpers\FileUploadHelper;
use App\Jobs\ProcessLoanApplication;


class LoanApplicationController extends Controller
{
    // public function store(Request $request): JsonResponse
    // {
    //     Log::info('📥 Received application data from external system', [
    //         'ip' => $request->ip(),
    //         'user_agent' => $request->userAgent(),
    //         'payload_keys' => array_keys($request->all()),
    //         'data_sample' => json_encode($request->all())
    //     ]);

    //     try {
    //         // Validate the incoming data
            // $validator = Validator::make($request->all(), [
            //     'application_id' => 'required|string|max:255',
            //     'identifier_type' => 'required|in:EMAIL,PHONE,NAME',
            //     'owner_identifier' => 'required|string|max:255',
            //     'phone_number' => 'nullable|string|max:20',
            //     'status' => 'required|in:draft,submitted,under_review,approved,rejected,disbursed,closed',

            //     // Group data
            //     'group_name' => 'required|string|max:255',
            //     'group_registration_number' => 'required|string|max:255',
            //     'date_registered' => 'required|date',
            //     'group_registration_certificate' => 'nullable|array',
            //     'list_of_members_certificate' => 'nullable|array',
            //     'group_meeting_minutes' => 'nullable|array',
            //     'group_background' => 'nullable|array',
            //     'key_activities' => 'nullable|array',
            //     'other_activities' => 'nullable|string',
            //     'group_credit_history' => 'nullable|array',


            //     // Location data
            //     'county' => 'required|string|max:255',
            //     'constituency' => 'required|string|max:255',
            //     'ward' => 'required|string|max:255',
            //     'meeting_point' => 'required|string|max:255',
            //     'nearest_landmark' => 'required|string|max:255',
            //     'nature_of_group' => 'required|string|max:255',

            //     // Group members
            //     'group_members' => 'required|array|min:1',
            //     'group_members.*.name' => 'required|string|max:255',
            //     'group_members.*.id_number' => 'required|string|max:50',
            //     'group_members.*.gender' => 'required|in:male,female,other',
            //     'group_members.*.phone_number' => 'required|string|max:20',
            //     'group_members.*.position' => 'nullable|string|max:255',
            //     'group_members.*.economic_activity' => 'nullable|string|max:255',
            //     'group_members.*.photo_member_id' => 'nullable|array',
            //     'group_members.*.person_with_disability' => 'nullable|boolean',
            //     'group_members.*.certificate_of_disability' => 'nullable|array',
            //     'group_members.*.pwd_number' => 'nullable|string|max:50',

            //     // Confirmation fields
            //     'group_members.*.confirmation_token' => 'nullable|string|max:64',
            //     'group_members.*.confirmation_status' => 'nullable|in:pending,sent,confirmed,declined,failed',
            //     'group_members.*.confirmation_sent_at' => 'nullable|integer',
            //     'group_members.*.confirmation_confirmed_at' => 'nullable|integer',
            //     'group_members.*.requires_confirmation' => 'nullable|boolean',

            //     // Loan conditions
            //     'loan_type' => 'required|string|max:255',
            //     'loan_amount' => 'required|numeric|min:0',
            //     'proposed_repayment_period' => 'required|integer|min:1',
            //     'bank_name' => 'required|string|max:255',
            //     'institution_type' => 'nullable|string|max:255',
            //     'bank_branch' => 'required|string|max:255',
            //     'bank_account_number' => 'required|string|max:255',

            //     // Business information
            //     'nature_of_business' => 'required|string|max:255',
            //     'business_type' => 'required|array',
            //     'years_in_operation' => 'required|integer|min:0',
            //     'average_income' => 'required|numeric|min:0',
            //     'expenses' => 'nullable|array',
            // ]);

    //         if ($validator->fails()) {
    //             Log::warning('Validation failed for application data', [
    //                 'errors' => $validator->errors()->toArray(),
    //                 'application_id' => $request->input('application_id')
    //             ]);

    //             return response()->json([
    //                 'success' => false,
    //                 'error' => 'validation_error',
    //                 'message' => 'Validation failed',
    //                 'errors' => $validator->errors()->toArray(),
    //             ], 422);
    //         }

    //         $validatedData = $validator->validated();

    //         $application = null;

    //         DB::transaction(function () use ($validatedData, &$application) {
    //             // Step 1: Find or create temp user
    //             $user = $this->findOrCreateTempUser($validatedData);

    //             // Step 2: Map location data to existing database codes
    //             $locationCodes = $this->mapLocationCodes($validatedData);

    //             // Step 3: Create or update temporary application
    //             $application = $this->createOrUpdateTempApplication($validatedData, $user->id, $locationCodes);

    //             // Step 4: Sync group members
    //             $this->syncTempGroupMembers($application, $validatedData['group_members']);

    //             // Step 5: Sync expenses if any
    //             if (!empty($validatedData['expenses'])) {
    //                 $this->syncTempExpenses($application, $validatedData['expenses']);
    //             }

    //             Log::info('✅ Application stored in temporary tables', [
    //                 'application_id' => $application->application_id,
    //                 'temp_application_id' => $application->id,
    //                 'member_count' => count($validatedData['group_members'])
    //             ]);
    //         });

    //         if ($application === null) {
    //             Log::error('❌ Application processing failed without exception', [
    //                 'application_id' => $request->input('application_id')
    //             ]);

    //             return response()->json([
    //                 'success' => false,
    //                 'error' => 'processing_error',
    //                 'message' => 'Failed to process application',
    //             ], 500);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Application received and stored successfully',
    //             'data' => [
    //                 // add id of temp application
    //                 'id' => $application->id,
    //                 'status' => 'received',
    //             ],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         Log::error('❌ Error processing application', [
    //             'application_id' => $request->input('application_id'),
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'error' => 'processing_error',
    //             'message' => 'Failed to process application: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function store(Request $request): JsonResponse
    {
        Log::info('API: Loan application received', [
            'application_id' => $request->input('application_id'),
            'ip' => $request->ip(),
            'payload_keys' => array_keys($request->all()),
            'data_sample' => json_encode($request->all())
        ]);

        // Minimal validation — only what’s needed to accept
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|string|max:255',
            'status' => 'required|in:draft,submitted,under_review,approved,rejected,disbursed,closed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => 'validation_error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors()->toArray(), 
            ], 422);
        }

        ProcessLoanApplication::dispatch($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Application received successfully. Processing in background.',
            'data' => [ // This key is required by your Zod schema
                'application_id' => $request->input('application_id'),
                'status'         => 'queued',
                'created'        => now()->toDateTimeString(), // Matches "created" in Zod
            ]
        ], 202);
    }

    private function findOrCreateTempUser(array $data): TempUser
    {
        $userIdentifier = [
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier']
        ];

        return TempUser::firstOrCreate($userIdentifier, [
            'external_id' => $data['owner_identifier'],
            'email' => $data['identifier_type'] === 'EMAIL' ? $data['owner_identifier'] : null,
            'phone_number' => $data['identifier_type'] === 'PHONE' ? $data['owner_identifier'] : ($data['phone_number'] ?? null),
            'name' => $data['identifier_type'] === 'NAME' ? $data['owner_identifier'] : null,
        ]);
    }

    private function mapLocationCodes(array $data): array
    {
        $county = County::where('county_name', 'like', '%' . $data['county'] . '%')->first();
        $constituency = Constituency::where('constituency_name', 'like', '%' . $data['constituency'] . '%')
            ->when($county, function ($query) use ($county) {
                return $query->where('county_code', $county->county_code);
            })
            ->first();

        $ward = Ward::where('ward_name', 'like', '%' . $data['ward'] . '%')
            ->when($constituency, function ($query) use ($constituency) {
                return $query->where('constituency_code', $constituency->constituency_code);
            })
            ->first();

        return [
            'county_code' => $county->county_code ?? null,
            'constituency_code' => $constituency->constituency_code ?? null,
            'ward_id' => $ward->id ?? null,
        ];
    }

    private function createOrUpdateTempApplication(array $data, int $userId, array $locationCodes): TempApplication
    {
        // Check if application already exists
        $existingApplication = TempApplication::where('application_id', $data['application_id'])->first();

        // Save files and get their paths - ALL files go under application_id directory
        $filesData = $this->saveApplicationFiles($data, $existingApplication);

        $applicationData = [
            'temp_user_id' => $userId,
            'status' => $data['status'],
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
            'phone_number' => $data['phone_number'] ?? null,

            // Group information with file paths instead of base64
            'group_name' => $data['group_name'],
            'group_registration_number' => $data['group_registration_number'],
            'date_registered' => $data['date_registered'],
            'group_registration_certificate' => $filesData['group_registration_certificate'] ?? null,
            'list_of_members_certificate' => $filesData['list_of_members_certificate'] ?? null,
            'group_meeting_minutes' => $filesData['group_meeting_minutes'] ?? null,
            'group_background' => $data['group_background'] ?? null,
            'key_activities' => $data['key_activities'] ?? null,
            'other_activities' => $data['other_activities'] ?? null,
            'group_credit_history' => $filesData['group_credit_history'] ?? null,

            // Location
            'county_name' => $data['county'],
            'constituency_name' => $data['constituency'],
            'ward_name' => $data['ward'],
            'meeting_point' => $data['meeting_point'],
            'nearest_landmark' => $data['nearest_landmark'],
            'nature_of_group' => $data['nature_of_group'],
            'county_code' => $locationCodes['county_code'],
            'constituency_code' => $locationCodes['constituency_code'],
            'ward_id' => $locationCodes['ward_id'],

            // Loan information
            'loan_type' => $data['loan_type'],
            'loan_amount' => $data['loan_amount'],
            'proposed_repayment_period' => $data['proposed_repayment_period'],
            'bank_name' => $data['bank_name'],
            'bank_branch' => $data['bank_branch'],
            'bank_account_number' => $data['bank_account_number'],

            // Business information
            'nature_of_business' => $data['nature_of_business'],
            'business_type' => $data['business_type'],
            'years_in_operation' => $data['years_in_operation'],
            'average_income' => $data['average_income'],
            'expenses' => $data['expenses'] ?? null,

            'raw_data' => json_encode($data),
        ];

        return TempApplication::updateOrCreate(
            ['application_id' => $data['application_id']],
            $applicationData
        );
    }

    /**
     * Save all application files to storage under application_id directory
    */
    private function saveApplicationFiles(array $data, ?TempApplication $existingApplication = null): array
    {
        $savedFiles = [];

        // Use application_id as the base directory
        $applicationId = $data['application_id'];
        $baseDirectory = 'applications/' . $applicationId;

        // Determine if we need to cleanup (is update with new files)
        $isUpdate = $existingApplication !== null;

        Log::info('Saving files for application', [
            'application_id' => $applicationId,
            'base_directory' => $baseDirectory,
            'is_update' => $isUpdate,
            'has_existing_files' => $existingApplication ? 'yes' : 'no'
        ]);

        // Save single files with cleanup if it's an update
        $singleFiles = [
            'group_registration_certificate',
            'list_of_members_certificate',
        ];

        foreach ($singleFiles as $fileKey) {
            if (!empty($data[$fileKey]) && is_array($data[$fileKey]) && isset($data[$fileKey]['data'])) {

                // Check if this file has changed compared to existing
                $shouldCleanup = $this->shouldCleanupFile($existingApplication, $fileKey, $data[$fileKey]);

                $savedFile = FileUploadHelper::saveBase64File(
                    $data[$fileKey],
                    $baseDirectory . '/' . $fileKey,
                    'public',
                    $shouldCleanup
                );

                if ($savedFile) {
                    $savedFiles[$fileKey] = $savedFile;
                    Log::info("Saved {$fileKey} file", [
                        'application_id' => $applicationId,
                        'original_filename' => $savedFile['original_filename'],
                        'path' => $savedFile['path'],
                        'cleanup_performed' => $shouldCleanup
                    ]);
                }
            } elseif ($isUpdate) {
                // If file is not in new data but existed before, keep existing
                $existingFile = $existingApplication->{$fileKey} ?? null;
                if ($existingFile) {
                    $savedFiles[$fileKey] = $existingFile;
                    Log::info("Keeping existing {$fileKey} file for update", [
                        'application_id' => $applicationId,
                        'original_filename' => $existingFile['original_filename'] ?? 'unknown'
                    ]);
                }
            }
        }

        // Save group meeting minutes (array of files)
        if (!empty($data['group_meeting_minutes']) && is_array($data['group_meeting_minutes'])) {

            // Check if meeting minutes have changed
            $shouldCleanup = $this->shouldCleanupMultipleFiles(
                $existingApplication,
                'group_meeting_minutes',
                $data['group_meeting_minutes']
            );

            $savedMinutes = FileUploadHelper::saveBase64Files(
                $data['group_meeting_minutes'],
                $baseDirectory . '/meeting_minutes',
                'public',
                $shouldCleanup
            );

            if (!empty($savedMinutes)) {
                $savedFiles['group_meeting_minutes'] = $savedMinutes;
                Log::info("Saved group meeting minutes", [
                    'application_id' => $applicationId,
                    'count' => count($savedMinutes),
                    'files' => array_column($savedMinutes, 'original_filename'),
                    'cleanup_performed' => $shouldCleanup
                ]);
            }
        } elseif ($isUpdate && $existingApplication->group_meeting_minutes) {
            // Keep existing meeting minutes if no new ones provided
            $savedFiles['group_meeting_minutes'] = $existingApplication->group_meeting_minutes;
            Log::info("Keeping existing group meeting minutes for update", [
                'application_id' => $applicationId,
                'count' => count($existingApplication->group_meeting_minutes)
            ]);
        }

        // Save group credit history files
        if (!empty($data['group_credit_history']) && is_array($data['group_credit_history'])) {

            $shouldCleanup = $this->shouldCleanupMultipleFiles(
                $existingApplication,
                'group_credit_history',
                $data['group_credit_history']
            );

            $savedCreditHistory = FileUploadHelper::saveBase64Files(
                $data['group_credit_history'],
                $baseDirectory . '/credit_history',
                'public',
                $shouldCleanup
            );

            if (!empty($savedCreditHistory)) {
                $savedFiles['group_credit_history'] = $savedCreditHistory;
                Log::info("Saved group credit history files", [
                    'application_id' => $applicationId,
                    'count' => count($savedCreditHistory),
                    'cleanup_performed' => $shouldCleanup
                ]);
            }
        } elseif ($isUpdate && $existingApplication->group_credit_history) {
            // Keep existing credit history if no new ones provided
            $savedFiles['group_credit_history'] = $existingApplication->group_credit_history;
            Log::info("Keeping existing credit history for update", [
                'application_id' => $applicationId,
                'count' => count($existingApplication->group_credit_history)
            ]);
        }

        return $savedFiles;
    }

    /**
     * Check if a single file needs cleanup (has changed)
     */
    private function shouldCleanupFile(?TempApplication $existingApplication, string $fileKey, array $newFileData): bool
    {
        if (!$existingApplication) {
            return false; // New application, no cleanup needed
        }

        $existingFile = $existingApplication->{$fileKey} ?? null;

        // If no existing file, no cleanup needed
        if (!$existingFile) {
            return false;
        }

        // Compare by original filename
        $existingFilename = $existingFile['original_filename'] ?? null;
        $newFilename = $newFileData['filename'] ?? null;

        // Also check if file content might have changed (optional: you could add MD5 hash comparison)
        return $existingFilename !== $newFilename;
    }

    /**
     * Check if multiple files need cleanup (have changed)
     */
    private function shouldCleanupMultipleFiles(?TempApplication $existingApplication, string $fileKey, array $newFilesData): bool
    {
        if (!$existingApplication) {
            return false; // New application, no cleanup needed
        }

        $existingFiles = $existingApplication->{$fileKey} ?? [];

        // If no existing files, no cleanup needed
        if (empty($existingFiles)) {
            return false;
        }

        // Extract filenames for comparison
        $existingFilenames = [];
        foreach ($existingFiles as $file) {
            if (isset($file['original_filename'])) {
                $existingFilenames[] = $file['original_filename'];
            }
        }

        $newFilenames = [];
        foreach ($newFilesData as $file) {
            if (isset($file['filename'])) {
                $newFilenames[] = $file['filename'];
            }
        }

        // Check if arrays are different
        sort($existingFilenames);
        sort($newFilenames);

        return $existingFilenames != $newFilenames;
    }

    private function syncTempGroupMembers(TempApplication $application, array $members): void
    {
        $existingMemberIds = [];
        $existingMembers = $application->groupMembers()->get()->keyBy('id_number');

        foreach ($members as $index => $memberData) {
            $existingMember = $existingMembers->get($memberData['id_number']);

            // Handle member photo upload if present
            $memberPhotoData = null;
            if (!empty($memberData['photo_member_id']) && is_array($memberData['photo_member_id']) && isset($memberData['photo_member_id']['data'])) {
                $baseDirectory = 'applications/' . $application->application_id . '/member_photos';

                // Check if member already has a photo and if it's the same
                $shouldCleanup = false;
                if ($existingMember && $existingMember->photo_member_id) {
                    $existingPhoto = $existingMember->photo_member_id;
                    $existingFilename = $existingPhoto['original_filename'] ?? null;
                    $newFilename = $memberData['photo_member_id']['filename'] ?? null;
                    $shouldCleanup = $existingFilename !== $newFilename;
                }

                $memberPhotoData = FileUploadHelper::saveBase64File(
                    $memberData['photo_member_id'],
                    $baseDirectory,
                    'public',
                    $shouldCleanup
                );
            } elseif ($existingMember && $existingMember->photo_member_id) {
                // Keep existing photo if no new one provided
                $memberPhotoData = $existingMember->photo_member_id;
            }

            // Handle disability certificate if present
            $disabilityCertificateData = null;
            if (!empty($memberData['certificate_disability']) && is_array($memberData['certificate_disability']) && isset($memberData['certificate_disability']['data'])) {
                $baseDirectory = 'applications/' . $application->application_id . '/disability_certificates';

                // Check if member already has a certificate and if it's the same
                $shouldCleanup = false;
                if ($existingMember && $existingMember->certificate_of_disability) {
                    $existingCert = $existingMember->certificate_of_disability;
                    $existingFilename = $existingCert['original_filename'] ?? null;
                    $newFilename = $memberData['certificate_disability']['filename'] ?? null;
                    $shouldCleanup = $existingFilename !== $newFilename;
                }

                $disabilityCertificateData = FileUploadHelper::saveBase64File(
                    $memberData['certificate_disability'],
                    $baseDirectory,
                    'public',
                    $shouldCleanup
                );
            } elseif ($existingMember && $existingMember->certificate_of_disability) {
                // Keep existing certificate if no new one provided
                $disabilityCertificateData = $existingMember->certificate_of_disability;
            }

            $member = $application->groupMembers()->updateOrCreate(
                ['id_number' => $memberData['id_number']],
                [
                    'name' => $memberData['name'],
                    'gender' => $memberData['gender'],
                    'phone_number' => $memberData['phone_number'],
                    'position' => $memberData['position'] ?? null,
                    'photo_member_id' => $memberPhotoData,
                    'person_with_disability' => $memberData['person_with_disability'] ?? false,
                    'certificate_of_disability' => $disabilityCertificateData,
                    'pwd_number' => $memberData['pwd_number'] ?? null,
                    'requires_confirmation' => $memberData['requires_confirmation'] ?? false,
                    'confirmation_token' => $memberData['confirmation_token'] ?? null,
                    'confirmation_status' => $memberData['confirmation_status'] ?? 'pending',
                    'confirmation_sent_at' => $memberData['confirmation_sent_at'] ?
                        \Carbon\Carbon::createFromTimestamp($memberData['confirmation_sent_at']) : null,
                    'confirmation_confirmed_at' => $memberData['confirmation_confirmed_at'] ?
                        \Carbon\Carbon::createFromTimestamp($memberData['confirmation_confirmed_at']) : null,
                ]
            );

            $existingMemberIds[] = $member->id;

            // Cleanup orphaned files for removed members
            if ($existingMember) {
                $existingMembers->forget($memberData['id_number']);
            }
        }

        // Remove members that are no longer in the data and cleanup their files
        foreach ($existingMembers as $oldMember) {
            // Delete member's files before deleting the member record
            if ($oldMember->photo_member_id && isset($oldMember->photo_member_id['path'])) {
                Storage::delete($oldMember->photo_member_id['path']);
                Log::info("Deleted orphaned member photo", [
                    'application_id' => $application->application_id,
                    'member_name' => $oldMember->name,
                    'path' => $oldMember->photo_member_id['path']
                ]);
            }

            if ($oldMember->certificate_of_disability && isset($oldMember->certificate_of_disability['path'])) {
                Storage::delete($oldMember->certificate_of_disability['path']);
                Log::info("Deleted orphaned disability certificate", [
                    'application_id' => $application->application_id,
                    'member_name' => $oldMember->name,
                    'path' => $oldMember->certificate_of_disability['path']
                ]);
            }

            $oldMember->delete();
            Log::info("Deleted removed member", [
                'application_id' => $application->application_id,
                'member_name' => $oldMember->name
            ]);
        }
    }

    private function syncTempExpenses(TempApplication $application, array $expenses): void
    {
        $application->expenses()->delete(); // Clear existing expenses

        foreach ($expenses as $expenseData) {
            $application->expenses()->create([
                'name' => $expenseData['name'] ?? 'Custom Expense',
                'amount' => $expenseData['amount'] ?? 0,
            ]);
        }
    }

    /**
     * Download uploaded file
     */
    public function downloadFile($fileId): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            $file = TempFile::findOrFail($fileId);

            if (!Storage::disk('public')->exists($file->path)) {
                abort(404, 'File not found');
            }

            return response()->download(
                public_path('storage/' . $file->path),
                $file->original_name,
                ['Content-Type' => $file->mime_type]
            );

        } catch (\Exception $e) {
            Log::error('❌ Error downloading file', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            abort(404, 'File not found');
        }
    }

    public function getFileUrl($fileId): JsonResponse
    {
        try {
            $file = TempFile::findOrFail($fileId);

            if (!Storage::disk('public')->exists($file->path)) {
                return response()->json([
                    'success' => false,
                    'error' => 'not_found',
                    'message' => 'File not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $file->url,
                    'download_url' => route('api.application.file.download', $fileId)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'File not found',
            ], 404);
        }
    }

    /**
     * Get application files
     */
    public function getApplicationFiles($applicationId): JsonResponse
    {
        try {
            $application = TempApplication::where('application_id', $applicationId)->firstOrFail();
            $files = $application->files()->get();

            return response()->json([
                'success' => true,
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Application not found',
            ], 404);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $application = TempApplication::with(['user', 'groupMembers', 'expenses', 'files'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $application,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Application not found',
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Update endpoint not implemented yet',
        ], 501);
    }

    // /**
    //  * Cleanup orphaned files and directories (call this periodically via scheduler)
    //  */
    // public static function cleanupOrphanedFiles(): void
    // {
    //     $applications = TempApplication::pluck('application_id')->toArray();
    //     $baseDirectory = 'applications/';

    //     // Get all application directories
    //     $directories = Storage::directories($baseDirectory);

    //     foreach ($directories as $directory) {
    //         $applicationId = basename($directory);

    //         // Delete directory if application no longer exists
    //         if (!in_array($applicationId, $applications)) {
    //             FileUploadHelper::deleteDirectory($directory);
    //             Log::info("Cleaned up orphaned application directory", [
    //                 'application_id' => $applicationId,
    //                 'directory' => $directory
    //             ]);
    //         }
    //     }
    // }

    // 4. Add to Laravel Scheduler (app/Console/Kernel.php):
    // protected function schedule(Schedule $schedule)
    // {
    //     // Daily cleanup of orphaned files
    //     $schedule->call(function () {
    //         \App\Http\Helpers\FileUploadHelper::cleanupOrphanedFiles();
    //     })->daily();
    // }
}
