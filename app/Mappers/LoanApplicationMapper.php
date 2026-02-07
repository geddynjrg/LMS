<?php

namespace App\Mappers;

use Illuminate\Support\Facades\DB;

class LoanApplicationMapper
{
    public static function mapToTable(array $data, int $userId, array $locationCodes): array
    {
        return [
            // Identifiers
            'temp_user_id'     => $userId,
            'application_id'   => $data['application_id'],
            'status'           => $data['status'] ?? 'submitted',
            'identifier_type'  => $data['identifier_type'],
            'owner_identifier' => $data['owner_identifier'],
            'phone_number'     => $data['phone_number'] ?? null,

            // Group Info
            'group_name'                => $data['group_name'],
            'group_registration_number' => $data['group_registration_number'],
            'date_registered'           => $data['date_registered'],
            'nature_of_group'           => $data['nature_of_group'],
            'group_background'          => $data['group_background'] ?? null,
            'key_activities'            => $data['key_activities'] ?? null,
            'other_activities'          => $data['other_activities'] ?? null,

            // Location
            'county_name'       => $data['county'],
            'constituency_name' => $data['constituency'],
            'ward_name'         => $data['ward'],
            'county_code'       => $locationCodes['county_code'],
            'constituency_code' => $locationCodes['constituency_code'],
            'ward_id'           => $locationCodes['ward_id'],
            'meeting_point'     => $data['meeting_point'],
            'nearest_landmark'  => $data['nearest_landmark'],

            // Financials
            'loan_type'                 => $data['loan_type'],
            'loan_amount'               => $data['loan_amount'],
            'proposed_repayment_period' => $data['proposed_repayment_period'],
            'bank_name'                 => $data['bank_name'],
            'bank_branch'               => $data['bank_branch'],
            'bank_account_number'       => $data['bank_account_number'],
            'institution_type'          => $data['institution_type'] ?? null,

            // Business
            'nature_of_business' => $data['nature_of_business'],
            'business_type'      => $data['business_type'], 
            'years_in_operation' => $data['years_in_operation'],
            'average_income'     => $data['average_income'],
            'expenses'           => $data['expenses'] ?? null,

            // Sync Metadata
            'sync_status'   => 'processing',
            'last_sync_at'  => now(),
            'sync_attempts' => DB::raw('sync_attempts + 1'),
            'raw_data'      => $data, 
        ];
    }
}