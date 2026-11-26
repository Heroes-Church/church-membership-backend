<?php

namespace App\Models;

use App\Models\Concerns\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'name', 'description', 'is_active'];
}
