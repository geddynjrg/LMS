<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class TempApplication extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'group_id', 'sync_status', 'link_status', 'temp_user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship to get activities for this specific application
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
    protected $fillable = [
        'application_id',
        'external_id',
        'temp_user_id',
        'status',
        'identifier_type',
        'owner_identifier',
        'phone_number',
        'group_name',
        'group_registration_number',
        'date_registered',
        'group_registration_certificate',
        'list_of_members_certificate',
        'group_meeting_minutes',
        'institution_type',
        'group_background',
        'key_activities',
        'other_activities',
        'group_credit_history',
        'county_name',
        'constituency_name',
        'ward_name',
        'meeting_point',
        'nearest_landmark',
        'nature_of_group',
        'loan_type',
        'loan_amount',
        'proposed_repayment_period',
        'bank_name',
        'bank_branch',
        'bank_account_number',
        'nature_of_business',
        'business_type',
        'years_in_operation',
        'average_income',
        'expenses',
        'county_code',
        'constituency_code',
        'ward_id',
        'sync_status',
        'sync_attempts',
        'last_sync_at',
        'sync_error',
        'external_application_id',
        'raw_data',
        'group_id',
        'link_status',
    ];

    protected $casts = [
        'group_registration_certificate' => 'array',
        'list_of_members_certificate' => 'array',
        'group_meeting_minutes' => 'array',
        'group_background' => 'array',
        'key_activities' => 'array',
        'group_credit_history' => 'array',
        'business_type' => 'array',
        'expenses' => 'array',
        'loan_amount' => 'decimal:2',
        'average_income' => 'decimal:2',
        'date_registered' => 'date',
        'last_sync_at' => 'datetime',
        'raw_data' => 'array',
    ];

    // protected $with  = [
    //     'groupMembers'
    // ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(TempUser::class, 'temp_user_id');
    }

    public function groupMembers()
    {
        return $this->hasMany(TempGroupMember::class, 'temp_application_id');
    }

    public function expenses()
    {
        return $this->hasMany(TempExpense::class, 'temp_application_id');
    }

    /**
     * Get the permanent Group record linked to this application.
     */
    public function linkedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function files()
    {
        return $this->hasMany(TempApplicationFile::class, 'temp_application_id');
    }

    public function phaseApplication()
    {
        return $this->hasOne(PhaseApplication::class, 'temp_application_id');
    }

    public function getAllFilesAttribute()
    {
        $files = [];

        // Add group registration certificate
        if ($this->group_registration_certificate && is_array($this->group_registration_certificate)) {
            $files[] = [
                'field_name' => 'Group Registration Certificate',
                'data' => $this->group_registration_certificate,
                'type' => 'group_registration_certificate'
            ];
        }

        // Add list of members certificate
        if ($this->list_of_members_certificate && is_array($this->list_of_members_certificate)) {
            $files[] = [
                'field_name' => 'List of Members Certificate',
                'data' => $this->list_of_members_certificate,
                'type' => 'list_of_members_certificate'
            ];
        }

        // Add group meeting minutes
        if ($this->group_meeting_minutes && is_array($this->group_meeting_minutes)) {
            foreach ($this->group_meeting_minutes as $index => $minute) {
                $files[] = [
                    'field_name' => 'Group Meeting Minutes ' . ($index + 1),
                    'data' => $minute,
                    'type' => 'group_meeting_minutes'
                ];
            }
        }

        // Add group credit history
        if ($this->group_credit_history && is_array($this->group_credit_history)) {
            foreach ($this->group_credit_history as $index => $creditFile) {
                $files[] = [
                    'field_name' => 'Group Credit History ' . ($index + 1),
                    'data' => $creditFile,
                    'type' => 'group_credit_history'
                ];
            }
        }

        return $files;
    }

    // Helper method to get file URL
    public function getFileUrl($fileData)
    {
        // If URL is already in data
        if (isset($fileData['url'])) {
            $url = $fileData['url'];

            // Make sure URL is absolute
            if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
                // If it starts with /storage, prepend base URL
                if (strpos($url, '/storage/') === 0) {
                    $url = url($url);
                } else {
                    // Otherwise, use Storage facade
                    $url = Storage::disk('public')->url($fileData['path']);
                }
            }
            return $url;
        }

        // Generate URL from path
        if (isset($fileData['path'])) {
            return Storage::disk('public')->url($fileData['path']);
        }

        return null;
    }

    // Helper method to get download URL
    public function getFileDownloadUrl($fileData)
    {
        if (isset($fileData['path']) && Storage::exists($fileData['path'])) {
            return route('application.file.download', [
                'applicationId' => $this->application_id,
                'filePath' => base64_encode($fileData['path'])
            ]);
        }
        return null;
    }

    // Check if application has any files
    public function getHasFilesAttribute()
    {
        return !empty($this->group_registration_certificate) ||
               !empty($this->list_of_members_certificate) ||
               !empty($this->group_meeting_minutes) ||
               !empty($this->group_credit_history);
    }

}
