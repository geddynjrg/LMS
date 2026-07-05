<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Notifications\QueuedVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'name',
                'email',
                'phone_number',
                'id_number',
            ]) // log only these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // skip logs if no changes

        if (app()->runningInConsole()) {
            $options->disableLogging();
        }

        return $options;
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'county_code',
        'current_constituency_code',
        'password',
        'phone_number',
        'id_number',
        'slug',
        'dismissed_officer_guide_at',
    ];

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Helper to check status
     */
    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    public function getRouteKeyName()
    {
        return 'slug'; // assumes you have a 'slug' column like 'taita-taveta'
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!app()->runningInConsole()) {
                $user->slug = static::generateUniqueSlug($user->name);
            }
        });

        static::updating(function ($user) {
            // Only regenerate slug if name has changed
            if ($user->isDirty('name')) {
                if (!app()->runningInConsole()) {
                    $user->slug = static::generateUniqueSlug($user->name, $user->id);
                }
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
        'dismissed_officer_guide_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function sendEmailVerificationNotification()
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class, 'county_code', 'county_code');
    }

    public function constituencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Constituency::class,
            'user_constituencies',         // pivot table
            'user_id',                     // FK on pivot pointing to this model
            'constituency_code',           // FK on pivot pointing to related model
            'id',                          // Local PK on User model
            'constituency_code'            // Local PK on Constituency model
        )->withTimestamps()->withPivot('id'); // Optional pivot columns
    }

    public function currentConstituency() : BelongsTo
    {
        return $this->belongsTo(Constituency::class, 'current_constituency_code', 'constituency_code');
    }

    public function lockedPhases(): HasMany
    {
        return $this->hasMany(LoanPhase::class, 'locked_by');
    }

    public function constituencyOfficers(): HasMany
    {
        return $this->hasMany(LoanPhase::class, 'constituency_officer_id');
    }

    public function hqOfficers(): HasMany
    {
        return $this->hasMany(LoanPhase::class, 'hq_officer_id');
    }

    public function isNationalOfficer(): bool
    {
        return $this->hasAnyRole(['hq_officer', 'head_of_secretariat', 'accountant']);
    }

    public function canAccessHq(): bool
    {
        return \App\Support\HqAccess::userCanAccessRoutes($this);
    }

    public function canApproveHqPhases(): bool
    {
        return \App\Support\HqAccess::userCanApprovePhases($this);
    }

    public function clearLocalAssignments(): void
    {
        $this->constituencies()->detach();

        if ($this->county_code !== null || $this->current_constituency_code !== null) {
            $this->forceFill([
                'county_code' => null,
                'current_constituency_code' => null,
            ])->save();
        }
    }

    public function phaseApplications()
    {
        return $this->hasMany(PhaseApplication::class, 'user_id');
    }
}
