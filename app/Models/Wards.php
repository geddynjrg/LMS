<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wards extends Model
{
    use HasFactory;

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'ward_id', 'id');
    }

    public function tempApplications() : HasMany
    {
        return $this->hasMany(TempApplication::class, 'ward_id', 'id');
    }
}
