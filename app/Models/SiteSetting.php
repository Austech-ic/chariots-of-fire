<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Settings::flush());
        static::deleted(fn () => Settings::flush());
    }
}
