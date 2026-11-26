<?php

namespace App\Models;

use App\Models\Concerns\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class MemberStatus extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'code', 'name', 'description'];
}
