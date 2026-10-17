<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Key/value site configuration editable from the CMS. */
class Settings
{
    public const CACHE_KEY = 'fco.settings';

    public const DEFAULTS = [
        'site_name' => 'Flaming Chariot Outreach',
        'tagline' => 'Igniting Faith. Transforming Lives.',
        'contact_email' => null,
        'contact_phone' => null,
        'contact_address' => null,
        'whatsapp_number' => null,
        'social_youtube' => null,
        'social_facebook' => null,
        'social_instagram' => null,
        'social_x' => null,
        'footer_text' => null,
        'counselling_intro' => null,
        'counselling_confidentiality' => null,
        'giving_intro' => null,
        'donation_presets' => '5000,10000,25000,50000,100000',
        'donation_currencies' => 'NGN',
        'default_og_image' => null,
        'analytics_snippet' => null,
    ];

    public static function all(): array
    {
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('site_settings')) {
                    return [];
                }

                return SiteSetting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        return array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null && $v !== ''));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
