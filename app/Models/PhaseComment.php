<?php
// app/Models/PhaseComment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseComment extends Model
{
    protected $fillable = [
        'loan_phase_id',
        'phase_application_id',
        'user_id',
        'comment',
        'type',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class, 'loan_phase_id');
    }

    public function phaseApplication(): BelongsTo
    {
        return $this->belongsTo(PhaseApplication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
