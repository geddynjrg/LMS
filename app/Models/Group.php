<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Group extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('group')
            ->logOnly([
                'name',
                'slug',
                'ward_id',
                'identifier',
                'code',
                'gfs_code',
                'date_formed',
                'date_registered',
                'reg_number',
                'bank',
                'account',
                'category',
                'activity',
                'activity_desc',
                'location',
                'box',
                'status',
            ]) // log only these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // skip logs if no changes
    }

    public function getRouteKeyName()
    {
        return 'slug'; // assumes you have a 'slug' column like 'taita-taveta'
    }

    protected static function booted()
    {
        static::creating(function ($group) {
            $group->slug = static::generateUniqueSlug($group->name);
        });

        static::updating(function ($group) {
            // Only regenerate slug if name has changed
            if ($group->isDirty('name')) {
                $group->slug = static::generateUniqueSlug($group->name, $group->id);
            }
        });
    }

    /**
     * Generate a unique slug based on the group name.
     */
    protected static function generateUniqueSlug(string $name, $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }


    protected $fillable = [
        'name',
        'slug',
        'ward_id',
        'identifier',
        'code',
        'gfs_code',
        'date_formed',
        'date_registered',
        'reg_number',
        'bank',
        'account',
        'category',
        'activity',
        'activity_desc',
        'location',
        'box',
        'status',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Wards::class, 'ward_id', 'id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'group_code', 'code');
    }

    public function groupLoans(): HasMany
    {
        return $this->hasMany(GroupLoan::class);
    }

    /**
     * Get the applications associated with this group.
     */
    public function phaseApplications(): HasMany
    {
        return $this->hasMany(PhaseApplication::class);
    }

    /**
     * Helper to get the most recent financed application.
     */
    public function currentLoan(): HasOne
    {
        return $this->hasOne(GroupLoan::class)
        ->latestOfMany()
        ->where(function ($query) {
            $query->where('loan_status', 'financed')
                  ->orWhere('amount_issued', '>', 0);
        });
    }


}
