<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
        'url',
        'temp_user_id',
        'temp_application_id',
        'field_name',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(TempUser::class, 'temp_user_id');
    }

    public function application()
    {
        return $this->belongsTo(TempApplication::class, 'temp_application_id');
    }
}
