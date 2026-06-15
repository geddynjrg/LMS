<?php
// app/Models/CommitteeMember.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    protected $fillable = [
        'loan_phase_id',
        'user_id',
        'role',
        'attended',
        'signature_path',
        'signature_data',
        'signed_at',
        'comments',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'signed_at' => 'datetime',
        'signature_data' => 'array',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(LoanPhase::class, 'loan_phase_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasSigned(): bool
    {
        return !is_null($this->signed_at) || !is_null($this->signature_path);
    }
}
