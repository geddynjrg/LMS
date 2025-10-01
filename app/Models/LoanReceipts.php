<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LoanReceipts extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('receipt')
            ->logOnly(['receipt_number', 'transaction_date', 'amount', 'payment', 'reference_number']) // log only these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // skip logs if no changes
    }

    protected $fillable = [
        'group_loans_id',
        'receipt_number',
        'transaction_date',
        'amount',
        'payment',
        'reference_number',
    ];

    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class, 'group_loans_id', 'id');
    }
}
