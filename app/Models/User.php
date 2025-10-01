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
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
}
