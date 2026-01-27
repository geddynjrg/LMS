<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    use HasFactory;
    public function getRouteKeyName()
    {
        return 'slug'; // or 'county_name_slug'
    }

    public function constituencies(): HasMany
    {
        return $this->hasMany(Constituency::class, 'county_code', 'county_code');
    }
}
