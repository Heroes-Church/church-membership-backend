<?php

namespace App\Models\Concerns;

use App\Support\CurrentChurch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToChurch
{
    protected static function bootBelongsToChurch(): void
    {
        static::addGlobalScope('church', function (Builder $query): void {
            $churchId = app(CurrentChurch::class)->id();
            $column = $query->getModel()->qualifyColumn('church_id');

            $query->where($column, $churchId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('church_id') === null) {
                $model->setAttribute(
                    'church_id',
                    app(CurrentChurch::class)->id()
                );
            }
        });
    }
}