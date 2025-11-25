<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\LoanApplication as Application;
use App\Models\GroupMember;

class LoanApplicationController extends Controller
{
    /**
     * Receive application data from external systems (Drupal/Next.js)
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('📥 Received application data from external system', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_keys' => array_keys($request->all())
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Store endpoint not implemented yet',
        ], 501);

        // try {
        //     // Step 1: Log the raw incoming data for debugging
        //     Log::debug('Raw incoming application data:', $request->all());

        //     // Step 2: Validate the incoming data
        //     $validator = Validator::make($request->all(), [
        //         'application_id' => 'required|string|max:255',
        //         'identifier_type' => 'required|in:EMAIL,PHONE,NAME',
        //         'owner_identifier' => 'required|string|max:255',
        //         'phone_number' => 'nullable|string|max:20',
        //         'status' => 'required|in:draft,submitted,under_review,approved,rejected,disbursed,closed',

        //         // Group data validation
        //         'group_name' => 'required|string|max:255',
        //         'group_registration_number' => 'required|string|max:255',
        //         'date_registered' => 'required|date',

        //         // Group members validation
        //         'group_members' => 'required|array|min:1',
        //         'group_members.*.name' => 'required|string|max:255',
        //         'group_members.*.id_number' => 'required|string|max:50',
        //         'group_members.*.gender' => 'required|in:male,female,other',
        //         'group_members.*.phone_number' => 'required|string|max:20',
        //         'group_members.*.position' => 'nullable|string|max:255',

        //         // Loan conditions validation
        //         'loan_amount' => 'required|numeric|min:0',
        //         'proposed_repayment_period' => 'nullable|integer|min:1',

        //         // Confirmation data (if present)
        //         'group_members.*.confirmation_token' => 'nullable|string|max:255',
        //         'group_members.*.confirmation_status' => 'nullable|in:pending,sent,confirmed,declined,failed',
        //         'group_members.*.confirmation_sent_at' => 'nullable|integer',
        //         'group_members.*.confirmation_confirmed_at' => 'nullable|integer',
        //         'group_members.*.requires_confirmation' => 'nullable|boolean',
        //     ]);

        //     if ($validator->fails()) {
        //         Log::warning('Validation failed for application data', [
        //             'errors' => $validator->errors()->toArray(),
        //             'application_id' => $request->input('application_id')
        //         ]);

        //         return response()->json([
        //             'success' => false,
        //             'error' => 'validation_error',
        //             'message' => 'Validation failed',
        //             'errors' => $validator->errors()->toArray(),
        //         ], 422);
        //     }

        //     $validatedData = $validator->validated();

        //     Log::info('✅ Validation passed for application', [
        //         'application_id' => $validatedData['application_id'],
        //         'group_name' => $validatedData['group_name'],
        //         'member_count' => count($validatedData['group_members'] ?? [])
        //     ]);

        //     // Step 3: Check if application already exists
        //     $existingApplication = Application::where('application_id', $validatedData['application_id'])->first();

        //     if ($existingApplication) {
        //         Log::info('🔄 Updating existing application', [
        //             'application_id' => $validatedData['application_id'],
        //             'existing_id' => $existingApplication->id
        //         ]);

        //         // Update existing application
        //         $this->updateApplication($existingApplication, $validatedData);
        //         $message = 'Application updated successfully';
        //     } else {
        //         Log::info('🆕 Creating new application', [
        //             'application_id' => $validatedData['application_id']
        //         ]);

        //         // Create new application
        //         $existingApplication = $this->createApplication($validatedData);
        //         $message = 'Application created successfully';
        //     }

        //     // Step 4: Trigger loan processing workflow
        //     $this->processLoanApplication($existingApplication);

        //     // Step 5: Return success response
        //     return response()->json([
        //         'success' => true,
        //         'message' => $message,
        //         'data' => [
        //             'id' => $existingApplication->id,
        //             'lms_application_id' => $existingApplication->id,
        //             'application_id' => $existingApplication->application_id,
        //             'status' => $existingApplication->status,
        //             'group_name' => $existingApplication->group_name,
        //             'created_at' => $existingApplication->created_at->toISOString(),
        //             'updated_at' => $existingApplication->updated_at->toISOString(),
        //         ],
        //     ], 201);

        // } catch (\Exception $e) {
        //     Log::error('❌ Error processing application', [
        //         'application_id' => $request->input('application_id'),
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);

        //     return response()->json([
        //         'success' => false,
        //         'error' => 'processing_error',
        //         'message' => 'Failed to process application: ' . $e->getMessage(),
        //     ], 500);
        // }
    }

    /**
     * Create a new application
     */
    private function createApplication(array $data): Application
    {
        Log::info('Creating new application in LMS', [
            'application_id' => $data['application_id'],
            'group_name' => $data['group_name']
        ]);

        // Create the main application
        $application = Application::create([
            'application_id' => $data['application_id'],
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
            'phone_number' => $data['phone_number'] ?? null,
            'status' => $data['status'],
            'group_name' => $data['group_name'],
            'group_registration_number' => $data['group_registration_number'],
            'date_registered' => $data['date_registered'],
            'loan_amount' => $data['loan_amount'] ?? 0,
            'proposed_repayment_period' => $data['proposed_repayment_period'] ?? null,
            'raw_data' => json_encode($data), // Store full payload for reference
        ]);

        // Create group members
        if (!empty($data['group_members'])) {
            $this->createGroupMembers($application, $data['group_members']);
        }

        Log::info('✅ Application created successfully', [
            'application_id' => $application->id,
            'lms_id' => $application->id
        ]);

        return $application;
    }

