<?php

namespace App\Models;

use App\Support\LmsGracePeriod;
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
        'accountant_review_status',
        'accountant_notes',
        'accountant_reviewed_at',
        'accountant_reviewed_by',
        'hq_accountant_resubmit_notes',
        'hq_accountant_resubmitted_at',
        'hq_accountant_resubmitted_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'submitted_to_hq_at' => 'datetime',
        'hq_approved_at' => 'datetime',
        'hq_reviewed_at' => 'datetime',
        'accountant_reviewed_at' => 'datetime',
        'hq_accountant_resubmitted_at' => 'datetime',
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

    public function canBeViewedByHq($user): bool
    {
        return \App\Support\HqAccess::phaseCanBeViewedBy($user, $this);
    }

    public function canBeEditedBy($user)
    {
        if ($this->is_locked) {
            return false;
        }

        if ($this->status === 'disbursed' && !$this->hasPendingTrainingOrDisbursement()) {
            return false;
        }

        if ($user->canAccessHq() && $user->can('approve application')) {
            return \App\Support\HqAccess::phaseCanBeEditedBy($user, $this);
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
     * Phases created via historical data upload (no application workflow).
     */
    public function isHistoricalUpload(): bool
    {
        if ($this->phaseApplications()->exists()) {
            return false;
        }

        if ($this->grouploans()->exists()) {
            return true;
        }

        return $this->status === 'disbursed'
            && $this->submitted_to_hq_at === null
            && $this->review_date === null;
    }

    /**
     * Whether constituency staff may edit phase metadata (name, dates, amounts).
     */
    public function canEditDetailsBy($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (!$user->hasAnyRole(['constituency_officer', 'intern'])) {
            return false;
        }

        if (!$this->userCanManageConstituencyPhases($user)) {
            return false;
        }

        if (LmsGracePeriod::isActive()) {
            return true;
        }

        if ($this->is_locked) {
            return false;
        }

        if ($this->isHistoricalUpload()) {
            return true;
        }

        if ($this->status === 'draft') {
            return true;
        }

        return $this->canEditCompletedDetails();
    }

    /**
     * Whether constituency staff may delete this phase.
     * Deletion is blocked while applications are still linked to the phase.
     */
    public function canDeleteBy($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            if (LmsGracePeriod::isActive()) {
                return true;
            }

            return !$this->is_locked;
        }

        if (!$user->hasAnyRole(['constituency_officer', 'intern'])) {
            return false;
        }

        if (!$this->userCanManageConstituencyPhases($user)) {
            return false;
        }

        if (LmsGracePeriod::isActive()) {
            return true;
        }

        if ($this->is_locked) {
            return false;
        }

        return !$this->phaseApplications()->exists();
    }

    /**
     * Closed workflow phases where only metadata corrections are allowed.
     */
    public function canEditCompletedDetails(): bool
    {
        if ($this->isHistoricalUpload()) {
            return true;
        }

        if (!in_array($this->status, ['disbursed', 'rejected'], true)) {
            return false;
        }

        if ($this->hasPendingTrainingOrDisbursement()) {
            return false;
        }

        return $this->phaseApplications()->exists();
    }

    protected function userCanManageConstituencyPhases($user): bool
    {
        if (!$user->can('submit application') && !$user->can('create new phase')) {
            return false;
        }

        return $user->constituencies
            ->contains('constituency_code', $this->constituency_code);
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

    public function accountantReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_reviewed_by');
    }

    public function hqAccountantResubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hq_accountant_resubmitted_by');
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

    public function getHasBankReconciliationAttribute(): bool
    {
        if (array_key_exists('has_bank_reconciliation', $this->attributes)) {
            return (bool) $this->attributes['has_bank_reconciliation'];
        }

        return app(\App\Services\Loan\BankReconciliationService::class)->hasAllRequiredReconciliations($this);
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
        \App\Support\LoanPhaseFinancialTotals::refresh($this);
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
            && $this->has_minutes
            && $this->has_bank_reconciliation
            && ($this->has_request_for_transfer_letter ?? false);
    }

}

