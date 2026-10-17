<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class HeroSlide extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected string $translationModel = Translations\HeroSlideTranslation::class;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];


    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    /** Published slides inside their display window, in order. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->published()
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort_order');
    }
}
