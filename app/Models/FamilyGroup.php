<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToChurch;

class FamilyGroup extends Model
{
    use BelongsToChurch;
    
    protected $fillable = [
        'name',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(MemberProfile::class);
    }
}
