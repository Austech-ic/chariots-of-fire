<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sermon extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected string $translationModel = Translations\SermonTranslation::class;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['preached_at' => 'date', 'featured' => 'boolean', 'members_only' => 'boolean'];


    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'thumbnail_asset_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(SermonSeries::class, 'series_id');
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(SermonCategory::class, 'sermon_category', 'sermon_id', 'category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(SermonMedia::class)->orderBy('sort_order');
    }

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'sermon_media')->withPivot(['media_type', 'download_allowed']);
    }

    public function externalSources(): HasMany
    {
        return $this->hasMany(SermonExternalSource::class);
    }

    public function audio(): ?SermonMedia
    {
        return $this->media->firstWhere('media_type', 'audio');
    }

    public function video(): ?SermonMedia
    {
        return $this->media->firstWhere('media_type', 'video');
    }

    public function youtube(): ?SermonExternalSource
    {
        return $this->externalSources->firstWhere('provider', 'youtube') ?? $this->externalSources->first();
    }

    public function mediaTypes(): array
    {
        $types = $this->media->pluck('media_type')->unique()->values()->all();
        if ($this->externalSources->isNotEmpty()) {
            $types[] = 'video';
        }

        return array_values(array_unique($types));
    }

    /** Uploaded audio/video of a public (non members-only) sermon is publicly playable. */
    public function extraPublicMediaIds(): array
    {
        return $this->media()->pluck('media_asset_id')->all();
    }
}
