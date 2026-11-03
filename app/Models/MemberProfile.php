<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToChurch;

class MemberProfile extends Model
{

    use BelongsToChurch;
    
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'phone_number',
        'gender',
        'province',
        'city_municipality',
        'barangay',
        'street_address',
        'zip_code',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function familyGroup(): BelongsTo
    {
        return $this->belongsTo(FamilyGroup::class);
    }
}
