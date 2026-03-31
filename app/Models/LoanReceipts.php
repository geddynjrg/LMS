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
            ->useLogName('receipt_audit')
            ->logOnly([
                'receipt_number',
                'transaction_date',
                'amount',
                'payment',
                'reference_number',
                'status' // Crucial to log when a receipt is voided
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Receipt has been {$eventName}");
    }

    protected $fillable = [
        'group_loans_id',
        'receipt_number',
        'transaction_date',
        'amount',
        'payment',
        'reference_number',
        'status',
        'void_reason',
        'voided_by'
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class, 'group_loans_id', 'id');
    }
}
