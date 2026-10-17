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
            // Staff without the publish permission can only save drafts or submit for review.
            $user = auth()->user();
            if ($user && method_exists($user, 'hasPermission') && ! $user->hasPermission('content.publish')
                && $model->isDirty('status') && ! in_array($model->status, ContentStatus::editorOptions(), true)) {
                $model->status = ContentStatus::InReview;
            }

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

        static::saved(fn ($model) => $model->syncMediaVisibility());
    }

    /** Media attached to visible content becomes publicly servable. */
    public function syncMediaVisibility(): void
    {
        if (! $this->isPubliclyVisible() || ($this->members_only ?? false)) {
            return;
        }

        $ids = collect($this->publicMediaColumns())->map(fn ($c) => $this->{$c})->filter()->all();
        if (method_exists($this, 'extraPublicMediaIds')) {
            $ids = array_merge($ids, $this->extraPublicMediaIds());
        }
        if ($ids) {
            \App\Models\MediaAsset::query()->whereKey($ids)->where('visibility', 'private')->update(['visibility' => 'public']);
        }
    }

    /** @return list<string> */
    protected function publicMediaColumns(): array
    {
        return array_values(array_filter(
            ['image_asset_id', 'banner_asset_id', 'thumbnail_asset_id', 'cover_asset_id'],
            fn ($c) => array_key_exists($c, $this->getAttributes()),
        ));
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
