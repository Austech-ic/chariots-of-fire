<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Speaker extends Model
{
    use Concerns\Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function portrait(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'portrait_asset_id');
    }

    public function sermons(): BelongsToMany
    {
        return $this->belongsToMany(Sermon::class);
    }
}
