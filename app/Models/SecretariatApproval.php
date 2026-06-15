<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretariatApproval extends Model
{
    protected $fillable = [
        'loan_phase_id',
        'approval_type',
        'approved_by',
        'signature_path',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public const TYPE_POST_HQ_REVIEW = 'post_hq_review';
    public const TYPE_FUND_TRANSFER = 'fund_transfer';
    public const TYPE_TRAINING_AUTHORIZATION = 'training_authorization';
    public const TYPE_FINAL_DISBURSEMENT = 'final_disbursement';

    public static function trainingGateTypes(): array
    {
        return [
            self::TYPE_POST_HQ_REVIEW,
            self::TYPE_FUND_TRANSFER,
            self::TYPE_TRAINING_AUTHORIZATION,
        ];
    }

    public static function defaultApprovalLabels(): array
    {
        return [
            self::TYPE_POST_HQ_REVIEW => 'Confirm HQ officer approval',
            self::TYPE_FUND_TRANSFER => 'Letter of authority to disburse funds',
            self::TYPE_TRAINING_AUTHORIZATION => 'Authorize training at constituency',
            self::TYPE_FINAL_DISBURSEMENT => 'Authorize final disbursement',
        ];
    }

    public function loanPhase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
