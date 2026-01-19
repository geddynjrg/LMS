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
        'training_date',
        'training_venue',
        'constituency_officer_id',
        'hq_officer_id',
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
        'hq_reviewed_at' => 'datetime',
        'total_amount_applied' => 'decimal:2',
        'total_amount_approved' => 'decimal:2',
        'is_locked' => 'boolean',
        'training_date' => 'date',
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
        // 1. Permanent Lock: Once disbursed, it's a historical record.
        if ($this->status === 'disbursed' || $this->is_locked) {
            return false;
        }

        // 2. HQ Logic
        if ($user->hasRole('hq_officer')) {
            // HQ can only edit/review if the constituency has officially handed it over
            return $this->status === 'submitted_to_hq';
        }

        // 3. Constituency Officer Logic
        if ($user->hasRole('constituency_officer')) {
            // Officer has control during Draft/Review
            // AND after HQ gives the green light (Approved) for training/cheques
            return in_array($this->status, ['draft', 'in_review', 'approved', 'rejected']);
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
        return $this->attributes['has_minutes']
            ?? $this->phaseApplicationDocuments()->where('document_type', 'minutes')->exists();
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

