<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_Id',
        'group_loans_id',
        'role',
    ];

    /**
     * Get the user that owns the GroupMember
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_Id', 'member_Id');
    }

    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class, 'group_loans_id', 'id');
    }

    public function getDefaultArrearsAttribute(): float
    {
        return (float) ($this->groupLoan->total_arrears ?? 0);
    }
}
