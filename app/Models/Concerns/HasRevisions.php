<?php

namespace App\Models\Concerns;

use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Keeps a JSON snapshot of the record (and its translations) whenever it is saved. */
trait HasRevisions
{
    public static function bootHasRevisions(): void
    {
        static::saved(function ($model) {
            if (! $model->wasRecentlyCreated && ! $model->wasChanged()) {
                return;
            }
            $snapshot = $model->attributesToArray();
            if (method_exists($model, 'translations')) {
                $snapshot['translations'] = $model->translations()->get()->map->attributesToArray()->all();
            }
            $model->revisions()->create(['snapshot' => $snapshot, 'user_id' => auth()->id()]);
        });
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisionable')->latest('id');
    }
}
