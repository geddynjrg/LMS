<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Constituency extends Model
{
    use HasFactory;

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function phases(): HasMany
    {
        return $this->hasMany(LoanPhase::class, 'constituency_code', 'constituency_code');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Wards::class, 'constituency_code', 'constituency_code');
    }

     public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'county_code', 'county_code');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_constituencies',
            'constituency_code',
            'user_id',
            'constituency_code',
            'id'
        )->withTimestamps()->withPivot('id');
    }

    public function groups(): HasManyThrough
    {
        return $this->hasManyThrough(
            Group::class,
            Wards::class,
            'constituency_code', // Foreign key on Wards
            'ward_id',           // Foreign key on Groups
            'constituency_code', // Local key on Constituency
            'id'                 // Local key on Wards
        );
    }
}
