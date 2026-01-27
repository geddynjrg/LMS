<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @method bool hasRole($roles, $guard = null)
 * @method bool hasAnyRole(...$roles)
 * @method bool hasAllRoles($roles, $guard = null)
 * @method \Illuminate\Database\Eloquent\Collection assignRole(...$roles)
 * @method \Illuminate\Database\Eloquent\Collection syncRoles(...$roles)
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property string $email
 * @property string|null $id_number
 * @property string|null $phone_number
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $county_code
 * @property string|null $current_constituency_code
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $profile_photo_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Constituency> $constituencies
 * @property-read int|null $constituencies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanPhase> $constituencyOfficers
 * @property-read int|null $constituency_officers_count
 * @property-read \App\Models\County $county
 * @property-read \App\Models\Constituency|null $currentConstituency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanPhase> $hqOfficers
 * @property-read int|null $hq_officers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanPhase> $lockedPhases
 * @property-read int|null $locked_phases_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCountyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCurrentConstituencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'name',
                'email',
                'phone_number',
                'id_number',
            ]) // log only these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // skip logs if no changes
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
            $user->slug = static::generateUniqueSlug($user->name);
        });

        static::updating(function ($user) {
            // Only regenerate slug if name has changed
            if ($user->isDirty('name')) {
                $user->slug = static::generateUniqueSlug($user->name, $user->id);
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
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    protected $with = ['county', 'currentConstituency'];

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
}
