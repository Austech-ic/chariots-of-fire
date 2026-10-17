<?php

namespace App\Models;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use Concerns\Auditable;

    protected $fillable = ['code', 'name', 'native_name', 'direction', 'is_default', 'is_enabled', 'sort_order'];

    protected $casts = ['is_default' => 'boolean', 'is_enabled' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Language $language) {
            if ($language->is_default) {
                $language->is_enabled = true;
            }
        });
        static::saved(function (Language $language) {
            if ($language->is_default) {
                static::query()->whereKeyNot($language->getKey())->update(['is_default' => false]);
            }
            Locales::flush();
        });
        static::deleted(fn () => Locales::flush());
    }
}
