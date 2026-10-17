<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mandate extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected string $translationModel = Translations\MandateTranslation::class;

    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'banner_asset_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
