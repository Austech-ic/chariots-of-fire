<?php

namespace App\Models;

use App\Services\Streaming\StreamManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SermonExternalSource extends Model
{
    protected $fillable = ['sermon_id', 'provider', 'external_id', 'embed_url', 'watch_url', 'is_embeddable'];

    protected $casts = ['is_embeddable' => 'boolean'];

    protected static function booted(): void
    {
        // Normalise pasted URLs into provider IDs and embed/watch URLs.
        static::saving(function (SermonExternalSource $source) {
            $resolved = app(StreamManager::class)->resolve($source->provider, $source->external_id ?: $source->watch_url);
            if ($resolved) {
                $source->external_id = $resolved->externalId;
                $source->embed_url = $resolved->embedUrl;
                $source->watch_url = $resolved->watchUrl;
            }
        });
    }

    public function sermon(): BelongsTo
    {
        return $this->belongsTo(Sermon::class);
    }
}
