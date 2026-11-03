<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'contact_email',
        'contact_phone',
        'address',
        'logo_path',
        'primary_color',
        'secondary_color',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function memberProfiles(): HasMany
    {
        return $this->hasMany(MemberProfile::class);
    }

    public function familyGroups(): HasMany
    {
        return $this->hasMany(FamilyGroup::class);
    }
}