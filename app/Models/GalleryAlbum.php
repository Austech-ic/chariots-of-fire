<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected string $translationModel = Translations\GalleryAlbumTranslation::class;

    protected string $translationForeignKey = 'album_id';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['taken_at' => 'date', 'video_urls' => 'array'];


    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_asset_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'album_id')->orderBy('sort_order');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function coverAsset(): ?MediaAsset
    {
        return $this->cover ?? $this->images->first()?->mediaAsset;
    }

    public function extraPublicMediaIds(): array
    {
        return $this->images()->pluck('media_asset_id')->all();
    }
}
