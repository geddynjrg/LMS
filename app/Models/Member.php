<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Member extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('member')
            ->logOnly(['name', 'member_Id', 'number', 'gender', 'disabled']) // log only these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // skip logs if no changes
    }

    protected $fillable = [
        'name',
        'member_Id',
        'number',
        'gender',
        'disabled',
    ];
    /**
     * Get all of the comments for the Member
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'member_Id', 'member_Id');
    }

}
