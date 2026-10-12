<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalCorrectionRequest extends Model
{
    public const TYPE_GROUP_LOAN = 'group_loan';

    public const TYPE_ALLOCATION_PROFILE = 'allocation_profile';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const FIELD_LABELS = [
        'disbursement_date' => 'When the cheque / disbursement was issued (date issued)',
        'amount_issued' => 'How much was issued',
        'amount_repaid' => 'Amount repaid',
        'cheque_no' => 'Cheque number',
        'allocation_profile' => 'Constituency allocation profile figures',
    ];

    protected $fillable = [
        'type',
        'constituency_code',
        'group_loan_id',
        'requested_by',
        'fields',
        'message',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class, 'group_loan_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * @return list<string>
     */
    public function fieldLabels(): array
    {
        return collect($this->fields ?? [])
            ->map(fn ($field) => self::FIELD_LABELS[$field] ?? str_replace('_', ' ', (string) $field))
            ->values()
            ->all();
    }
}
