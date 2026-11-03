<?php

namespace App\Support;

use App\Models\Church;

class CurrentChurch
{
    public function church(): Church
    {
        return Church::query()
            ->where('slug', config('church.default_slug'))
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function id(): int
    {
        return (int) $this->church()->getKey();
    }
}