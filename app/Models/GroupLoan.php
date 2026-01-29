<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use \Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GroupLoan extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('group-loan')
            ->logOnly([
                'loan_phase_id',
                'group_id',
                'application_date',
                'disbursement_date',
                'amount_applied',
                'loan_status',
                'cheque_no',
                'amount_approved',
                'amount_issued',
                'amount_repaid',
                'status',
                'comments',
            ]) // log only these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // skip logs if no changes
    }

    protected $fillable = [
        'loan_phase_id',
        'group_id',
        'level',
        'application_date',
        'disbursement_date',
        'amount_applied',
        'loan_status',
        'cheque_no',
        'amount_approved',
        'amount_issued',
        'amount_repaid',
        'status',
        'comments',
        'is_defaulted',
        'months_defaulted',
        'total_arrears',
        'amount_due',
        'default_status_updated_at',
    ];

    protected $casts = [
        'total_arrears' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];


    public function loanPhase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get all of the comments for the GroupLoan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_loans_id', 'id');
    }

    public function loanReceipts(): HasMany
    {
        return $this->hasMany(LoanReceipts::class, 'group_loans_id', 'id');
    }

    /**
     * Scope: Loans that are still in progress (not fully repaid or cancelled)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Loans that are currently in default (based on computed flag)
     */
    public function scopeDefaulted($query)
    {
        return $query->where('is_defaulted', true);
    }

    /**
     * Scope: All loans that are not yet completed or cancelled
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['active']);
    }

    /**
     * Accessor: Human-readable status including default info
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'Cancelled';
        }

        if ($this->status === 'completed') {
            return 'Completed';
        }

        // Active loan
        return $this->is_defaulted
            ? "Active (Defaulted - {$this->months_defaulted} months)"
            : 'Active';
    }

    /**
     * Accessor: Quick check if loan is problematic
     */
    public function getIsProblematicAttribute(): bool
    {
        return $this->is_defaulted || $this->status === 'cancelled';
    }

}
