<?php

namespace App\Support;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Enabled site languages, administered in the CMS and cached. */
class Locales
{
    public const CACHE_KEY = 'fco.languages';

    /** @return array<string, array{name: string, native_name: string, direction: string, is_default: bool}> */
    public static function all(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('languages')) {
                    return [];
                }

                return Language::query()->where('is_enabled', true)->orderBy('sort_order')->get()
                    ->mapWithKeys(fn (Language $l) => [$l->code => [
                        'name' => $l->name,
                        'native_name' => $l->native_name,
                        'direction' => $l->direction,
                        'is_default' => $l->is_default,
                    ]])->all();
            }) ?: self::fallback();
        } catch (Throwable) {
            return self::fallback();
        }
    }

    /** @return list<string> */
    public static function enabled(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        foreach (self::all() as $code => $lang) {
            if ($lang['is_default']) {
                return $code;
            }
        }

        return config('app.locale', 'en');
    }

    /** Locales that get a URL prefix (every enabled locale except the default). */
    public static function prefixed(): array
    {
        return array_values(array_diff(self::enabled(), [self::default()]));
    }

    public static function isEnabled(string $code): bool
    {
        return in_array($code, self::enabled(), true);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function fallback(): array
    {
        $code = config('app.locale', 'en');

        return [$code => ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true]];
    }
}
