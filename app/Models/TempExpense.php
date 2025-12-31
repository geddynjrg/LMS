<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'temp_application_id',
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function application()
    {
        return $this->belongsTo(TempApplication::class, 'temp_application_id');
    }
}
