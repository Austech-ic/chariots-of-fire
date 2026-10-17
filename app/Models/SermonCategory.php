<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SermonCategory extends Model
{
    use Concerns\HasTranslations, Concerns\Auditable;

    protected string $translationModel = Translations\SermonCategoryTranslation::class;

    protected string $translationForeignKey = 'category_id';

    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function sermons(): BelongsToMany
    {
        return $this->belongsToMany(Sermon::class, 'sermon_category', 'category_id', 'sermon_id');
    }
}
