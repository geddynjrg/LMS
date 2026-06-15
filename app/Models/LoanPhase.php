<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LoanPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'constituency_code',
        'phase',
        'slug',
        'phase_ref',
        'amount_disbursed',
        'amount_repaid',
        'review_date',
        'review_venue',
        'status',
        'training_start_date',
        'training_end_date',
        'training_venue',
        'training_notes',
        'constituency_officer_id',
        'hq_officer_id',
        'assigned_hq_officer_id',
        'hq_remarks',
        'hq_approved_at',
        'disbursed_at',
        'committee_remarks',
        'total_amount_applied',
        'total_amount_approved',
        'submitted_to_hq_at',
        'hq_reviewed_at',
    ];

    protected $casts = [
        'review_date' => 'date',
        'submitted_to_hq_at' => 'datetime',
        'hq_approved_at' => 'datetime',
        'hq_reviewed_at' => 'datetime',
        'total_amount_applied' => 'decimal:2',
        'total_amount_approved' => 'decimal:2',
        'is_locked' => 'boolean',
        'training_start_date' => 'date',
        'training_end_date'   => 'date',
        'disbursed_at' => 'datetime',
        'locked_at' => 'datetime',
    ];



    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($phase) {
            $phase->slug = Str::slug($phase->phase) . '-' . $phase->constituency_code . '-' . Str::random(6);

            if (!$phase->phase_ref) {
                $year = date('Y');

                $count = self::where('constituency_code', $phase->constituency_code)
                            ->whereYear('created_at', $year)
                            ->count() + 1;

                $phase->phase_ref = "PHS/{$phase->constituency_code}/{$year}/" . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function canBeEditedBy($user)
    {
        if ($this->is_locked) {
            return false;
        }

        if ($this->status === 'disbursed' && !$this->hasPendingTrainingOrDisbursement()) {
            return false;
        }

        if ($user->hasRole('hq_officer') && $user->can('approve application')) {
            return $this->status === 'submitted_to_hq';
        }

        if ($user->hasRole('constituency_officer') && $user->can('submit application')) {
            if (in_array($this->status, ['draft', 'in_review', 'approved', 'in_training'], true)) {
                return true;
            }

            return $this->status === 'disbursed' && $this->hasPendingTrainingOrDisbursement();
        }

        return false;
    }

    /**
     * Groups still awaiting training completion or disbursement recording.
     */
    public function hasPendingTrainingOrDisbursement(): bool
    {
        return $this->phaseApplications()
            ->where(function ($query) {
                $query->where(function ($pendingTraining) {
                    $pendingTraining->where('status', 'in_training')
                        ->where('is_trained', false);
                })->orWhereIn('status', ['pending_disbursement']);
            })
            ->exists();
    }

    public function canFinalizeDisbursement(): bool
    {
        if ($this->hasPendingTrainingOrDisbursement()) {
            return false;
        }

        return $this->phaseApplications()
            ->whereNotIn('status', ['disbursed', 'rejected', 'disbursement_failed'])
            ->doesntExist();
    }

    /**
     * Constituency officers may record per-group disbursement outcomes after training.
     */
    public function canManageDisbursementBy($user): bool
    {
        if ($this->is_locked) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->hasRole('constituency_officer') && $user->can('submit application')) {
            return in_array($this->status, ['in_training', 'approved', 'disbursed'], true);
        }

        return false;
    }

    public function phaseLocker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }

    public function grouploans(): HasMany
    {
        return $this->hasMany(GroupLoan::class);
    }

    //efficient loading
    public function scopeWithGroupLoansAndGroups($query)
    {
        return $query->with(['grouploans' => function ($query) {
            $query->with('group')->orderBy('level');
        }]);
    }

    public function constituencyOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'constituency_officer_id');
    }

    public function hqOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hq_officer_id');
    }

    public function assignedHqOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_hq_officer_id');
    }

    public function secretariatApprovals(): HasMany
    {
        return $this->hasMany(SecretariatApproval::class);
    }

    public function phaseApplications(): HasMany
    {
        return $this->hasMany(PhaseApplication::class);
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function phaseApplicationDocuments(): HasMany
    {
        return $this->hasMany(PhaseApplicationDocument::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PhaseComment::class);
    }

    public function hqReview(): HasOne
    {
        return $this->hasOne(HqReview::class);
    }

    // Helper Methods
    public function getDocumentsByType($type)
    {
        return $this->phaseApplicationDocuments()->where('document_type', $type)->get();
    }

    public function getHasMinutesAttribute(): bool
    {
        if (array_key_exists('has_minutes', $this->attributes)) {
            return (bool) $this->attributes['has_minutes'];
        }

        return $this->phaseApplicationDocuments()
            ->where('document_type', 'minutes')
            ->exists();
    }

    public function getHasAttendanceListAttribute(): bool
    {
        if (array_key_exists('has_attendance_list', $this->attributes)) {
            return (bool) $this->attributes['has_attendance_list'];
        }

        return $this->phaseApplicationDocuments()
            ->where('document_type', 'attendance_list')
            ->exists();
    }

    public function getSupportingDocumentsAttribute()
    {
        return $this->getDocumentsByType('supporting_docs');
    }

    public function getCreditReportsAttribute()
    {
        return $this->getDocumentsByType('credit_reports');
    }

    // Stats calculation
    public function calculateStats(): void
    {
        $stats = $this->phaseApplications()
            ->selectRaw('
                COUNT(*) as total_applications,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = "adjusted" THEN 1 ELSE 0 END) as adjusted,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(amount_applied) as total_amount_applied,
                SUM(amount_approved) as total_amount_approved
            ')
            ->first();

        $this->update([
            'total_amount_applied' => $stats->total_amount_applied ?? 0,
            'total_amount_approved' => $stats->total_amount_approved ?? 0,
        ]);
    }

    public function getStatsAttribute(): array
    {
        return [
            'total_applications' => $this->phaseApplications()->count(),
            'approved' => $this->phaseApplications()->where('status', 'approved')->count(),
            'rejected' => $this->phaseApplications()->where('status', 'rejected')->count(),
            'adjusted' => $this->phaseApplications()->where('status', 'adjusted')->count(),
            'pending' => $this->phaseApplications()->where('status', 'pending')->count(),
            'total_amount_applied' => $this->total_amount_applied,
            'total_amount_approved' => $this->total_amount_approved,
            'committee_members' => $this->committeeMembers()->count(),
            'documents_count' => $this->phaseApplicationDocuments()->count(),
        ];
    }

    // Status helpers
    public function canAddApplications(): bool
    {
        return in_array($this->status, ['draft', 'in_review']);
    }

    public function canSubmitToHq(): bool
    {
        return $this->status === 'draft'
            && $this->getMinutesAttribute()->isNotEmpty();
    }

}