    /**
     * Update existing application
     */
    private function updateApplication(Application $application, array $data): Application
    {
        Log::info('Updating existing application', [
            'application_id' => $application->application_id,
            'lms_id' => $application->id
        ]);

        // Update application fields
        $application->update([
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
            'phone_number' => $data['phone_number'] ?? null,
            'status' => $data['status'],
            'group_name' => $data['group_name'],
            'group_registration_number' => $data['group_registration_number'],
            'date_registered' => $data['date_registered'],
            'loan_amount' => $data['loan_amount'] ?? 0,
            'proposed_repayment_period' => $data['proposed_repayment_period'] ?? null,
            'raw_data' => json_encode($data), // Update with latest data
        ]);

        // Update or create group members
        if (!empty($data['group_members'])) {
            $this->syncGroupMembers($application, $data['group_members']);
        }

        return $application;
    }

    /**
     * Create group members for application
     */
    private function createGroupMembers(Application $application, array $members): void
    {
        // foreach ($members as $memberData) {
        //     $application->groupMembers()->create([
        //         'name' => $memberData['name'],
        //         'id_number' => $memberData['id_number'],
        //         'gender' => $memberData['gender'],
        //         'phone_number' => $memberData['phone_number'],
        //         'position' => $memberData['position'] ?? null,
        //         'confirmation_token' => $memberData['confirmation_token'] ?? null,
        //         'confirmation_status' => $memberData['confirmation_status'] ?? 'pending',
        //         'confirmation_sent_at' => $memberData['confirmation_sent_at'] ?? null,
        //         'confirmation_confirmed_at' => $memberData['confirmation_confirmed_at'] ?? null,
        //         'requires_confirmation' => $memberData['requires_confirmation'] ?? false,
        //     ]);
        // }

        Log::info('Created group members', [
            'application_id' => $application->id,
            'member_count' => count($members)
        ]);
    }

    /**
     * Sync group members (update existing, create new, remove missing)
     */
    private function syncGroupMembers(Application $application, array $members): void
    {
        $existingMemberIds = [];

        // foreach ($members as $memberData) {
        //     $member = $application->groupMembers()->updateOrCreate(
        //         ['id_number' => $memberData['id_number']],
        //         [
        //             'name' => $memberData['name'],
        //             'gender' => $memberData['gender'],
        //             'phone_number' => $memberData['phone_number'],
        //             'position' => $memberData['position'] ?? null,
        //             'confirmation_token' => $memberData['confirmation_token'] ?? null,
        //             'confirmation_status' => $memberData['confirmation_status'] ?? 'pending',
        //             'confirmation_sent_at' => $memberData['confirmation_sent_at'] ?? null,
        //             'confirmation_confirmed_at' => $memberData['confirmation_confirmed_at'] ?? null,
        //             'requires_confirmation' => $memberData['requires_confirmation'] ?? false,
        //         ]
        //     );

        //     $existingMemberIds[] = $member->id;
        // }

        // // Remove members that are no longer in the data
        // $application->groupMembers()->whereNotIn('id', $existingMemberIds)->delete();

        Log::info('Synced group members', [
            'application_id' => $application->id,
            'member_count' => count($members),
            'kept_members' => count($existingMemberIds)
        ]);
    }

    /**
     * Process loan application workflow
     */
    private function processLoanApplication(Application $application): void
    {
        Log::info('🚀 Starting loan processing workflow', [
            'application_id' => $application->id,
            'loan_amount' => $application->loan_amount
        ]);

        // TODO: Implement your loan processing logic
        // - Credit checks
        // - Risk assessment
        // - Approval workflows
        // - Document generation

        // Example: Trigger background job
        // \App\Jobs\ProcessLoanApplication::dispatch($application);

        Log::info('✅ Loan processing workflow initiated', [
            'application_id' => $application->id
        ]);
    }

    /**
     * Get application details
     */
    public function show($id): JsonResponse
    {
        try {
            $application = Application::with('groupMembers')->findOrFail($id);

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

    /**
     * Update application
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Similar to store but for updates
        // Implement as needed
        return response()->json([
            'success' => false,
            'message' => 'Update endpoint not implemented yet',
        ], 501);
    }
}
