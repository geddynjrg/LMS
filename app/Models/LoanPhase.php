<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'constituency_code',
        'phase',
        'amount_disbursed',
        'amount_repaid',
    ];


    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }

    public function grouploans(): HasMany
    {
        return $this->hasMany(GroupLoan::class);
    }
}
