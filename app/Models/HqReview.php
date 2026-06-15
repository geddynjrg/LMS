<?php
// app/Models/HqReview.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HqReview extends Model
{
    protected $fillable = [
        'loan_phase_id',
        'hq_officer_id',
        'decision',
        'comments',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class, 'loan_phase_id');
    }

    public function hqOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hq_officer_id');
    }

    public function isApproved(): bool
    {
        return $this->decision === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->decision === 'rejected';
    }
}
