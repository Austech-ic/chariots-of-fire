<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use Concerns\HasTranslations, Concerns\Auditable;

    protected string $translationModel = Translations\HomeSectionTranslation::class;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['is_visible' => 'boolean'];


    public const KEYS = [
        'intro' => 'Ministry introduction',
        'watch' => 'Watch & listen',
        'programmes' => 'Upcoming programmes',
        'gallery' => 'Ministry life (gallery)',
        'connect' => 'Connect & give',
    ];
}
