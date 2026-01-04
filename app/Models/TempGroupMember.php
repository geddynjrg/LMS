<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'temp_application_id',
        'name',
        'id_number',
        'gender',
        'phone_number',
        'position',
        'photo_member_id',
        'person_with_disability',
        'economic_activity',
        'certificate_of_disability',
        'pwd_number',
        'requires_confirmation',
        'confirmation_token',
        'confirmation_status',
        'confirmation_sent_at',
        'confirmation_confirmed_at',
        'member_id',
    ];

    protected $casts = [
        'photo_member_id' => 'array',
        'certificate_of_disability' => 'array',
        'person_with_disability' => 'boolean',
        'requires_confirmation' => 'boolean',
        'confirmation_sent_at' => 'datetime',
        'confirmation_confirmed_at' => 'datetime',
    ];

    protected $appends = ['is_defaulter'];

    public function getIsDefaulterAttribute()
    {
        return $this->attributes['is_defaulter'] ?? false;
    }

    // Relationships
    public function application()
    {
        return $this->belongsTo(TempApplication::class, 'temp_application_id');
    }
}
