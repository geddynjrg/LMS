<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempApplicationFile extends Model
{
    protected $fillable = [
        'temp_application_id',
        'field_name',
        'original_filename',
        'filename',
        'path',
        'url',
        'mime_type',
        'size',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function application()
    {
        return $this->belongsTo(TempApplication::class);
    }
}
