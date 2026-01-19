<?php
// app/Models/PhaseApplication.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PhaseApplication extends Model
{
    use LogsActivity;
    // LoanPhase loan_phase_id
    protected $fillable = [
        'loan_phase_id',
        'temp_application_id',
        'group_id',
        'group_code',
        'gfs_code',
        'amount_applied',
        'amount_approved',
        'amount_issued',
        'status',
        'cheque_number',
        'disbusement_date',
        'comments',
        'rejection_reason',
        'is_priority',
        'recommendation',
        'user_id',
        'level',
        'action',
        'loan_level',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'amount_issued' => 'decimal:2',
        'is_priority' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('phase-application')
            ->logOnly(['loan_phase_id', 'temp_application_id', 'amount_applied', 'status', 'amount_issued', 'amount_approved', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function reviewPhase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class, 'loan_phase_id');
    }

    public function tempApplication(): BelongsTo
    {
        return $this->belongsTo(TempApplication::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PhaseApplicationDocument::class);
    }

    /**
     * Get the permanent Group record linked to this application.
     */
    public function linkedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PhaseComment::class);
    }

    // Helper Methods
    public function getCreditHistoryFilesAttribute()
    {
        // Get original credit history files from temp application
        $originalFiles = $this->tempApplication->group_credit_history ?? [];

        // Get committee-added credit history files
        $committeeFiles = $this->documents()
            ->where('document_type', 'like', '%credit%')
            ->get()
            ->map(function ($doc) {
                return [
                    'original_filename' => $doc->original_filename,
                    'filename' => $doc->filename,
                    'path' => $doc->path,
                    'url' => $doc->url,
                    'mime_type' => $doc->mime_type,
                    'size' => $doc->size,
                    'description' => $doc->description,
                    'source' => 'committee_added',
                    'uploaded_at' => $doc->created_at,
                ];
            })->toArray();

        return array_merge($originalFiles, $committeeFiles);
    }

    public function getAllDocumentsAttribute(): array
    {
        $allDocs = [];

        // Add application original documents
        $originalDocs = [
            'group_registration_certificate' => $this->tempApplication->group_registration_certificate,
            'list_of_members_certificate' => $this->tempApplication->list_of_members_certificate,
            'group_meeting_minutes' => $this->tempApplication->group_meeting_minutes,
            'group_credit_history' => $this->tempApplication->group_credit_history,
        ];

        foreach ($originalDocs as $type => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $allDocs[] = [
                        'type' => $type,
                        'source' => 'original_application',
                        ...$file
                    ];
                }
            }
        }

        // Add committee-added documents
        $committeeDocs = $this->documents->map(function ($doc) {
            return [
                'type' => $doc->document_type,
                'source' => 'committee_added',
                'original_filename' => $doc->original_filename,
                'filename' => $doc->filename,
                'path' => $doc->path,
                'url' => $doc->url,
                'mime_type' => $doc->mime_type,
                'size' => $doc->size,
                'description' => $doc->description,
                'uploaded_at' => $doc->created_at,
            ];
        })->toArray();

        return array_merge($allDocs, $committeeDocs);
    }

    // Status helpers
    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'adjusted']);
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getStatusBadgeClass(): string
    {
        $classes = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'adjusted' => 'info',
            'deferred' => 'secondary',
        ];

        return $classes[$this->status] ?? 'secondary';
    }
}
