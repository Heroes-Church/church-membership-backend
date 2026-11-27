<?php

namespace App\Models;

use App\Models\Concerns\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'attendance_session_id',
        'member_id',
        'checked_in_by',
        'check_in_method',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    protected $appends = ['user_id', 'user'];

    protected $hidden = ['member'];

    public function getUserIdAttribute(): ?int
    {
        return $this->member?->user_id;
    }

    public function getUserAttribute(): ?User
    {
        return $this->member?->loadMissing('user:id,name,church_id')->user;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}