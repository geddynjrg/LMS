<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempApplicationApproval extends Model
{
    protected $fillable = [
        'temp_application_id',
        'user_id',
        'level',
        'action',
        'amount',
        'remarks',
    ];

    /**
     * Relationship to the application being approved.
     */
    public function tempApplication(): BelongsTo
    {
        return $this->belongsTo(TempApplication::class, 'temp_application_id');
    }

    /**
     * Relationship to the officer who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
