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

        public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_member_id['url'] ?? null;
    }

    public function getPhotoOriginalFilenameAttribute(): ?string
    {
        return $this->photo_member_id['original_filename'] ?? null;
    }

    public function getPhotoExistsAttribute(): bool
    {
        if (!$this->photo_url) {
            return false;
        }

        $path = public_path($this->photo_url);
        return file_exists($path);
    }

    public function getPwdCertificateUrlAttribute(): ?string
    {
        return $this->certificate_of_disability['url'] ?? null;
    }

    public function getPwdCertificateOriginalFilenameAttribute(): ?string
    {
        return $this->certificate_of_disability['original_filename'] ?? null;
    }

    public function getPwdCertificateExistsAttribute(): bool
    {
        if (!$this->pwd_certificate_url) {
            return false;
        }

        $path = public_path($this->pwd_certificate_url);
        return file_exists($path);
    }


    public function getFileData(string $field, string $key = 'url', $default = null)
    {
        $data = $this->$field;

        if (empty($data) || !is_array($data)) {
            return $default;
        }

        return $data[$key] ?? $default;
    }

    public function hasValidFile(string $field): bool
    {
        $url = $this->getFileData($field, 'url');

        if (!$url) {
            return false;
        }

        $path = public_path($url);

        if (!file_exists($path)) {
            Log::warning('File not found on disk', [
                'model' => static::class,
                'id' => $this->id,
                'field' => $field,
                'url' => $url
            ]);
            return false;
        }

        return true;
    }
}
