<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Draft → In review → Scheduled → Published → Archived lifecycle.
 *
 * A record is publicly visible when it is Published, or Scheduled with a
 * publication time that has passed.
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    public static function bootPublishable(): void
    {
        static::saving(function ($model) {
            if ($model->status === ContentStatus::Published && ! $model->published_at) {
                $model->published_at = now();
            }
            if (auth()->check()) {
                if (! $model->exists && $model->isFillable('created_by')) {
                    $model->created_by ??= auth()->id();
                }
                if ($model->isFillable('updated_by')) {
                    $model->updated_by = auth()->id();
                }
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', ContentStatus::Published->value)
                ->orWhere(fn (Builder $s) => $s->where('status', ContentStatus::Scheduled->value)->where('published_at', '<=', now()));
        })->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPubliclyVisible(): bool
    {
        $visibleStatus = $this->status === ContentStatus::Published || $this->status === ContentStatus::Scheduled;

        return $visibleStatus && (! $this->published_at || $this->published_at->lte(now()));
    }
}
