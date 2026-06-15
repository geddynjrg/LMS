<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConstituency extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'constituency_code',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function Constituency(): BelongsTo {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }
}
