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

    // Method for eager loading with optimization
    public function loadOptimizedPhases()
    {
        return $this->load([
            'phases' => function ($query) {
                $query->with(['grouploans' => function ($q) {
                    $q->with('group')
                    ->orderBy('level')
                    ->orderBy('disbursement_date');
                }]);
            }
        ]);
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
            'constituency_code',
            'ward_id',
            'constituency_code',
            'id'
        );
    }

    // Fixed: Specify the table for columns to avoid ambiguity
    public function financedLoans()
    {
        return $this->hasManyThrough(
            GroupLoan::class,
            LoanPhase::class,
            'constituency_code', // Foreign key on loan_phases
            'loan_phase_id',     // Foreign key on group_loans
            'constituency_code', // Local key on constituencies
            'id'                 // Local key on loan_phases
        )->where('group_loans.loan_status', 'financed')
         ->select('group_loans.*'); // Explicitly select from group_loans
    }
}
