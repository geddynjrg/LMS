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

}
