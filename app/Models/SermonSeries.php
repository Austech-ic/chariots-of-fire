<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SermonSeries extends Model
{
    use Concerns\HasTranslations, Concerns\Publishable, Concerns\Auditable, Concerns\HasRevisions;

    protected $table = 'sermon_series';

    protected string $translationModel = Translations\SermonSeriesTranslation::class;

    protected string $translationForeignKey = 'sermon_series_id';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['is_featured' => 'boolean'];


    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    public function sermons(): HasMany
    {
        return $this->hasMany(Sermon::class, 'series_id');
    }
}
