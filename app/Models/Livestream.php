<?php

namespace App\Models;

use App\Services\Streaming\StreamManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livestream extends Model
{
    use Concerns\Auditable;

    public const STATUSES = ['scheduled' => 'Scheduled', 'live' => 'Live now', 'ended' => 'Ended', 'cancelled' => 'Cancelled'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ended_at' => 'datetime',
        'status_checked_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Livestream $stream) => $stream->forgetLiveCache());
        static::deleted(fn (Livestream $stream) => $stream->forgetLiveCache());
        static::saving(function (Livestream $stream) {
            $input = $stream->external_id ?: $stream->watch_url ?: $stream->embed_url;
            if ($input && ($resolved = app(StreamManager::class)->resolve($stream->provider, $input))) {
                $stream->external_id = $resolved->externalId;
                $stream->embed_url = $resolved->embedUrl;
                $stream->watch_url = $resolved->watchUrl;
            }
        });
    }

    public function forgetLiveCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('fco.live-now');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Sermon::class, 'recording_sermon_id');
    }

    /** A stream is only shown as live when an administrator or the provider confirmed it. */
    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public static function current(): ?self
    {
        return static::visible()->where('status', 'live')->latest('scheduled_at')->first();
    }

    public static function next(): ?self
    {
        return static::visible()->where('status', 'scheduled')->where('scheduled_at', '>=', now()->subHour())->orderBy('scheduled_at')->first();
    }
}
