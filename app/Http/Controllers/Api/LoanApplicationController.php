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
            'payload_size_bytes' => strlen($request->getContent()),
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
}
