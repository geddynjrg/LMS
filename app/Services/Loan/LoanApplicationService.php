<?php

namespace App\Services\Loan;

use App\Models\TempApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Helpers\FileUploadHelper;
use App\Models\LoanApplication as Application;
use App\Models\GroupMember;
use App\Models\TempUser;
use App\Models\TempFile;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Wards as Ward;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Services\WorkflowNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LoanApplicationService
{
    /**
     * The core logic for processing an application.
     * This can be called from a Controller OR a Job.
     */
    public function process(array $data): TempApplication
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = $this->findOrCreateTempUser($data);
                $locationCodes = $this->mapLocationCodes($data);

                // $attributes = \App\Mappers\LoanApplicationMapper::mapToTable($data, $user->id, $locationCodes);

                $application = $this->createOrUpdateTempApplication($data, $user->id, $locationCodes);


                $this->syncTempGroupMembers($application, $data['group_members'] ?? []);

                if (!empty($data['expenses'])) {
                    $this->syncTempExpenses($application, $data['expenses']);
                }

                // Mark Success
                $application->update([
                    'status' => 'under_constituency_review',
                    'sync_status' => 'completed',
                    'last_sync_at'  => now(),
                ]);

                Log::info('✅ Application stored in temporary tables', [
                    'application_id' => $application->application_id,
                    'temp_application_id' => $application->id,
                ]);

                SyncApplicationStatusToDrupal::dispatch($application->id);

                DB::afterCommit(function () use ($application) {
                    app(WorkflowNotificationService::class)
                        ->notifyConstituencyOfficersNewApplication($application);
                });

                return $application;
            });
        } catch (\Throwable $e) {
            TempApplication::where('application_id', $data['application_id'])->update([
                'sync_status' => 'failed',
                'sync_error' => sprintf(
                    "Error: %s in %s:%d",
                    $e->getMessage(),
                    basename($e->getFile()),
                    $e->getLine()
                ),
                'last_sync_at' => now()
            ]);
            throw $e;
        }
    }

    public function validationRules(): array
    {
        return [
            'application_id' => 'required|string|max:255',
            'identifier_type' => 'required|in:EMAIL,PHONE,NAME',
            'owner_identifier' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:draft,submitted,under_review,approved,rejected,disbursed,closed',

            // Group data
            'group_name' => 'required|string|max:255',
            'group_registration_number' => 'required|string|max:255',
            'date_registered' => 'required|date',
            'group_registration_certificate' => 'nullable|array',
            // 'group_registration_certificate.data' => 'nullable|string|base64|max:10485760', // ~10MB
            // 'group_registration_certificate.filename' => 'nullable|string|max:255',
            'list_of_members_certificate' => 'nullable|array',
            'group_meeting_minutes' => 'nullable|array',
            'group_background' => 'nullable|array',
            'key_activities' => 'nullable|array',
            'other_activities' => 'nullable|string',
            'group_credit_history' => 'nullable|array',


            // Location data
            'county' => 'required|string|max:255',
            'constituency' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'meeting_point' => 'required|string|max:255',
            'nearest_landmark' => 'required|string|max:255',
            'nature_of_group' => 'required|string|max:255',

            // Group members
            'group_members' => 'required|array|min:1',
            'group_members.*.name' => 'required|string|max:255',
            'group_members.*.id_number' => 'required|string|max:50',
            'group_members.*.gender' => 'required|in:male,female,other',
            'group_members.*.phone_number' => 'required|string|max:20',
            'group_members.*.position' => 'nullable|string|max:255',
            'group_members.*.economic_activity' => 'nullable|string|max:255',
            'group_members.*.photo_member_id' => 'nullable|array',
            'group_members.*.person_with_disability' => 'nullable|boolean',
            'group_members.*.certificate_of_disability' => 'nullable|array',
            'group_members.*.pwd_number' => 'nullable|string|max:50',

            // Confirmation fields
            'group_members.*.confirmation_token' => 'nullable|string|max:64',
            'group_members.*.confirmation_status' => 'nullable|in:pending,sent,confirmed,declined,failed',
            'group_members.*.confirmation_sent_at' => 'nullable|integer',
            'group_members.*.confirmation_confirmed_at' => 'nullable|integer',
            'group_members.*.requires_confirmation' => 'nullable|boolean',

            // Loan conditions
            'loan_type' => 'required|string|max:255',
            'loan_amount' => 'required|numeric|min:0',
            'proposed_repayment_period' => 'required|integer|min:1',
            'bank_name' => 'required|string|max:255',
            'institution_type' => 'nullable|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',

            // Business information
            'nature_of_business' => 'required|string|max:255',
            'business_type' => 'required|array',
            'years_in_operation' => 'required|integer|min:0',
            'average_income' => 'required|numeric|min:0',
            'expenses' => 'nullable|array',
        ];
    }

    public function findOrCreateTempUser(array $data)
    {
        $userIdentifier = [
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
        ];

        $existing = TempUser::where($userIdentifier)->first();
        if ($existing) {
            return $existing;
        }

        $externalId = $data['owner_identifier'];
        $byExternal = TempUser::where('external_id', $externalId)->first();
        if ($byExternal) {
            return $byExternal;
        }

        if (!empty($data['phone_number'])) {
            $byPhone = TempUser::where('phone_number', $data['phone_number'])->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        return TempUser::create([
            'external_id' => $externalId,
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
            'email' => $data['identifier_type'] === 'EMAIL' ? $data['owner_identifier'] : null,
            'phone_number' => $data['identifier_type'] === 'PHONE'
                ? $data['owner_identifier']
                : ($data['phone_number'] ?? null),
            'name' => $data['identifier_type'] === 'NAME' ? $data['owner_identifier'] : null,
        ]);
    }

    public function mapLocationCodes(array $data)
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
            'status' => in_array($data['status'] ?? '', ['draft', 'submitted', 'approved', 'rejected', 'disbursed', 'closed'], true)
                ? $data['status']
                : 'submitted',
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

            // Sync Metadata
            'sync_status'   => 'processing',
            'last_sync_at'  => now(),
            'sync_attempts' => DB::raw('sync_attempts + 1'),
            'raw_data'      => $this->compactPayloadForRawDataStorage($data),
        ];

        return TempApplication::updateOrCreate(
            ['application_id' => $data['application_id']],
            $applicationData
        );
    }

    /**
     * Re-save application files from stored raw_data when storage is missing on disk.
     */
    public function repairApplicationFiles(TempApplication $application): array
    {
        $rawData = $application->raw_data;

        if (!is_array($rawData) || empty($rawData['application_id'])) {
            throw new \InvalidArgumentException('Application has no raw_data to repair files from.');
        }

        $filesData = $this->saveApplicationFiles($rawData, $application);

        $application->update([
            'group_registration_certificate' => $filesData['group_registration_certificate'] ?? $application->group_registration_certificate,
            'list_of_members_certificate' => $filesData['list_of_members_certificate'] ?? $application->list_of_members_certificate,
            'group_meeting_minutes' => $filesData['group_meeting_minutes'] ?? $application->group_meeting_minutes,
            'group_credit_history' => $filesData['group_credit_history'] ?? $application->group_credit_history,
        ]);

        if (!empty($rawData['group_members'])) {
            $this->syncTempGroupMembers($application, $rawData['group_members']);
        }

        return $filesData;
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
                    'local',
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
                'local',
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
                'local',
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
                    'local',
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
                    'local',
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
                    'confirmation_sent_at' => $this->parseMemberTimestamp($memberData, 'confirmation_sent_at'),
                    'confirmation_confirmed_at' => $this->parseMemberTimestamp($memberData, 'confirmation_confirmed_at'),
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
            try {
                // Delete member's files before deleting the member record
                if ($oldMember->photo_member_id && isset($oldMember->photo_member_id['path'])) {
                    Storage::delete($oldMember->photo_member_id['path']);
                    Log::info("Deleted orphaned member photo", [
                        'application_id' => $application->application_id,
                        'member_name' => $oldMember->name,
                        'path' => $oldMember->photo_member_id['path']
                    ]);
                }
            } catch (Throwable $e) {
                Log::warning("Failed to delete orphaned member photo", ['path' => $oldMember->photo_member_id['path'], 'error' => $e->getMessage()]);
            }

            try {
                if ($oldMember->certificate_of_disability && isset($oldMember->certificate_of_disability['path'])) {
                    Storage::delete($oldMember->certificate_of_disability['path']);
                    Log::info("Deleted orphaned disability certificate", [
                        'application_id' => $application->application_id,
                        'member_name' => $oldMember->name,
                        'path' => $oldMember->certificate_of_disability['path']
                    ]);
                }
            } catch (Throwable $e) {
                Log::warning("Failed to delete orphaned member cert of disability", ['path' => $oldMember->photo_member_id['path'], 'error' => $e->getMessage()]);
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
     * Optional member confirmation timestamps from the loans portal may be omitted.
     */
    private function parseMemberTimestamp(array $memberData, string $key): ?Carbon
    {
        if (!array_key_exists($key, $memberData) || $memberData[$key] === null || $memberData[$key] === '') {
            return null;
        }

        $value = $memberData[$key];

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            Log::warning('Invalid member confirmation timestamp', [
                'field' => $key,
                'value' => $value,
            ]);

            return null;
        }
    }

    public function notifyDrupal(TempApplication $application)
    {
        $drupalStatus = $this->mapToDrupalStatus($application->status);

        $response = Http::withHeaders([
            'X-API-Key' => config('services.drupal.api_key'),
            'Content-Type' => 'application/json',
        ])->patch(config('services.drupal.url') . "/api/v1/lms/application/{$application->application_id}", [
            'status' => $drupalStatus,
            'rejection_reason' => $application->rejection_reason ?? null,
            'rejection_message' => $application->rejection_message ?? null,
        ]);

        if ($response->successful()) {
            $application->update([
                'drupal_sync_status' => 'synced',
                'drupal_synced_at' => now(),
                'drupal_sync_error' => null,
            ]);
            return true;
        }

        $application->update([
            'drupal_sync_status' => 'failed',
            'drupal_sync_error' => $response->body(),
        ]);

        return false;
    }

    /**
     * Store a lightweight copy of the portal payload in raw_data (no base64 blobs).
     */
    private function compactPayloadForRawDataStorage(array $data): array
    {
        $compact = $data;

        foreach ([
            'group_registration_certificate',
            'list_of_members_certificate',
        ] as $fileKey) {
            if (!empty($compact[$fileKey]) && is_array($compact[$fileKey])) {
                $compact[$fileKey] = $this->compactFileReference($compact[$fileKey]);
            }
        }

        foreach (['group_meeting_minutes', 'group_credit_history'] as $fileKey) {
            if (!empty($compact[$fileKey]) && is_array($compact[$fileKey])) {
                $compact[$fileKey] = array_map(
                    fn ($file) => is_array($file) ? $this->compactFileReference($file) : $file,
                    $compact[$fileKey],
                );
            }
        }

        if (!empty($compact['group_members']) && is_array($compact['group_members'])) {
            $compact['group_members'] = array_map(function (array $member) {
                foreach (['photo_member_id', 'certificate_of_disability', 'certificate_disability'] as $fileKey) {
                    if (!empty($member[$fileKey]) && is_array($member[$fileKey])) {
                        $member[$fileKey] = $this->compactFileReference($member[$fileKey]);
                    }
                }

                return $member;
            }, $compact['group_members']);
        }

        return $compact;
    }

    private function compactFileReference(array $file): array
    {
        unset($file['data']);

        return $file;
    }
}
