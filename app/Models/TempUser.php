<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'identifier_type',
        'owner_identifier',
        'email',
        'phone_number',
        'name',
        'drupal_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tempApplications()
    {
        return $this->hasMany(TempApplication::class);
    }

    public function tempFiles()
    {
        return $this->hasMany(TempFile::class);
    }
}
