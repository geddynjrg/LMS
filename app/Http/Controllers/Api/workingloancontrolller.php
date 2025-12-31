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
use App\Models\County;
use App\Models\Constituency;
use App\Models\Wards as Ward;
use Illuminate\Support\Facades\DB;


class LoanApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Log::info('📥 Received application data from external system', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_keys' => array_keys($request->all()),
            'data_sample' => json_encode($request->all())
        ]);

        try {
            // Validate the incoming data
            $validator = Validator::make($request->all(), [
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
                'institution_type' => 'nullable|string|max:255',
                'bank_name' => 'required|string|max:255',
                'bank_branch' => 'required|string|max:255',
                'bank_account_number' => 'required|string|max:255',

                // Business information
                'nature_of_business' => 'required|string|max:255',
                'business_type' => 'required|array',
                'years_in_operation' => 'required|integer|min:0',
                'average_income' => 'required|numeric|min:0',
                'expenses' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for application data', [
                    'errors' => $validator->errors()->toArray(),
                    'application_id' => $request->input('application_id')
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'validation_error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }

            $validatedData = $validator->validated();

            DB::transaction(function () use ($validatedData) {
                // Step 1: Find or create temp user
                $user = $this->findOrCreateTempUser($validatedData);

                // Step 2: Map location data to existing database codes
                $locationCodes = $this->mapLocationCodes($validatedData);

                // Step 3: Create or update temporary application
                $application = $this->createOrUpdateTempApplication($validatedData, $user->id, $locationCodes);

                // Step 4: Sync group members
                $this->syncTempGroupMembers($application, $validatedData['group_members']);

                // Step 5: Sync expenses if any
                if (!empty($validatedData['expenses'])) {
                    $this->syncTempExpenses($application, $validatedData['expenses']);
                }

                Log::info('✅ Application stored in temporary tables', [
                    'application_id' => $application->application_id,
                    'temp_application_id' => $application->id,
                    'member_count' => count($validatedData['group_members'])
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Application received and stored successfully',
                'data' => [
                    'application_id' => $validatedData['application_id'],
                    'status' => 'received',
                    'message' => 'Application is being processed'
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error processing application', [
                'application_id' => $request->input('application_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'processing_error',
                'message' => 'Failed to process application: ' . $e->getMessage(),
            ], 500);
        }
    }

    // private function findOrCreateTempUser(array $data): TempUser
    // {
    //     $userIdentifier = [
    //         'identifier_type' => $data['identifier_type'],
    //         'owner_identifier' => $data['owner_identifier']
    //     ];

    //     return TempUser::firstOrCreate($userIdentifier, [
    //         'email' => $data['identifier_type'] === 'EMAIL' ? $data['owner_identifier'] : null,
    //         'phone_number' => $data['identifier_type'] === 'PHONE' ? $data['owner_identifier'] : ($data['phone_number'] ?? null),
    //         'name' => $data['identifier_type'] === 'NAME' ? $data['owner_identifier'] : null,
    //     ]);
    // }
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
        $applicationData = [
            'temp_user_id' => $userId,
            'status' => $data['status'],
            'identifier_type' => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
            'phone_number' => $data['phone_number'] ?? null,

            // Group information
            'group_name' => $data['group_name'],
            'group_registration_number' => $data['group_registration_number'],
            'date_registered' => $data['date_registered'],
            'group_registration_certificate' => $data['group_registration_certificate'] ?? null,
            'list_of_members_certificate' => $data['list_of_members_certificate'] ?? null,
            'group_background' => $data['group_background'] ?? null,
            'key_activities' => $data['key_activities'] ?? null,
            'other_activities' => $data['other_activities'] ?? null,
            'group_credit_history' => $data['group_credit_history'] ?? null,

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

    private function syncTempGroupMembers(TempApplication $application, array $members): void
    {
        $existingMemberIds = [];

        foreach ($members as $memberData) {
            $member = $application->groupMembers()->updateOrCreate(
                ['id_number' => $memberData['id_number']],
                [
                    'name' => $memberData['name'],
                    'gender' => $memberData['gender'],
                    'phone_number' => $memberData['phone_number'],
                    'position' => $memberData['position'] ?? null,
                    'photo_member_id' => $memberData['photo_member_id'] ?? null,
                    'person_with_disability' => $memberData['person_with_disability'] ?? false,
                    'certificate_of_disability' => $memberData['certificate_of_disability'] ?? null,
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
        }

        // Remove members that are no longer in the data
        $application->groupMembers()->whereNotIn('id', $existingMemberIds)->delete();
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

    public function show($id): JsonResponse
    {
        try {
            $application = TempApplication::with(['user', 'groupMembers', 'expenses'])
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
        // Implement update logic similar to store but for specific ID
        return response()->json([
            'success' => false,
            'message' => 'Update endpoint not implemented yet',
        ], 501);
    }
}
