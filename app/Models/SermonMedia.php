<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonMedia extends Model
{
    protected $table = 'sermon_media';

    protected $fillable = ['sermon_id', 'media_asset_id', 'media_type', 'download_allowed', 'sort_order'];

    protected $casts = ['download_allowed' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn (self $item) => $item->sermon?->syncMediaVisibility());
    }

    public function sermon(): BelongsTo
    {
        return $this->belongsTo(Sermon::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
