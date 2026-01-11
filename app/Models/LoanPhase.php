<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class LoanPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'constituency_code',
        'phase',
        'amount_disbursed',
        'amount_repaid',
        'phase_number',
        'phase_name',
        'review_date',
        'review_venue',
        'status',
        'constituency_officer_id',
        'hq_officer_id',
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
    ];


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

    public function phaseDocuments(): HasMany
    {
        return $this->hasMany(PhaseDocument::class);
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
        return $this->phaseDocuments()->where('document_type', $type)->get();
    }

    public function getMinutesAttribute()
    {
        return $this->getDocumentsByType('committee_minutes');
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
            'documents_count' => $this->phaseDocuments()->count(),
        ];
    }

    // Status helpers
    public function canAddApplications(): bool
    {
        return in_array($this->status, ['draft', 'in_review']);
    }

    public function canSubmitToHq(): bool
    {
        return $this->status === 'approved_by_committee'
            && $this->getMinutesAttribute()->isNotEmpty();
    }

}

