<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected string $translationModel = Translations\PageTranslation::class;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['show_in_footer' => 'boolean'];


    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
