<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected string $translationModel = Translations\EventTranslation::class;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'recurrence' => 'array', 'registration_enabled' => 'boolean', 'featured' => 'boolean'];


    public function banner(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'banner_asset_id');
    }

    public const TYPES = ['in_person' => 'In person', 'online' => 'Online', 'hybrid' => 'Hybrid'];

    public const LIFECYCLES = ['scheduled' => 'Scheduled', 'postponed' => 'Postponed', 'cancelled' => 'Cancelled'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'parent_event_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Event::class, 'parent_event_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function livestreams(): HasMany
    {
        return $this->hasMany(Livestream::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('ends_at', '>=', now())->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '>=', now()->subHours(3))))
            ->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('ends_at', '<', now())->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '<', now()->subHours(3))))
            ->orderByDesc('starts_at');
    }

    public function isCancelled(): bool
    {
        return $this->lifecycle === 'cancelled';
    }

    public function localStart()
    {
        return $this->starts_at->copy()->setTimezone($this->timezone ?: config('app.display_timezone'));
    }

    public function localEnd()
    {
        return $this->ends_at?->copy()->setTimezone($this->timezone ?: config('app.display_timezone'));
    }

    public function confirmedSeats(): int
    {
        return (int) $this->registrations()->where('status', 'confirmed')->selectRaw('COALESCE(SUM(1 + guests), 0) as seats')->value('seats');
    }

    public function acceptsRegistrations(): bool
    {
        return $this->registration_enabled && ! $this->isCancelled() && $this->starts_at->isFuture();
    }
}
